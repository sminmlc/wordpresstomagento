<?php
/*
Plugin Name: Product Display for Magento 
Plugin URI:  https://www.thatsoftwareguy.com/wp_product_display_for_magento.html
Description: Shows off a product from your Magento based store on your blog.
Version:     1.0
Author:      That Software Guy 
Author URI:  https://www.thatsoftwareguy.com 
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: mage_product_display
Domain Path: /languages
*/

function mage_product_display_shortcode($atts = [], $content = null, $tag = '')
{
   // normalize attribute keys, lowercase
   $atts = array_change_key_case((array)$atts, CASE_LOWER);
   $sku = $atts['sku'];

   $headers = connectToMagento();
   $response = getProduct($headers,$sku);


   // Initialize
   $data['name'] = ' ';
   $data['price'] = ' ';
   $data['special'] = ' ';
   $data['link'] = ' ';
   $data['image'] = ' ';
   $data['description'] = ' ';

   $url = getUrl();

   // Fill from response
   $data['name'] = wp_kses_post($response->name);
   $data['price'] = mage_product_display_price(sanitize_text_field($response->price));
   $data['link'] = $url . "catalog/product/view/id/" . sanitize_text_field($response->id);
   foreach ($response->custom_attributes as $attribute) {
      if ($attribute->attribute_code == 'description') {
         // field contains HTML markup
         $data['description'] = wp_kses_post($attribute->value);
      } else if ($attribute->attribute_code == 'thumbnail') {
         $data['image'] = '<img src="' . $url . '/pub/media/catalog/product' . sanitize_text_field($attribute->value) . '" />';
      } else if ($attribute->attribute_code == 'url_key') {
         $data['link'] = $url . sanitize_text_field($attribute->value);
      } else if ($attribute->attribute_code == 'special_price') {
         $data['price'] = mage_product_display_price(sanitize_text_field($attribute->value));
      }
   }

   // start output
   $o = '';

   // start box
   $o .= '<div class="mage_product_display-box">';

   $o .= '<div id="prod-left">' . '<a href="' . $data['link'] . '">' . $data['image'] . '</a>' . '</div>';
   $o .= '<div id="prod-right">' . '<a href="' . $data['link'] . '">' . $data['name'] . '</a>' . '<br />';
   $o .= $data['price'];
   $o .= '</div>';
   $o .= '<div class="prod-clear"></div>';
   $o .= '<div id="prod-desc">' . $data['description'] . '</div>';

   // enclosing tags
   if (!is_null($content)) {
      // secure output by executing the_content filter hook on $content
      $o .= apply_filters('the_content', $content);

      // run shortcode parser recursively
      $o .= do_shortcode($content);
   }

   // end box
   $o .= '</div>';

   // return output
   return $o;
}

function getUrl(){
    $magepd_settings = get_option('magepd_settings');
    $url = $magepd_settings['magepd_url'];
    return $url;
}

//Create magento conexion
function connectToMagento(){

    $magepd_settings = get_option('magepd_settings');

    $userid = $magepd_settings['magepd_userid'];
    $password = $magepd_settings['magepd_password'];
    $url = getUrl();

    //API URL for authentication
    $apiURL = $url . "/rest/V1/integration/admin/token";

    //parameters passing with URL
    $data = array("username" => $userid, "password" => $password);
    $data_string = json_encode($data);

    $response = wp_remote_post($apiURL, array(
        'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
        'body' => $data_string,
    ));

    if (is_wp_error($response)) {
        $o = mage_product_display_get_error("Auth query failure: " . $response->get_error_message());
        return $o;
    }  else if (wp_remote_retrieve_response_code( $response ) != 200) {
        $o = mage_product_display_get_error("Auth unexpected return: " . wp_remote_retrieve_response_message( $response ));
        return $o;
    }

    $token = json_decode(wp_remote_retrieve_body($response));
    $headers = array("Authorization: Bearer " . $token);


    return $headers;
}

function getProduct($headers,$sku){
    //API URL to get product by SKU
    $requestURL = getUrl() . "/rest/V1/products/" . $sku;

    $response = wp_remote_get($requestURL, array(
        'headers' => $headers,
        'body' => null,
    ));
    if (is_wp_error($response)) {
        $o = mage_product_display_get_error("Product query failure: " . $response->get_error_message());
        return $o;
    }  else if (wp_remote_retrieve_response_code( $response ) != 200) {
        $o = mage_product_display_get_error("Product query unexpected return: " . wp_remote_retrieve_response_message( $response ));
        return $o;
    }

    $response = json_decode(wp_remote_retrieve_body($response));
    return $response;
}

function mage_product_display_price($price)
{
   setlocale(LC_MONETARY, 'es_ES');
   //return money_format('%.2n', $price);
   return $price;
}

function mage_product_display_get_error($msg)
{
   $o = '<div class="mage_product_display-box">';
   $o .= $msg;
   $o .= '</div>';
   return $o;
}

function mage_product_display_shortcodes_init()
{
   wp_register_style('mage_product_display', plugins_url('style.css', __FILE__));
   wp_enqueue_style('mage_product_display');

   add_shortcode('mage_product_display', 'mage_product_display_shortcode');
}

function magepd_add_admin_menu()
{
   add_menu_page ( 'Conector Magento', 'Conector Magento', 'manage_options', 'mage_product_display_', 'magepd_options_page', 'dashicons-store' );
}

function magepd_settings_init()
{
   register_setting('magepd_pluginPage', 'magepd_settings');

   add_settings_section(
      'magepd_pluginPage_section',
      __('Configuración', 'wordpress'),
      'magepd_settings_section_callback',
      'magepd_pluginPage'
   );

   $args = array('size' => '80');
   add_settings_field(
      'magepd_url',
      __('URL del Magento (https)', 'wordpress'),
      'magepd_url_render',
      'magepd_pluginPage',
      'magepd_pluginPage_section',
      $args
   );
   add_settings_field(
      'magepd_userid',
      __('Usuario API', 'wordpress'),
      'magepd_userid_render',
      'magepd_pluginPage',
      'magepd_pluginPage_section',
      $args
   );
   add_settings_field(
      'magepd_password',
      __('Contraseña API', 'wordpress'),
      'magepd_password_render',
      'magepd_pluginPage',
      'magepd_pluginPage_section',
      $args
   );
}

function magepd_url_render($args)
{
   $options = get_option('magepd_settings');
   ?>
    <input type='text' name='magepd_settings[magepd_url]' value='<?php echo $options['magepd_url']; ?>'
       <?php
       if (is_array($args) && sizeof($args) > 0) {
          foreach ($args as $key => $value) {
             echo $key . "=" . $value . " ";
          }
       }
       ?>>
   <?php
}

function magepd_userid_render($args)
{
   $options = get_option('magepd_settings');
   ?>
    <input type='text' name='magepd_settings[magepd_userid]' value='<?php echo $options['magepd_userid']; ?>'
       <?php
       if (is_array($args) && sizeof($args) > 0) {
          foreach ($args as $key => $value) {
             echo $key . "=" . $value . " ";
          }
       }
       ?>>
   <?php

}

function magepd_password_render($args)
{
   $options = get_option('magepd_settings');
   ?>
    <input type='text' name='magepd_settings[magepd_password]' value='<?php echo $options['magepd_password']; ?>'
       <?php
       if (is_array($args) && sizeof($args) > 0) {
          foreach ($args as $key => $value) {
             echo $key . "=" . $value . " ";
          }
       }
       ?>>
   <?php
}

function magepd_settings_section_callback()
{
   echo __('Rellena los campos para conectarte al Magento', 'wordpress');
}

function magepd_options_page()
{
   ?>
    <form action='options.php' method='post'>

        <h2>Conector Magento</h2>

       <?php
       settings_fields('magepd_pluginPage');
       do_settings_sections('magepd_pluginPage');
       submit_button();
       ?>

    </form>
   <?php
}

/* Call functions */
add_action('init', 'mage_product_display_shortcodes_init');

add_action('admin_menu', 'magepd_add_admin_menu');
add_action('admin_init', 'magepd_settings_init');


/* START TEST */
add_action('add_meta_boxes', 'add_custom_products_magento');
function add_custom_products_magento()
{
    $screens = ['post', 'wporg_cpt'];
    foreach ($screens as $screen) {
        add_meta_box(
            'wporg_box_id',           // Unique ID
            'Productos del magento',  // Box title
            'wporg_custom_box_html',  // Content callback, must be of type callable
            $screen                   // Post type
        );
    }
}
function wporg_custom_box_html($post)
{
    ?>
    <label for="wporg_field">Selecciona los productos a mostrar...</label>
    <select name="wporg_field" id="wporg_field" class="postbox">
        <option value="">Selecciona...</option>
        <!-- Llamamos función para coger todos los productos de magento -->
    </select>
    <?php
}
/* END TEST */