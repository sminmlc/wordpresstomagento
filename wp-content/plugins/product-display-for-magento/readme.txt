=== Product Display for Magento ===
Contributors: scottcwilson
Donate link: http://donate.thatsoftwareguy.com/
Tags: magento
Requires at least: 4.3 
Tested up to: 4.8
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to easily display products from your Magento v2 installation 
on your WordPress blog using a shortcode.

== Description ==

Product Display for Magento takes a product sku, and pulls in the product name, price, image, description and link, and displays it in a post. 

== Installation ==

Note: This is a two-part install; you have to do some configuration on your Magento installation, then you must install code on your WordPress installation. 

In your Magento admin, do the following: 

1. Login to the Magento 2 Admin Panel.
1. Go to System -> User Roles and press the Add New Role button. 
1. Set the the Role Name to blog.  In Your Password field, enter the current password of your Magento 2 Admin.  Then, on the left side of the screen, click Role Resources.  In the Resource Access, select Custom, and click the Products checkbox under Resources.  Now press the Save Role button. 
1. Go to System -> All Users and press the Add New User button.  Enter all required information, setting the User Name to blog, and the User Password to something *other* than your Admin password.  Then,  on the left side of the screen, click User Role and select blog. Now press the Save User button. 

Install the WordPress part of this mod as usual (using the Install button 
on the mod page on WordPress.org).  The follow these steps: 

1. In your WordPress admin, do the following: 
- In Plugins->Installed Plugins, click the "Activate" link under Product Display for Magento.
- In Settings->Product Display for Magento, set your Magento URL, user name and password.  

To show a specific product on your blog, use the shortcode 
[mage_product_display] with parameter "sku" as a self closing tag.  
So showing the product with SKU "MS06-L-Blue" would be done as follows: 

[mage_product_display sku="MS06-L-Blue"]


== Frequently Asked Questions ==
= Are there any requirements for products I'd like to display? =

The product must have the Visibility setting (in Admin->Products->Catalog->edit product) set so that "Catalog" is included in the value (e.g. "Catalog" or "Catalog,Search").  Otherwise the link back to the product won't work.

= I use a currency other than dollars - how do I change the price display? = 

Modify `product_display_for_magento.php` and change the function `mage_product_display_price`.

== Screenshots ==

1. What the product information in your post will look like. 

== Changelog ==
First version

== Upgrade Notice ==
First version

