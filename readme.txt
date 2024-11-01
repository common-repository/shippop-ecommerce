=== SHIPPOP ===
Contributors: shippop
Tags: ecommerce, e-commerce, sales, sell, shop, cart, checkout, woo commerce,woocommerce , shipping, shippop, SHIPPOP
Requires at least: 4.8
Tested up to: 5.9
Stable tag: trunk
Requires PHP: 5.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin [SHIPPOP](https://wordpress.org/plugins/shippop-ecommerce) is plugin for Woocommerce and this is official plugin from [SHIPPOP](https://shippop.com), Plugin that allows you to easily manage your shipments through the [SHIPPOP](https://shippop.com) system.

== Description ==

Details of the capabilities of this plugin to help you manage your shipping.

[youtube https://www.youtube.com/watch?v=AsiQEgYoVcQ]

Or you have any questions, Please mail to dev@shippop.com
= Backend =

*	You can customize the name, address, contact information for pick-up address, airwaybill, invoice and receipt.
*	You can config parcel box dimention for delivery (optinal).
*	Update shipping status realtime to Woocommerce, and if shipping status is success, it will update order to complete automatic.
*	Realtime price check, cheapest, fastest and various promotions.
*	Show delivery list and COD report.
*	You can export report to .csv file.
*	Show tracking number after booking order, and can check shipping status by tracking number both on the woocommerce order page and order detail page.
*   Can print airwaybill with multiple paper size.
*	Multiple language Thai and English.
*   You can change origin address via filter `shippop_ecommerce_hook_change_origin_address` instead in setting page

= Frontend =

*   See tracking number from each order, and can check tracking status in order list page.

== Hooks ==

= list filter =

* hook name `shippop_ecommerce_hook_change_origin_address` and have 3 parameter ( $from , $order_id , $order_data_booking ) in this callback function [Example](https://github.com/SHIPPOPDEV/simple-hooks-shippop-ecommerce).

== Installation ==

= Minimum specifications =

* PHP 5.5+
* WooCommerce 3.1.2+

= Install for WordPress Store =

Install and Active plugin from "Plugins > Add New > Search SHIPPOP" page.

= Install manual =

1. Extract zip file to folder "/wp-content/plugins"
2. Active Plugin [SHIPPOP](https://wordpress.org/plugins/shippop-ecommerce) in "\wp-admin\plugins.php" page

== Frequently Asked Questions ==

= How do I register to use SHIPPOP? =

You can register from this link [SHIPPOP Register](https://www1.shippop.com/embed/register/woocommerce).

= Where can I download this plugin manual / user guide ? =

You can download plugin manual from 
[SHIPPOP Manual Thai language for Thai user](https://www1.shippop.com/assets/downloads/2021_SHIPPOP_Manual_Woocommerce_TH.pdf)
[SHIPPOP Manual English language for Malaysia user](https://www1.shippop.com/assets/downloads/2021_SHIPPOP_Manual_Woocommerce_EN.pdf)
[SHIPPOP Guide English language for Malaysia user](https://www1.shippop.com/assets/downloads/2021_SHIPPOP_Guide_Woocommerce_EN.pdf) 

= I have more questions Where can I contact? =

You can contact us, from this link [SHIPPOP Support](https://www.shippop.com/contact).

== Screenshots ==

1. Screenshot Login to use SHIPPOP.
2. Screenshot Register to use SHIPPOP.
3. Screenshot Settings and detail page.
4. Screenshot Choose courier delivery.
5. Screenshot Show shipping status.
6. Screenshot Admin will see SHIPPOP system in sidebar menu in order detail.
7. Screenshot Customer see tracking button SHIPPOP in order detail.

== Changelog ==

= 4.5 =
* add check product has weight

= 4.4 =
* replace default product_weight from 1 to 0.001

= 4.3 =
* change default product weight from 1 kg to 1 g

= 4.2 =
* update courier information
* update price table

= 4.1 =
* add filters `shippop_ecommerce_hook_change_origin_address` for change origin address instead in setting page
* fix dimention value to 1

= 4.0 =
* booking on demand delivery

= 3.5 =
* increase timeout

= 3.5 =
* show item can't booking
* filter status

= 3.4 =
* teleport 9 format

= 3.3 =
* merge version custom for kito

= 3.2 =
* add filter status in setting page
* show product in label
* add label addres custom to show in label

= 3.1 =
* add product name to remark

= 3.0.0 =
* Zaviago compatible

= 2.7.2 =
* fix bug get_weight()
* fix bug get CURL
* fix bug www1

= 2.7.1 =
* edit readme

= 2.7.0 =
* tracking purchase after confirm order
* optimize code
* skip complete order if already complete
* add permission_callback in rest api
* adding meta on create order backend

= 2.6.6 =
* fix logo courier

= 2.6.5 =
* fix bug some bug in address parcel show
* adding logo courier

= 2.6.4 =
* fix bug some bug

= 2.6.3 =
* fix bug some bug

= 2.6.2 =
* fix bug some bug

= 2.6.1 =
* fix bug some bug

= 2.6 =
* Ooption select env
* Show/Hide section billing address in SHIPPOP Settings
* Adding courier information
* Adding courier tracking code / tracking code to order meta
* Fix bug get product weight, w/l/h 
* Message notice about webhook
* Adding Role by woocommerce capabilities
* Fix bug weight input number to float number
* Adding message about SHIPPOP Inter

= 2.6.2 =
* fix bug duplicate address
* fix bug post per page limit
* new function to confirm order

= 2.6.1 =
* fix bug

= 2.5.25 =
* add field sub_city.

= 2.5.24 =
* fix somebug

= 2.5.23 =
* fix somebug

= 2.5.22 =
* remove label size sticker from malaysia server.

= 2.5.22 =
* change text incorrect.
* edit readme file.

= 2.5.21 =
* change endpoint route address corrector
* show error message for billing address api

= 2.5.20 =
* change endpoint route address corrector

= 2.5.19 =
* show address error

= 2.5.18 =
* show address error

= 2.5.17 =
* use phpcbf code sniffer
* fix bug address corrector

= 2.5.16 =
* Add translate error code.
* Change UI Dialog, Button

= 2.5.15 =
* Hot fix bug.

= 2.5.14 =
* Hot fix bug.

= 2.5 =
* Add login and register system.
* Add validate address corrector.
* Change some ui layout.
* Fix some bug.
* Add function to use plugin in Malaysia server.
* Add multiple language.

== Upgrade Notice ==

= 4.0 =
* Ondemand delivery

= 3.0 =
* Zaviago compatible

= 2.7 =
* tracking purchase after confirm order
* optimize code
* skip complete order if already complete
* add permission_callback in rest api
* adding meta on create order backend

= 2.6 =
* new feature update

= 2.5 =
* Fix some bug.
* Add address corrector in address pick-up and billing address.

= 2.0 =
* Make user friendly

= 1.0 =
* Release Plugin SHIPPOP
