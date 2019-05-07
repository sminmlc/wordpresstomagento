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

   $magepd_settings = get_option('magepd_settings');
   $url = $magepd_settings['magepd_url'];
   $userid = $magepd_settings['magepd_userid'];
   $password = $magepd_settings['magepd_password'];


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

   //API URL to get product by SKU
   $requestURL = $url . "/rest/V1/products/" . $sku;

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

   // Initialize
   $data['name'] = ' ';
   $data['price'] = ' ';
   $data['special'] = ' ';
   $data['link'] = ' ';
   $data['image'] = ' ';
   $data['description'] = ' ';

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



function mage_product_display_price($price)
{
   setlocale(LC_MONETARY, 'en_US');
   return money_format('%.2n', $price);
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

add_action('init', 'mage_product_display_shortcodes_init');

add_action('admin_menu', 'magepd_add_admin_menu');
add_action('admin_init', 'magepd_settings_init');


function magepd_add_admin_menu()
{


   add_menu_page ( 'Product Display for Magento', 'Product Display for Magento', 'manage_options', 'mage_product_display_', 'magepd_options_page', 'dashicons-store' );

}


function magepd_settings_init()
{

   register_setting('magepd_pluginPage', 'magepd_settings');

   add_settings_section(
      'magepd_pluginPage_section',
      __('Settings', 'wordpress'),
      'magepd_settings_section_callback',
      'magepd_pluginPage'
   );

   $args = array('size' => '80');
   add_settings_field(
      'magepd_url',
      __('Your Magento URL (https)', 'wordpress'),
      'magepd_url_render',
      'magepd_pluginPage',
      'magepd_pluginPage_section',
      $args
   );
   add_settings_field(
      'magepd_userid',
      __('Your Magento blog Userid', 'wordpress'),
      'magepd_userid_render',
      'magepd_pluginPage',
      'magepd_pluginPage_section',
      $args
   );
   add_settings_field(
      'magepd_password',
      __('Your Magento blog Password', 'wordpress'),
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

   echo __('Settings required by this plugin', 'wordpress');

}


function magepd_options_page()
{

   ?>
    <form action='options.php' method='post'>

        <h2>Product Display for Magento</h2>

       <?php
       settings_fields('magepd_pluginPage');
       do_settings_sections('magepd_pluginPage');
       submit_button();
       ?>

    </form>
   <?php

}
