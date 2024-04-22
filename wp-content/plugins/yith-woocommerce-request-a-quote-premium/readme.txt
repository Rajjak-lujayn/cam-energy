=== YITH Request a Quote for WooCommerce ===

Contributors: yithemes
Tags: request a quote, quote, yithemes, message, woocommerce, shop, ecommerce, e-commerce
Requires at least: 6.2
Tested up to: 6.4
Stable tag: 4.22.0

The YITH Request a Quote for WooCommerce plugin lets your customers ask for an estimate of a list of products they are interested into.

== Changelog ==

= 4.22.0 – Released on 20 February 2024 =
* New: support for WooCommerce 8.6
* Update: YITH plugin framework

= 4.21.1 – Released on 1 February 2024 =
* Update: YITH plugin framework

= 4.21.0 – Released on 11 January 2024 =
* New: support for WooCommerce 8.5
* Update: YITH plugin framework

= 4.20.0 – Released on 15 December 2023 =
* New: support for WooCommerce 8.4
* Update: YITH plugin framework

= 4.19.1 - Released on 17 November 2023 =
* Update: YITH plugin framework
* Fix: Request a quote button not displayed in the shop pages

= 4.19.0 – Released on 14 November 2023 =
* New: support for WordPress 6.4
* New: support for WooCommerce 8.3
* Update: YITH plugin framework
* Fix: addons not displayed in accepted quote email
* Dev: added new filter "ywraq_customer_info_account_fields"
* Dev: added new filter "ywraq_gform_quote_table_html_format"

= 4.18.0 – Released on 10 October 2023 =
* New: support for WooCommerce 8.2
* Update: YITH plugin framework
* Fix: Override billing and shipping checkout fields
* Fix: add-on price on quote view and email
* Dev: added filter 'ywraq_wp_forms_template_html' to use html (true) or plain (false) template for WP Forms email
* Dev: added new filter 'yith_ywraq_quote_table_total_label'

= 4.17.0 – Released on 7 September 2023 =
* New: support for WooCommerce 8.1
* New: panel UI
* Update: YITH plugin framework
* Fix: avoid fatal error with WC Subscriptions
* Dev: added new filter ywraq_datepicker_min_date

= 4.16.1 – Released on 11 August 2023 =
 * Update: YITH plugin framework
 * Fix: button not visible in single product pages

= 4.16.0 – Released on 7 August 2023 =
 * New: support for WordPress 6.3
 * New: support for WooCommerce 8.0
 * Update: YITH plugin framework
 * Fix: fix Request a quote page message after click on checkout quote button
 * Dev: added support for Woostify product filter

= 4.15.0 – Released on 10 July 2023 =
 * New: support for WooCommerce 7.9
 * Update: YITH plugin framework
 * Fix: avoid displaying 'Add to Quote' button on variable products for WC blocks
 * Fix: fix on preview quote for the country field
 * Fix: minor fixes for YITH WooCommerce Product Add-Ons
 * Fix: fixed issue in the order page where on regular orders the plugin add the new request quote status too as the first option
 * Dev: added filter 'ywraq_get_orders_to_export_args' to manage export get orders args

= 4.14.0 – Released on 14 June 2023 =
 * New: support for WooCommerce 7.8
 * Update: YITH plugin framework
 * Fix: resolved an issue with the check of reCAPTCHA, ensuring that the JavaScript trigger only activates when the plugin has reCAPTCHA activated
 * Fix: css fix for PDF builder template editor on WordPress 6.1
 * Fix: fixed an problem where the expiry date was displayed even when the corresponding option was set to false
 * Fix: minor fix for YITH WooCommerce Product Add-Ons string on the quote view

= 4.13.0 – Released on 8 May 2023 =
 * New: support for WooCommerce 7.7
 * Update: YITH plugin framework
 * Fix: fixed issue with YITH WooCommerce Multi Vendor plugin
 * Fix: fixed the taxes on sold individual items with YITH WooCommerce Product Add-Ons integration
 * Fix: fixed issue on editing the request a quote metabox in order page
 * Fix: fixed style issues on the mini quote widget
 * Dev: added filter yith_wapo_ywraq_total_price to filter the total price of add-ons with YITH WooCommerce Product Add-Ons
 * Dev: added filter ywraq_default_deposit_rate to filter standard value of deposit rate

= 4.12.0 – Released on 13 April 2023 =
 * New: support for WooCommerce 7.6
 * Update: YITH plugin framework
 * Fix: fixed price calculation on variation products with YITH WooCommerce Product Add-Ons
 * Dev: added hooks to Polylang Plugin integration


= 4.11.1 – Released on 14 March 2023 =
 * Fix: empty list for non-logged users at first attempt

= 4.11.0 – Released on 10 March 2023 =
 * New: support for WordPress 6.2
 * New: support for WooCommerce 7.5
 * Update: YITH plugin framework
 * Fix: fixed Clear List button label not showing on frontend
 * Fix: fixed issue with gravity form addresses
 * Fix: fixed grouped YITH WooCommerce Product Add-Ons with the same key
 * Fix: fixed avoid double YITH WooCommerce Product Add-Ons value
 * Fix: fixed background color in PDF created by Gutenberg blocks
 * Fix: fixed form sent twice when submitting request from Cart page
 * Fix: forced session load on quote page

= 4.10.0 – Released on 9 February 2023 =
* New: support for WooCommerce 7.4
* Update: YITH plugin framework
* Tweak: mini quote widget with product names clickable
* Fix: show price when the quote is not new even if the total hide price is set on
* Fix: fixed restricted payment gateways for quotes on the "Pay for order" page
* Fix: fixed totals not updating on the Quote table when an item is removed
* Fix: fixed empty email to admin when on quote there are only vendor products
* Fix: password validation
* Dev: added filter 'ywraq_validate_ajax_quantity' to manage the max quantity of an item on the quote list
* Dev: added filter 'yith_ywraq_send_email_request_quote_form_fields' to add custom fields in the email

= 4.9.0 – Released on 9 January 2023 =
* New: support for WooCommerce 7.3
* Update: YITH plugin framework
* Fix: fixed issue with recaptcha v2
* Fix: fixed issue with YITH WooCommerce Product Add-Ons on product price calculation
* Fix: avoid fatal error if wp_get_attachment_image_src() returns false
* Dev: added filter ywraq_inquiry_gravity_form_id to filter the gravity form

= 4.8.0 – Released on 9 December 2022 =
 * New: support for WooCommerce 7.2
 * Update: YITH plugin framework
 * Fix: fixed add-ons shown in the admin email
 * Fix: added a check to avoid issue with the plugin WPForms User Registration
 * Fix: fixed wrong option value for pdf template from list

= 4.7.2 – Released on 11 November 2022 =
 * Fix: patched security vulnerability

= 4.7.1 – Released on 4 November 2022 =
 * Fix: fixed issue on PDF template editor

= 4.7.0 – Released on 3 November 2022 =
 * New: support for WordPress 6.1
 * New: support for WooCommerce 7.1
 * Update: YITH plugin framework
 * Fix: fixed issue with YITH WooCommerce Product Add-Ons on product price calculation
 * Fix: fixed issue with tax in 'ywraq_formatted_line_total' function
 * Dev: added filter 'ywraq_preview_slug' to change the preview slug after the quote has been created as in some server there's a redirect and the preview argument is removed.
 * Dev: added tag selector to css to hide quote button in order to avoid issues with some themes
 * Dev: added javascript dispatch event 'ywraq_default_form_sent' on default form
 * Dev: added filter 'ywraq_product_image' in quote table pdf builder template, to fix the issue with Product Add-Ons option plugin

= 4.6.0 – Released on 5 October 2022 =
 * New: support for WooCommerce 7.0
 * Update: YITH plugin framework
 * Fix: removed 'quote_number' placeholder when the PDF is created from the quote list
 * Fix: fixed image and meta data for label add-ons in the integration with Contact Form 7
 * Fix: removed YITH Request a Quote from the option to select the different payment methods
 * Dev: new filter 'ywraq_items_cookie_expiration_time' to set the expiration time for cookies
 * Dev: added the item object as an addition argument to the filter 'ywraq_quote_subtotal_item'

= 4.5.1 – Released on 19 September 2022 =
* Fix: fixed Textarea fields issue on the Request a Quote page
* Fix: fixed issue in quote table with WC_Product_Attribute object
* Dev: added filter 'ywraq_meta_data_do_escape' (default true) to avoid escaping html

= 4.5.0 – Released on 6 September 2022 =
 * New: support for WooCommerce 6.9
 * Update: YITH plugin framework
 * Fix: fixed integration with YITH WooCommerce Wishlist when adding a variable product from the Wishlist to the Quote list
 * Fix: default PDF template when a new template is created
 * Fix: fixed mobile price not hidden if catalog mode is enabled on it
 * Dev: added 'ywraq_quote_item_thumbnail' to filter item quote on quote view page and email

= 4.4.0 – Released on 4 August 2022 =
 * New: support for WooCommerce 6.8
 * Update: YITH plugin framework
 * Fix: fixed issue on shipping tax calculation
 * Fix: fixed billing company on PDF template builder

= 4.3.0 – Released on 12 July 2022 =
 * New: support for WooCommerce 6.7
 * Update: YITH plugin framework
 * Fix: fixed issue with YITH WooCommerce Product Add-Ons on product price calculation
 * Fix: avoid conflict with clean list button and quantity field
 * Fix: minor fix with YITH WooCommerce Gift Cards active
 * Fix: fixed issue with YITH WooCommerce Composite Products on emails
 * Fix: fixed issue with reCAPTCHA v2
 * Fix: added Casper proxy to call for templates in order to avoid problems with OVH and 403 forbidden error
 * Dev: added function 'remove_elementor_scripts' to remove style and script from elementor free that creates issues on PDF template editor

= 4.2.0 – Released on 13 June 2022 =
 * New: support for WooCommerce 6.6
 * Update: YITH plugin framework
 * Fix: issue with Ask again quote button
 * Fix: to show message after quote sent with Contact Form 7
 * Fix: fixed issues on PDF templates blocks
 * Fix: fixed issue with reCAPTCHA v3
 * Fix: fixed country fields on emails
 * Fix: issue with expiration date on emails
 * Dev: new filter 'ywraq_not_set_package_rates' to avoid shipping cost calculations

= 4.1.0 – Released on 9 May 2022 =
 * New: support for WordPress 6.0
 * New: support for WooCommerce 6.5
 * Update: YITH plugin framework
 * Update: mPDF library
 * Fix: fixed issue with Jetpack plugin
 * Fix: fixed issue with YITH WooCommerce Product Add-Ons on emails
 * Fix: fixed issue with the checkboxes on default form
 * Fix: show empty list template when clicking "Clear list" button
 * Fix: fixed issue with YITH WooCommerce Minimum Maximum Quantity for min max and checkout manager
 * Fix: added missing argument in do action 'woocommerce_order_item_meta'
 * Fix: fixed issue with reCAPTCHA
 * Fix: fixed problem on request a quote table when a product has addons and role based prices set



= 4.0.1 – Released on 4 April 2022 =
 * Fix: fixed issue on Exclusion List settings page
 * Fix: fixed issue with reCAPTCHA v2
 * Fix: fixed the max quantity check on default form

= 4.0.0 – Released on 30 March 2022 =
 * New: support for WooCommerce 6.4
 * New: added the button "Ask quote" on the Cart page to allow users to convert the cart content into a quote request
 * New: PDF Quote builder to create custom templates for the quotes
 * New: PDF button to convert the list into a PDF on the Request a quote page
 * New: added the email "pending quote"; if enabled this email is sent when the customer does not accept/reject the quote within x days
 * New: added version 3 of reCAPTCHA
 * New: "Ask new quote" action in quotes list in My Account
 * New: "Order again" action in quotes list in My Account
 * New: added option to change the icon of the Mini quote widget
 * New: integration with YITH Easy Order Page for WooCommerce v 1.8.0
 * Tweak: improved the style and layout of emails
 * Tweak: improved Accept and Reject buttons on the "My Account" page to be customized by the Admin panel
 * Tweak: changed the PDF library from DOMPDF into mPDF 8.0.5
 * Update: YITH plugin framework
 * Fix: fixed issue with YITH WooCommerce Product Add-Ons with the retrieved value of the first radio addon option
 * Fix: added missing parameter for 'yith_ywraq_widget_quote' shortcode
 * Fix: make YITH WooCommerce Product Add-Ons image larger on Request a quote list
 * Dev: new filter 'ywraq_product_image' for YITH WooCommerce Product Add-Ons integration

= 3.10.0 – Released on 2 March 2022 =
 * New: support for WooCommerce 6.3
 * Update: YITH plugin framework
 * Fix: fixed update status when quote expires
 * Dev: added additional arguments to filter 'ywraq_label_discount_coupon'

= 3.9.0 – Released on 9 February 2022 =
 * New: support for WooCommerce 6.2
 * Update: YITH plugin framework
 * Fix: fix for coupons added to the quote
 * Dev: added filter to change label coupon - 'ywraq_label_discount_coupon'

= 3.8.0 – Released on 25 January 2022 =
* New: support for WordPress 5.9
* Update: YITH plugin framework
* Fix: fixed issue with percentage discounts
* Fix: added WPML translation for YITH WooCommerce Product Add-Ons in the Quote table
* Fix: fixed duplicated YITH WooCommerce Product Add-Ons in the cart after accepting the quote
* Fix: fixed "State" field displayed in customer's email
* Fix: fixed issue with button clear list label option

= 3.7.0 – Released on 4 January 2022 =
* New: support for WooCommerce 6.1
* Fix: issue with YITH Multi Currency Switcher for WooCommerce
* Fix: fixed issues between grouped products and YITH WooCommerce Product Add-Ons
* Fix: missing thumbnail image in the email table template
* Fix: avoid to create a new coupon if one is applied to the quote editor
* Dev: added filter 'ywraq_register_panel_capability' to change the capability of register panel

= 3.6.0 – Released on 2 December 2021 =
* New: support for WooCommerce 6.0
* Update: YITH plugin framework
* Fix: fixed addon values when they are added to the cart after accepting the quote
* Fix: display addons on cart after accepting quote
* Fix: do not show empty addons on emails
* Fix: hide product add-ons free in cart
* Dev: added offset parameter to Cross Sells shortcode
* Dev: added new filter 'ywraq_thankyou_page_url' to filter the thank you page url

= 3.5.0 – Released on 9 November 2021 =
* New: support for WooCommerce 5.9
* New: Help tab in admin panel
* Update: YITH plugin framework
* Fix: issue with price with YITH WooCommerce Product Add-Ons plugin
* Dev: added filter 'yith_wapo_show_addons_price_on_raq' to manage the  price with YITH WooCommerce Product Add-Ons

= 3.4.0 – Released on 13 October 2021 =
* New: support for WooCommerce 5.8
* Update: YITH plugin framework
* Dev: added filter 'ywraq_gf_product_item' to manage the Gravity Form item text in hidden fields
* Dev: added filter 'ywraq_additional_email_content_array' to manage customer info inside quote editor

= 3.3.2 – Released on 27 September 2021 =
* Update: YITH plugin framework
* Fix: debug info feature removed for all logged in users
* Fix: fixed issue with Ninja form

= 3.3.1 – Released on 16 September 2021 =
Fix: fixed style on My Account page

= 3.3.0 – Released on 13 September 2021 =
Tweak: Option to choose the type of user that can see the "Add to quote" button.
Tweak: the "Request a quote" style page when the list is empty
Fix: Issue with YITH WooCommerce Product Add-Ons 2.0
Fix: fixed integration with Contact Form 7

= 3.2.0 – Released on 9 September 2021 =
New: support for WooCommerce 5.7
Update: YITH plugin framework
Fix: Issue with YITH WooCommerce Product Add-Ons 2.0
Fix: Removed expiration date on My Account quote page if the quote is on state 'new'
Dev: added AJAX action "prdctfltr_respond_550" for WooCommerce Product Filter plugin
Dev: new filters 'ywraq_addon_item_name_view', 'ywraq_addon_item_name_order' and 'ywraq_addon_item_name' to change add-ons labels

= 3.1.7 – Released on 17 August 2021 =
New: support for WooCommerce 5.6
Update: YITH plugin framework
Fix: fixed issues with YITH WooCommerce Product Add-Ons 2.0
Fix: added gateway description property in order to avoid issues with WooCommerce PayPal Payments
Fix: fixed issues inside the widget list on mobile
Fix: price format in totals column in the quote requests list
Dev: added a new filter 'ywraq_admin_send_quote_label' to change the label to send quote button in admin
Dev: added default form placeholder to wpml-config.xml file
Dev: addedd 'ywraq_request_list_item_total' filter to the totals column in the quote requests list

= 3.1.6 – Released on 1 July 2021 =
New: support for WordPress 5.8
New: support for WooCommerce 5.5
New: support for YITH WooCommerce Product Add-Ons 2.0
Fix: fixed issues with WPForms
Dev: added a new filter 'yith_ywraq_quote_deposit_amount' to customize the YITH WooCommerce Deposit and Down Payments deposit amount
Dev: added filter 'ywraq_csv_charset' to change csv charset

= 3.1.5 – Released on 18 June 2021 =
Tweak: added a new option to show the original price with a strikethrough if a discounted price is shown on all quotes
Tweak: added {quote_user} placeholder to use inside the email with quote
Update: YITH plugin framework
Update: WPML config XML file
Fix: fixed issue with the product's SKU inside the Email with Quote
Fix: fixed issues with "Quote button" inside the Gutenberg and Elementor blocks
Dev: added yith_ywraq_frontend_action_list filter to load the frontend class on Ajax calls
Dev: added yith_wapo_order_item_addon_price filter to customize the YITH WooCommerce Product Add-Ons price inside the order item meta

= 3.1.4 – Released on 27 May 2021 =
New: support for WooCommerce 5.4
New: integration with YITH WooCommerce Multi Currency
Update: YITH plugin framework
Fix: fixed PDF div template
Fix: avoid sending email if it is disabled from settings
Fix: disable new order email for quote sent on checkout
Fix: fixed behaviour of quote checkout link
Fix: fixed composite product image not hidden even if selected

= 3.1.3 – Released on 5 May 2021 =
New: support for WooCommerce 5.3
Update: YITH plugin framework
Fix: fixed wrong value of radio fields inside the email content
Fix: fixed missing data-title inside the table template on quotes view page
Fix: fixed form validation on required fields
Fix: fixed accepted quote redirect with WPML
Fix: fixed with shipping methods
Dev: added a new filter 'ywraq_hide_payment_method_pdf'

= 3.1.2 – Released on 12 April 2021 =
New: support for WooCommerce 5.2
Tweak: improved pdf preview on quote editor
Update: YITH plugin framework
Fix: fixed required check for state select field in default form
Fix: prevent fatal error on "My Account" view quote page.
Fix: pdf layout issue when the thumbnail is not shown
Fix: avoid sending YITH_YWRAQ_Send_Quote_Reminder email if it's disabled
Fix: added quotes endpoint label option to wpml xml file
Fix: keep form when last product is removed from Quote List if "Show form even with empty list" option is enabled
Fix: fixed issue on view my quote detail
Dev: added filter yith_wapo_cart_item_addon_price

= 3.1.1 – Released on 8 March 2021 =
Fix: fixed shortcode to show "Add to quote" button
Fix: fixed add to cart javascript issue for variable products
Fix: escaping issues
Fix: fixed cron time option
Fix: integration with Composite products when the component has the counted separately option enabled on mini-widget and email
Fix:  checked if version constant is defined for Ninja Forms
Dev: added the filter 'ywraq_customer_fields_to_hide' to hide specific fields in the customer request details

= 3.1.0 – Released on 23 Feb 2021 =
New: support for Wordpress 5.7
New: support for WooCommerce 5.1
New: support for Ninja Forms
New: support for WPForms
New: request tab for a complete overview of all quotes requests
New: quote payment settings to set the default setting to apply to all new quotes
New: Gutenberg block to add "Add to Quote" button
New: Elementor widget to add "Add to Quote" button
Tweak: exclusion list to add products, categories and tag from a single table
Update: YITH plugin framework
Fix: updated option key on wpml-config.xml

= 3.0.4 – Released on 02 Feb 2021 =
Fix: fixed add to quote button issues for out of stock variations
Fix: fixed the issues with YITH WooCommerce Deposit and Down Payments
Fix: avoid Fatal error if Order Creation is off
Fix: fixed check for the guest customer

= 3.0.3 – Released on 25 Jan 2021 =
New: support for WooCommerce 5.0
Fix: fixed "accepted" label on My Account page
Fix: fixed issue with old option for the exclusion list
Fix: issue during the PDF creation when a product doesn't exist
Dev: added new filter 'ywraq_pdf_enable_remote' to enable remove image file loading

= 3.0.2 – Released on 21 Jan 2021 =
Fix: Fixed the issue with user registration when default form is seletect
Fix: Fixed double order issue when a quote has been accepted and paid
Fix: Fixed integration with YITH WooCommerce Deposit and Down Payments
Fix: Fixed issue with Pay Order Now

= 3.0.1 – Released on 20 Jan 2021 =
Fix: Cron setting
Fix: Email table quote template
Fix: Expiry time option settings


= 3.0.0 – Released on 19 Jan 2021 =
New: Plugin panel
New: Added new options to improve the plugin customization
New: Restyling quote list and single quote detail
New: Endpoint "Quote" on "My account" page
Update: YITH plugin framework

= 2.4.3 – Released on 29 Dec 2020 =
New: Support for WooCommerce 4.9
Update: YITH plugin framework
Fix: Fix wrong order number if the plugin YITH WooCommerce Multi Vendor is active, in the confirmation customer email
Dev: Added filter 'yith_ywraq_widget_classes' and 'ywraq_default_form_account_username'

= 2.4.2 – Released on 1 Dec 2020 =
New: Support for WooCommerce 4.8
Update: YITH plugin framework
Fix: Avoid empty success notices when using CF7

= 2.4.1 – Released on 23 Nov 2020 =
New: Support for German language
Update: YITH plugin framework
Fix: Fixed show detail inside the quote

= 2.4.0 – Released on 4 Nov 2020 =
New: Support for Wordpress 5.6
New: Support for WooCommerce 4.6
New: Support for Greek language
New: Added shortcode yith_ywraq_cross_sells to show cross-sells on Quote page
Update: YITH plugin framework
Fix: Fixed exclusion list issues for categories and tags
Dev: Added filter "yith_ywraq_metabox_fields" and "ywraq_rejected_message"

= 2.3.9 – Released on 17 Sep 2020 =
New: Support for WooCommerce 4.6
Update: YITH plugin framework
Fix: Fixed integration with YITH WooCommerce Deposit and Down Payments
Fix: Fixed billing info not filled after that the quote is accepted
Dev: Added filter 'ywraq_clear_list_label' for "Clear list" button label
Dev: Added filter 'yith_ywraq_render_button_check_loop'

= 2.3.8 – Released on 17 Sep 2020 =
New: Support for WooCommerce 4.5
Update: YITH plugin framework
Fix: Quantity update process to allow decimal quantity
Dev: Added filter "ywraq_reject_quote_text"

= 2.3.7 – Released on 14 Aug 2020 =
Update: YITH plugin framework
Tweak: Improved style to Mini quote plugin
Tweak: Improved the Clear list button
Fix: Fixed the issue with Contact form 7
Fix: Fixed issue with required option is default form editor
Fix: Changed sanitization on the radio input field in default form to avoid issues with Japanese chars

= 2.3.6 – Released on 10 Aug 2020 =
New: Support for WordPress 5.5
New: Support for WooCommerce 4.4
New: Support for Polish language
Update: YITH plugin framework
Fix: variation not showing when requesting quote from checkout
Fix: Integration between YITH WooCommerce Request a Quote + YITH WooCommerce Catalog Mode +  YITH WooCommerce Product Add-ons
Fix: Order status labels for My Account page

= 2.3.5 – Released on 2 Jul 2020 =
New: Support for WooCommerce 4.3
Tweak: Replaced default form email regex with the same used by WooCommerce
Update: YITH plugin framework
Fix: Vendor's sub-quotes not visible in my-account
Fix: Fixed add to quote for grouped products
Fix: Fixed ajax update only on RAQ page
Fix: Fixed the widget list quote
Fix: Added email description field on Accepted/rejected Quote email
Fix: Fixed some style issues in PDF templates

= 2.3.4 – Released on 26 May 2020 =
New: Support for WooCommerce 4.2
Update: YITH plugin framework
Fix: Fixed escaping issues
Fix: Fixed BCC email field
Fix: Fixed YITH WooCommerce Minimum Maximum Quantity conflict

= 2.3.3 – Released on 4 May 2020 =
New: Support for WooCommerce 4.1
Update: YITH plugin framework
Update: Language Files
Fix: Fixed integration with YITH WooCommerce Deposit and Down Payments when there are fees on the cart
Fix: Fixed auto expiration date issue
Fix: Fixed missing information for shortcode Mini Quote list
Fix: Request a quote button on variations
Fix: Issue with WooCommerce Multilingual plugin

= 2.3.2 – Released on 9 April 2020 =
Fix: Fixed issue during the checkout
Fix: German language
Fix: Fixed issue in Return to shop button

= 2.3.1 – Released on 6 April 2020 =
Fix: Fixed "show title" option on mini quote widget

= 2.3.0 – Released on 6 April 2020 =
New: Added the option to show the form next to the quote list
New: Added the option to enable the loading of any cacheable quote button via AJAX
Tweak: Improved Mini quote widget
Tweak: Added an option to choose the columns to show inside the quote pdf table list
Tweak: Improved YITH Proteo Theme integration
Update: YITH plugin framework
Fix: Fixed subject email issue for customer confirmation email
Dev: Added filter ywraq_payment_method_label to change the quote payment method label

= 2.2.9 – Released on 6 March 2020 =
Fix: Fixed issue with showing the quote button on out-of-stock products

= 2.2.8 – Released on 4 March 2020 =
New: Support for WordPress 5.4
New: Support for WooCommerce 4.0
New: Support for YITH Proteo theme
New: Elementor widget - Request a quote button
New: Added option to show a button to clear all request list items in the table on request quote page
New: Added button to order a Quote again from My Account page
Tweak: Show the date of creation of the Quote in PDF document
Tweak: Improved WPML integration
Update: YITH plugin framework
Update: Spanish language
Update: Italian language
Update: Plugin templates
Fix: Automatic update of quote list when the quantity has changed
Fix: Frontend notices issue
Dev: New filter 'ywraq_number_items_count'
Dev: New do_actions before and after request a quote table
Dev: Added _created_via custom meta to quote

= 2.2.7 – Released on 23 December 2019     =
New: Support for WooCommerce 3.9
Update: Italian language
Update: YITH plugin framework
Fix: Fixed issue with customer email subject not changing
Fix: Fixed placeholders in the email
Fix: Fixed German translation
Fix: Fixed issue with mixed languages in the pdf table when using with WPML
Fix: Fixed WPML & Contact Form 7

= 2.2.6 – Released on 03 December 2019 =

Fix: Fatal Error with WooCommerce Multilingual 4.7.5

= 2.2.5 – Released on 26 November 2019 =
New: Added "* Sold individually" option to every individual item (same default behaviour of Product Add-ons)
Tweak: Added order_comments to field mapping
Update: YITH plugin framework
Update: Dutch language
Fix: Fixed order status of "pay for order" quotes
Fix: Hide product name and quantity on sold individually items from Product Add-ons
Fix: Fixed issue with removing all selected elements in Payment Gateway field in Quote Settings Panel
Dev: Added new filters "yith_ywraq_wapo_add_sold_individually_tag" and "ywraq_override_shipping_method"

= 2.2.4 – Released on 30 October 2019 =
Update: YITH plugin framework

= 2.2.3 – Released on 22 October 2019 =
New: Support for WooCommerce 3.8
New: Support for WordPress 5.3
New: Added the option to show Request a quote button inside WooCommerce Blocks

= 2.2.2 – Released on 14 October 2019 =
Fix: Problem when adding items to quote.

= 2.2.1 – Released on 14 October 2019 =
Update: Italian language
Update: Spanish language
Update: Dutch language
Fix: PDF creation on automatic quote from checkout
Fix: Button behavior for composite products
Fix: Text decoding for product add-ons
Fix: WPML integration
Fix: Product Add-ons meta in email
Fix: File upload if there is a validation
Fix: Avoid order creation on Gravity Forms if "Enable order creation" option is disabled.
Fix: Added composite information to widgets templates
Fix: Updated JS files
Dev: Added filter ywraq_pdf_crypt_file_name
Dev: Added filter ywraq_request_a_quote_send_email_to_vendor_recipient
Dev: Added filter yith_ywraq_auto_update_cart_on_quantity_change
Dev: Added filter ywraq_hide_payment_method_pdf
Dev: Added filter ywraq_sku_label_html

= 2.2.0 - Released on 21 May 2019 =
New: Support for WooCommerce 3.7.0
New: Added tags for the exclusion product
Tweak: Select fields of default form now shows the real value in emails instead of the key name of the value
Tweak: Added AJAX support to WooCommerce Product Table Pro
Update: Italian language
Update: YITH plugin framework
Fix: Issue with YITH Product Add-ons
Fix: Form validation
Fix: Fixed cron gap name
Fix: Quote can be expired also if accepted


= 2.1.9 - Released on 21 May 2019 =
Update: Language Files
Update: YITH plugin framework
Fix: Issue with request a quote button at and payment methods on checkout page
Fix: Fixed acceptance field localization
Fix: issue YITH Product Add-ons attached files
Dev: Added filter 'ywraq_override_checkout_fields'

= 2.1.8 - Released on 29 April 2019 =
New: Added a new option to set the available gateways to finalize the quote process
Tweak: Set send button as disabled when request was successfully placed and we need to redirect customer, to avoid multiple clicks
Update: Language Files
Update: YITH plugin framework
Fix: Automated sending quote
Fix: Fixed update quantity button behavior
Fix: Fixed CF7 issue with WC 3.6.0
Fix: Fixed raq prefix item meta displaying in emails


= 2.1.7 - Released on 16 April 2019 =
Tweak: Added out of stock message if selected variation is out of stock - the out of stock message can be managed also by filter yith_ywraq_variation_outofstock_label
Tweak: Handle placeholder in custom email content
Tweak: Added possibility to export product list with gravity form
Update: Language Files
Fix: Variations and out of stock
Fix: Fatal error on quote creation (backend) with wc 3.3.5
Fix: Fixed cart hash for WC 3.6.0
Fix: Fixed YITH composite products integration
Dev: Added filter ywraq_list_show_product_permalinks

= 2.1.6 - Released on 05 April 2019 =
New: Support for WooCommerce 3.6.0
New: Email confirmation for customer
Tweak: Automatic Quotes, now it is possible to sent immediately quotes without cron settings
Tweak: Added checkout button label option
Update: YITH plugin framework
Update: Language Files
Update: Dompdf library 0.8.3
Fix: Fixed possible issue with default form on settings tab
Fix: Fixed empty list message
Fix: Fixed quote prices when the quote is sent from checkout
Dev: New action 'ywraq_after_create_order_from_checkout'
Dev: New filter yith_ywraq_quote_list_empty_message inside the widget

= 2.1.5 - Released on 12 March 2019 =
Update: YITH plugin framework
Update: Language Files
Tweak: Remove discount coupon on cart if the quantity of a product is less than the quantity on quote
Fix: Fixed link add to quote for variables
Fix: Get default value of $override_shipping if not stored yet
Fix: Fixed check if product is excluded for yith_ywraq_button_quote shortcode
Fix: Fixed ReCaptcha conflicts with YITH WooCommerce Customize My Account
Dev: Add filter for frontend action list
Dev: Added trigger for widget refreshed 'yith_ywraq_widget_refreshed'
Dev: Added checkbox case to print a value for 1 and 0 as Yes and No, also added a filter for each text: 'yith_wraq_checkbox_yes_text' and 'yith_wraq_checkbox_no_text'
Dev: Added filters 'ywraq_email_filled_form_fields' and ywraq_gf_title_desc
Dev: Added key to wpml-config.xml

= 2.1.4 - Released on 29 January 2019 =
New: Option to add the Quote Author inside the PDF Header
Tweak: Added 'add to quote' button for YITH Composite Product type on loop
Tweak: Send customer email when the order is on-hold
Tweak: Send an email to admin when a quote change status from pending quote to on hold
Tweak: Session creation
Update: YITH plugin framework
Update: Language Files
Fix: Send customer processing email and new order email, when a quote change status from ywraq-pending to processing
Fix: Refresh the widget when the quantity is updated on quote list
Fix: Fixed quote table list template
Fix: WPML with YITH Product Add-ons attached files
Fix: Fixed product subtotal on widgets
Fix: Adjust price in composite products
Fix: Add $order->save after set new quote function
Fix: Check rqa_captcha value
Dev: Added do_action 'ywraq_added_to_quote_by_url' in add_to_quote_action on success so you can redirect to request quote page if asked

= 2.1.3 - Released on 03 January 2019 =
New: Compatibility with theme Vitrine
New: Support for WordPress 5.0
Tweak: Integration with YITH Quick Order Forms for WooCommerce
Update: YITH plugin framework
Fix: Integration with YITH WooCommerce Deposits and Down Payments Premium
Fix: Fixed send quote action when a webook on order is set
Fix: widget's options weren't being saved
Fix: Integration with YITH Booking for WooCommerce
Fix: Fixed quote list flushing when using non-raq forms
Dev: Added nocache_headers to RAQ list page

= 2.1.2 - Released on 05 December 2018 =
Update: YITH plugin framework
Fix: YITH WooCommerce Composite Product integration

= 2.1.1 - Released on 29 November 2018 =
Update: YITH plugin framework
Dev; Added filter 'ywraq_change_paper_orientation' for PDF orientation
Fix: Map fields default log error.

= 2.1.0 - Released on 20 November 2018 =
New: Option to add the "Request a quote" button to Checkout page
New: Email to remind a quote is about to expire
New: Option to set an expiration date for the quote automatically
New: Integration with YITH WooCommerce Deposits and Down Payments Premium
New: Integration with Quick Order Forms for WooCommerce Premium
New: Integration with Gutenberg and WordPress 5.0 beta 5
Update: YITH plugin framework
Update: Language Files
Tweak: Quote list widget - the request a quote button is now shown when an item is removed from the list
Tweak: Added an alert if a variation is not selected in single product page
Tweak: Added handle to drag and drop fields in the default form editor
Dev: Added filter 'yith_pdf_logo_id' to the PDF template header to fix functions if the logo doesn't show in PDF
Dev: Added filter 'yith_ywrad_hide_cart_single_css' to the PDF template header to fix functions if the logo doesn't show in PDF
Dev: Added localize arg & filter to frontend.min.js to prevent RAQ page table from refreshing
Fix: Fixed issue when an order is on hold and quote has expired
Fix: Fixed integration with Yith Composite Products for WooCommerce
Fix: WooCommerce Multilingual checking installation
Fix: Fixed redirect to thank you page for Gravity Form
Fix: Fixed integration with YITH WooCommerce Product Add-ons and grouped products
Fix: Fixed YITH WooCommerce Added to Cart Popup integration


= 2.0.15 - Released on 2 November 2018 =
Update: YITH plugin framework
Dev: Added new action ywraq_before_default_form
Fix: Adding variable products to list when no variation is selected.
Fix: User registration
Fix: Avoid multi email change status to administrator
Fix: Hiding price option not working for composite products on emails.
Fix: RTL issue

= 2.0.14 - Released on 23 October 2018 =
New: Added RTL support for email tables
Update: YITH plugin framework
Update: Language Files
Fix: Fix js error with quick view

= 2.0.13 - Released on 08 October 2018 =
New: Support for WooCommerce 3.5
Fix: Form default saving
Fix: Javascript error with variations
Fix: Fixed localized data-titles in Request a quote page table for mobile
Fix: Fixed  YITH WooCommerce Minimum Maximum Quantity messages not appearing
Fix: Fixed mini quote widget title not translating
Fix: Fix for YITH WooCommerce Product Add-ons to not show add-on price in list if the option Hide all prices is checked
Fix: Issue with YITH WooCommerce Tab Manager


= 2.0.12 - Released on 26 September 2018 =
Tweak: Itegration with YITH WooCommerce Minimum Maximum Quantity 1.3.3
Update: YITH plugin framework
Update: Language Files.
Dev: New filter 'ywraq_quote_item_name' to customize the product item name.

= 2.0.11 - Released on 20 September 2018 =
New: Hebrew language files by Arye Stern.
New: New Shortcode [yith_ywraq_mini_widget_quote] to show the mini-quote widget.
Tweak: Check and show a message when all the child products of a grouped product have quantity zero.
Tweak: Default form editor now saves the fields by Ajax to fix some issues with the latest version of WPML.
Tweak: Added data-title property to table rows.
Fix: Fixed issue with billing name when saving a quote.
Fix: Fixed javascript issues with the plugin WP reCaptcha Integration.
Fix: Fixed password error even if the registration is optional.
Fix: wpml-config.xml for compatibility with WPML.
Fix: Fixed issue when an order in pending quote didn't turn the status into complete.
Dev: Added a filter 'ywraq_show_taxes_quote_list' to show simple taxes on Request a quote List and Email.
Dev: Added new filter 'ywraq_request_quote_page_args' to filter the options of the shortcode [yith_ywraq_request_quote]. Update: Language files.

= 2.0.10 - Released on 06 September 2018 =
Fix: Activation licence issue

= 2.0.9 - Released on 05 September 2018 =
Update: Language files
Fix: Fixed exclusion checkbox on the product page
Fix: Fix on type field "Acceptance" on default form
Fix: Fixed accept/reject button behaviour on my account
Fix: Allow shortcodes to be executed on AJAX calls
Fix: Added check to avoid to hide add to cart button on the external page if the product price is empty
Fix: Fixed custom item meta issue

= 2.0.8 - Released on 28 August 2018 =
New: Textarea on Reject Quote page. Now, when the customer can leave a feedback when rejecting a quote.
New: Option inside the quote's editor to allow the customer to "pay now" for the order, without adding billing/shipping information. This information must be filled before sending the quote.
New: Added function to show custom item meta on cart and checkout
New: Format date picker option
New: Added new type field "Acceptance" to add, for example, a "Privacy Policy" checkbox that should be set by the customer before sending the request. It supports shortcode [terms] and [privacy_policy] (from WooCommerce 3.4.0).
Update: YITH plugin framework
Update: Language files
Fix: Integration with Yith Composite Products for WooCommerce plugin
Fix: My account quote template with billing fields
Fix: Email template path
Fix: Issue between YITH WooCommerce Product Add-ons and WPML
Dev: Added filter ywraq_item_thumbnail

= 2.0.7 - Released on 01 August 2018 =
New: Added translatable options for WPML (default form fields).
New: Added new option to format the time on time picker
Tweak: Check change quantity in the cart if a quote is accepted
Tweak: Avoid "There are no shipping methods available" message when adding shipping items with no shipping method.
Update: Language files
Update: YITH plugin framework
Fix: Empty Cart after that the request is sent
Fix: A warning message when a product price is empty
Fix: Expiration of a Quote
Fix: Show price in Pending Quote
Fix: Block checkout fields from read-only to disabled
Fix: Added some checks to avoid warnings
Fix: YITH Contact Form & WPML issues
Fix: Contact Form 7 and special tags issue
Fix: Undefined index attachments in vendor's email
Fix: Creation and attachment of PDF during cron job
Fix: List products in exclusion list tab
Fix: Admin email Copy Carbon to the user.
Fix: Removed non-ASCII symbols for translation files
Dev: Added 'ywraq_before_submit_default_form' filter
Dev: Added 'ywraq_ajax_add_item_is_valid' filter
Dev: Added 'ywraq_check_send_email_request_a_quote' filter

= 2.0.6 - Released on 25 May 2018 =
Fix: Issue with YITH_Privacy_Plugin_Abstract class


= 2.0.5 - Released on 24 May 2018 =
New: Added coupon expiration as quote expiration
New: Option to allow or not the add to cart after a quote is accepted
New: Privacy Policy Guide
Update: Localization files
Update: YITH plugin framework
Fix: Script load in checkout page
Dev: New filter 'ywraq_link_to_registered'

= 2.0.4 - Released on 10 May 2018 =
New: Support for Wordpress 4.9.6 beta 2
New: Support for WooCommerce 3.4.0 RC1
New: GDPR - Export personal data embed in WC_Order
New: GDPR - Erase personal data in WC_Order
Update: Localization files
Fix: Fixed error on upload field empty if not required
Dev: New filter 'ywraq_rejected_quote_message'
Dev: New filter 'ywraq_reject_quote_button_text

= 2.0.3 - Released on 26 April 2018 =
Fix: Form fields management on email body
Fix: Error function is_quote not exist
Fix: Quote Metaboxes show only for Quote order

= 2.0.2 - Released on 24 April 2018 =
Fix: Integration with YITH WooCommerce Quick View 1.3.2
Fix: Files in Request a quote email attachments

= 2.0.1 - Released on 20 April 2018 =
New: Support for woocommerce multilingual 4.2.10
Tweak: Billing information on quote email and pdf
Update: Localization files
Fix: Show button request a quote option
Fix: Option timepicker in default form
Fix: Integration with YITH WooCommerce Quick View 1.3.2
Fix: Default form fixed alignment for h3 in default form
Dev: Added filter 'override_shipping_option_default_value' on 'ywraq_disable_shipping_method' option (quote order admin side) to change the default value


= 2.0.0 - Released on 12 April 2018 =
New: Updated default form, now with customizable fields
New: Gravity form with new feature to link shipping address to an "Address" field
New: Contact Form 7 with new feature to link fields to WooCommerce User Fields
New: Admin option to change label "Product already on the list."
New: Admin option to change label "Browse the list"
New: Admin option to change label "Product added to the list!"
New: Option to Auto-complete the request a quote form fields linked to WooCommerce User Fields (only for default form)
Tweak: Exclusion list with product and categories exclusion
Tweak: Optimized admin setting pages with new features and tabs
Tweak: Registration of a guest in the default form to request a quote
Update: Localization files
Update: Plugin Core 3.0.13
Fix: Integration with YITH Booking for WooCommerce and YITH WooCommerce Product Add-Ons
Fix: Refresh table list only in the Request a quote page.
Dev: Refactoring of some classes
Dev: Deprecated functions 'yith_ywraq_locate_template', 'ywraq_get_quote_line_total', 'ywraq_get_quote_total', 'ywraq_get_browse_list_message', 'ywraq_convert_date_format', 'ywraq_adjust_type'
Dev: Methods of YITH_Request_Quote class: 'get_errors' and 'filter_woocommerce_template'

= 1.9.3 - Released on 30 January 2018 =
New: Support for WooCommerce 3.3.x
Update: YITH plugin framework
Fix: Column total header not hiding
Dev: New javascirpt trigger 'ywraq_table_reloaded'
Dev: New filter 'ywraq_formatted_discount_line_total'

= 1.9.2 - Released on 25 January 2018 =
New: Support for WooCommerce 3.3 RC1
New: German language
New: Dutch language
Update: YITH plugin framework
Fix: Issue that added to cart the elements of quote after
Fix: YITH WooCommerce Composite Product integration > mail thumbnail + check variation_data
Fix: Composit Product data on email
Fix: Metabox on off issue
Dev: Added 'ywraq_form_title' filter

= 1.9.1 - Released on 05 January 2018 =
Tweak: Update table list when the raq page is loaded
Update: YITH plugin framework
Fix: Reset value in js to prevent save the quote when an order is saved
Fix: Datepicker format on Quote Page

= 1.9.0 - Released on 05 December 2017 =
New: Added ReCaptcha to default form
New: Compatibility with WooCommerce Added to Cart Popup Premium version 1.2.8
Tweak: Totals in quote list
Update: Localization files
Update: YITH plugin framework
Fix: Redirect for contact form 7 when the form is not of the request
Dev: Added parameters to method must_be_showed of YITH_Request_Quote_Premium class
Dev: Added method 'get_raq_page_id' on YITH_Request_Quote class


= 1.8.1 - Released on 10 November 2017 =
Fix: Contact form 7 redirect to the Thank you page
Fix: Error with Polylang and global variable $sitepress
Fix: method must_be_showed params

= 1.8.0 - Released on 30 October 2017 =
New: Added hash to pdf file name
Update: Localization files
Fix: Fee taxes in the checkout page
Fix: Integration with YITH WooCommerce Sequential Order Number
Fix: Contact form 7 redirect to the Thank you page
Fix: Quote logo position
Fix: Loop function if product is null
Fix: Gravity Form redirect

= 1.7.9 - Released on 10 October 2017 =
New: Support for WooCommerce 3.2 RC2
New: Added placeholders {quote_user} e {quote_email} in request a quote email
New: Add New Request a quote status as first when a new quote is created by backend
New: Added check on quote list if product is sold individually
New: Added check if current checkout comes form a quote
Update: YITH plugin framework
Fix: Gravity Form redirect to the thank you page
Fix: Issue on saving the on-off options on Request a quote Metabox
Fix: Order creation issue with contact form 7
Dev: Added filter for no items message 'yith_ywraq_quote_list_empty_message'
Dev: Added filter 'ywraq_force_create_account'

= 1.7.8 - Released on 05 September 2017 =
New: Added check if current checkout comes form a quote
New: Ajax reloaded in the shortcode [yith_ywraq_number_items]
Fix: Fatal Error fixed with the new update of woocommerce multilingual 4.4.1
Fix: Fatal error with WooCommerce POS
Fix: Flatsome issue with quick view
Fix: Fatal error when a product is deleted from product list
Fix: Error on PHP 7.1 for arrays initialized as strings
Fix: Table border in email template
Fix: Taxes on pdf and quote email
Fix: Translation of tables in mobile
Fix: Javascript fix gravity form + avada
Fix: Redirect with contact form 7 when ajax is disabled
Fix: Integration with YITH WooCommerce Product Add-Ons Premium
Dev: Added filter 'ywraq_request_a_quote_send_email_from_address'
Dev: Added filter ywraq_request_a_quote_send_email_from_name'
Dev: Added filter ywraq_other_email_content_key passed on email 'ywraq_quote_status' the object email
Dev: Added handling for CC option


= 1.7.7 - Released on 22 June 2017 =
New: Support for WooCommerce 3.1 RC
Update: DOMPDF Library 0.8.0
Fix: Redirect to thank you page after Gravity form is sent
Fix: Gravity form get_forms function
Fix: Hide add to cart in loop
Fix: Total price of product with add-ons (YITH WooCommerce Product Add-Ons Premium)
Dev: "ywraq_quote_accepted_statuses_send" and "ywraq_quote_accepted_statuses_edit" filters
Dev: added backorder filter for out of stock items
Dev: added attachment filter on emails

= 1.7.6 - Released on 05 June 2017 =
Fix: Missing form in the request a quote email
Fix: Wpml cart redirect
Fix: Double meta with product add-on free

= 1.7.5 - Released on 30 May 2017 =
New: Support for WooCommerce 3.0.7
New: Option to show total in quote list
New: Map between quote and extra fields in default form
Update: YITH plugin framework
Fix: Pdf pagination option
Fix: Cart page as redirect after quote acceptance
Fix: Contact form 7 with WPML
Fix: Add to quote for grouped
Fix: Date format in quote email
Fix: Add to quote button with product addons in loop
Fix: Vendor user doesn't receive the quote email notification
Fix: Hide button in variable products
Fix: Fix variation thumbnail in pdf

= 1.7.4 - Released on 26 April 2017 =
New: Support for WooCommerce 3.0.4
Update: YITH plugin framework
Fix: Quantity in single product page
Fix: Display of thumbnails in some email clients
Fix: Removed loading of PrettyPhoto in single product page
Fix: Email to Vendors in the integration with YITH WooCommerce Multi Vendor Premium

= 1.7.3 - Released on 19 April 2017 =
Update: YITH plugin framework
Fix: Thumbnail view in some mail client
Fix: Transform the quote into order after that the quote is accepted
Fix: Select of products and categories into exclusion tab
Dev: Added a filter 'ywraq_hide_add_to_cart_single'

= 1.7.2 - Released on 17 March 2017 =
New: Support for WooCommerce 3.0 RC 2
New: Added a check if user email is valid
Update: YITH plugin framework
Fix: Hide price in product variations
Fix: Additional information in Requesta a quote page for YITH WooCommerce Product Add-Ons Premium
Fix: Additional information in Requesta a quote page for YITH Composite Product for WooCommerce
Dev: Added filter for Gravity Form 'ywraq_gravity_form_installation'

= 1.7.1 - Released on 09 March 2017 =
Fix: Issue with YITH WooCommerce Product Add-Ons Premium
Fix: Issue with Request a quote button and the button add to cart in variable products
Update: YITH plugin framework

= 1.7.0 - Released on 06 March 2017 =
New: Support for WooCommerce 2.7 RC 1
New: Support for 'upload' type file of YITH WooCommerce Product Add-Ons Premium 1.2.4
Update: YITH plugin framework

= 1.6.3 - Released on 30 January 2017  =
New: Option to override the shipping of shop from the quote
New: Option to override the billing/shipping info in the checkout page after that the quote is accepted
New: Option to lock billing/shipping info in the checkout page after that the quote is accepted
New: DOM PDF Library ready for the font 'fireflysung' Chinese font
New: DOM PDF Library ready for the font 'nanumbarungothic' Korean font
Tweak: Empty cart if a customer deletes a payment after that the quote is accepted
Tweak: Added the filter 'ywraq_meta_data_carret' to format the metadata in the single item of quote
Tweak: Compatibility with YITH WooCommerce Minimum Maximum Quantity
Tweak: Item data value on variation products
Fix: Automate quote process
Fix: Display button in single product page

= 1.6.2.3 - Released on 11 January 2017  =
Fix: Logo image in pdf quote

= 1.6.2.2 - Released on 11 January 2017  =
New: Russian translation
New: Quantity validation in single product page
Fix: Check on quantity fields in quote list
Fix: Contact form 7 additional fields the quote metabox
Fix: Automate quote process

= 1.6.2.1 - Released on 09 December 2016  =
Fix: Show button in single product page
Fix: Quote list in my account

= 1.6.2 - Released on 07 December 2016  =
New: Support for Wordpress 4.7
Fix: Show button in single product page

= 1.6.1 - Released on 03 December 2016  =
Fix: Hide add to cart button in single product page

= 1.6.0 - Released on 02 December 2016  =
New: Integration with Gravity Forms plugin to create custom forms for quote requests
New: Create quotes in the backend
New: Option to show the "Request a quote" button next to the "Add to Cart" button in single product page
New: Show/hide the "Request a Quote" button on out of stock products
New: Filter arguments for the button template using  'ywraq_add_to_quote_args'
New: WPML string translation in the request-a-quote email
New: Method to add an item in the list from query string
Tweak: Hide add to cart button
Fix: Removed Notice when the redirect to a thank you page is set
Update: YITH plugin framework


= 1.5.8 - Released on 03 October 2016  =
New: Integration with plugin YITH Composite Product for WooCommerce 1.0.1

= 1.5.7 - Released on 29 September 2016  =
New: Integration with plugin YITH WooCommerce Sequential Order Number Premium v.1.0.8
New: Integration with plugin YITH WooCommerce Product Bundles Premium v.1.1
New: Request a quote button visible in variation products
New: Filter 'ywraq_exclusion_limit' to change the number or products in page in the exclusion list
New: Shortocode [yith_ywraq_number_items] to show the number of items in list
Update: YITH plugin framework

= 1.5.6 - Released on 26 August 2016 =
New: Triggers in javascript add to quote events
New: Added total on request a quote list and email request a quote
Fix: some issue with WooCommerce Multilingual issue

= 1.5.5 - Released on 01 August 2016 =
Fix: Issue in the quote number

= 1.5.4 - Released on 07 July 2016 =
New: an option to add default shipping cost on quote
Fix: save option of single Product Settings for quote requests issue
Fix: some issue with WPML

= 1.5.3 - Released on 07 July 2016  =
New: Spanish translation
New: Filter 'ywraq_pdf_file_name' to change the pdf file name
Tweak: Option to site old price in the quote details, email and pdf document
Tweak: Removed quote without items in my account page for YITH WooCommerce Multi Vendor Premium compatibility
Fix: Double orders when a payment is made with a gateway like Paypal
Fix: Shipping Fee for wc 2.6

= 1.5.2 - Released on 28 June 2016 =
New: Norwegian translation
New: {quote_number} as placeholder in the request quote and quote email
Tweak: Pdf creation when WooCommerce PDF Invoices & Packing Slips is installed
Fix: Shipping tax from quote to order
Fix: Template Reject quote

= 1.5.1 - Released on 10 June 2016 =
New: Support for WooCommerce 2.6 RC1
Fix: Auto Save of quantity for formatted input numbers

= 1.5.0 - Released on 01 June 2016 =
Fix: Cron to clean the session on database
Fix: Optional argument to function yith_ywraq_get_product_meta
Update: YITH plugin framework

= 1.4.9 - Released on 25 May 2016 =
New: Support for WooCommerce 2.6 beta 2
New: [yith-request-a-quote-list] tag to Contact Form 7 legend
New: Options to manage the 'Return to Shop' button
New: Option to send quote automatically
New: Associate guests' quotes to newly registered customers using the same email address
Fix: Thank-you page redirect from Contact form 7
Fix: Wrong quote number and link in vendor quote emails

= 1.4.8 - Released on 05 May 2016 =
New: Option to force users to register when requesting a quote
New: Javascript min files

= 1.4.7 - Released on 04 May 2016 =
New: pt_BR translation
New: Compatibility with WooCommerce Advanced Quantity
Fix: Compatibility with YITH WooCommerce Product Add-Ons 1.0.8
Fix: Compatibility with WooCommerce Product Add-ons 2.7.17
Fix: Woocommerce Taxes in order created from a request
Fix: Variation's thumbnails in the quote email and pdf

= 1.4.6 - Released on 19 April 2016 =
New: Option to disable/enable orders
New: External/Affiliate products
Fix: Issue in the request a quote email
Fix: Variation details in the order

= 1.4.5 - Released on 12 April 2016 =
Fix: Contact form 7 issue after the latest update
Fix: The add to quote of grouped products

= 1.4.4 - Released on 11 April 2016 =
New: An option to hide or show the details of the quote after send the request of quote
New: A button "Return to shop" when the list is empty
New: A button "Return to shop" at the bottom of the list
New: Css classes inside the message when the list is empty
New: Compatibility with YITH WooCommerce Advanced Product Options
New: Compatibility with WooCommerce Composite Products
New: Options to customize the text message to show after request a quote sending
New: Options hide "Accept" button in the Quote
New: Options to change "Accept" button Label
New: Option to choose the page linked by Accept Quote Button. The default value is the page Checkout, change the page to disable the checkout process
New: Options hide "Reject" button in the Quote
New: Options to change "Reject" Button Label
New: A new order status Accepted used when the process to checkout is disabled
New: For default form you can choose now if each additional field is required or not
New: Option to hide the total column from the list
Tweak: Contact form 7 hidden when the list is empty
Tweak: Shipping methods and shipping prices are now set in the checkout
Tweak: Compatibility with YITH Woocommerce Email Templates Premium
Update: Template email quote-table.php and request-quote-table.php removed double border to the table
Update: YITH plugin framework
Fix: Download PDF now is showed after that the order is completed
Fix: Additional Field on Contact form 7 now are added into the quote email and in the Quote page details
Removed: File inlcudes/hooks.php all content now is in  YITH_YWRAQ_Frontend Class constructor

= 1.4.3 - Released on 14 March 2016 =
New: compatibility with YITH WooCommerce Minimum Maximum Quantity
New: compatibility with YITH WooCommerce Customize My Account Page
New: Attribute 'show_form' on shortcode 'yith_ywraq_request_quote' can be 'yes'|'no'

= 1.4.2 - Released on 07 March 2016 =
Fix: Ajax Calls for WooCommerce previous to 2.4.0
Fix: Notice in compatibility with Multi Vendor Premium
Update: YITH plugin framework

= 1.4.1 - Released on 04 March 2016 =
Fix: Request a quote order settings saving fields
Fix: Enable CC Options in Request a quote email settings

= 1.4.0 - Released on 02 March 2016 =
New: YITH WooCommerce Multi Vendor Premium 1.9.5 compatibility
New: Filter 'ywraq_clear_list_after_send_quote' to clear/not the list in request quote page
New: More details in the Quote Order Metabox
Update: button loading time for variations products
Fix: Loading of metabox in specific pages
Fix: Calculation totals for enables taxes

= 1.3.5 - Released on 19 January 2016 =
New: WooCommerce 2.5 compatibility
Fix: Send quote issue

= 1.3.4 - Released on 18 January 2016 =
New: Two more text field in default form
New: WooCommerce 2.5 RC 3 compatibility
Fix: compatibility with WooCommerce Product Addons
Update: YITH plugin framework

= 1.3.3 - Released on 30 December 2015 =
Fix: Update plugin error

= 1.3.2 - Released on 30 December 2015 =
New: WooCommerce 2.5 beta 3 compatibility
Fix: Endpoints for View Detail page
Fix: Email recipients settings to send quote

= 1.3.1 - Released on 15 December 2015 =
Fix: Issue on Number of Request Quote Details after sent the request
Fix: Issues on Contact Form 7 list in settings

= 1.3.0 - Released on 10 December 2015 =
New: Wordpress 4.4 compatibility
New: Optional Attachment in the email of quote
New: Fee and shipping cost to the email and pdf document of quote
New: Two text field to show before and after the product table in the quote email and pdf
New: Admin notice if WooCommerce Coupons are disabled
New: Product Grouped can be added into the request
New: A tab in the settings of the plugin to manage pdf options
New: An option to show "Download PDF" in my account page
New: Option to add a footer in the pdf document
New: An option to show Accept/Reject Quote in pdf document
New: An option to show the button only for out of stock products
New: Autosave increase/decrease quantity in the request quote page
New: The possibility to increase price of products on the quote
New: The possibility to choose the rule of users to show the request a quote button
New: Compatibility with WooCommerce Min/Max Quantities
New: Compatibility with WooCommerce Subscriptions
Update: Changed Text Domain from 'ywraq' to 'yith-woocommerce-request-a-quote'
Update: YITH plugin framework
Fix: Email settings on request quote

= 1.2.3 - Released on 02 October 2015 =
New: Select products to exclude by category

= 1.2.2 - Released on 30 September 2015 =
Fix: Product quantity when button Request a Quote is clicked
New: Woocommerce Addons details in Request Quote Email
New: Compatibily with YITH Essential Kit for WooCommerce #1

= 1.2.1 - Released on 21 September 2015 =
Fix: Show button for Guests
Update: YITH plugin framework

= 1.2.0 - Released on 11 September 2015 =
New: WooCommerce Subscriptions
Fix: Quote send options
Fix: Contact form 7 send email

= 1.1.9 - Released on 11 August 2015 =
New: WooCommerce 2.4.1 compatibility
Update: Changed the spinner file position, it is added to the plugin assets/images
Fix: Email Send Quote changed order id with order number in Accepted/Reject link

= 1.1.8 - Released on 27 July 2015 =
New: 'ywraq_quantity_max_value' for max quantity in the request a quote list
New: Compatibility with WooCommerce Product Add-ons
New: Compatibility with YITH WooCommerce Email Templates Premium
New: Option to choose the link to quote request details to show in "Request a Quote" email
New: Option to choose if after click the button "Request a Quote" go to the list page
New: Options to choose Email "From" Name and Email "From" Address in Woocommerce > Settings > Emails
Fix: Refresh the page after that contact form 7 sent email
Fix: Default Request a Quote form
Fix: Line breaks in request message
Fix: Minor bugs

= 1.1.7 - Released on 03 July 2015 =
Fix: Sending double email for quote
Fix: Reverse exclusion list in single product

= 1.1.6 - Released on 29 June 2015 =
New: Option to show the product sku on request list and quote
New: Option to show the product image on request list and quote
New: Reverse exclusion list
New: Send an email to Administrator when a Quote is Accepted/Rejected
Fix: Contact form 7 send email
Fix: Hide price in variation products

= 1.1.5 - Released on 10 June 2015 =
New: filter for 'add to quote' button label the name is 'ywraq_product_add_to_quote'
Fix: PDF Options settings

= 1.1.4 - Released on 04 June 2015 =
Fix: Show quantity if hide add to cart button
Fix: Minor bugs in backend panel

= 1.1.3 - Released on 28 May 2015 =
New: Additional text field in default form
New: Additional upload field in default form
Fix: Price of variation in email table
Fix: Request Number in Contact form 7

= 1.1.2 - Released on 21 May 2015 =
New: Compatibility with YITH Woocommerce Quick View
Fix: Message of success for guest users
Fix: Show quantity if hide add to cart button
Fix: Layout option tab issue with YIT Framework

= 1.1.1 - Released on 06 May 2015 =
New: Compatibility with YITH WooCommerce Catalog Mode
Fix: When hide "add to cart" button, the variation will not removed

= 1.1.0 - Released on 21 April 2015 =
New: Wrapper div to 'yith_ywraq_request_quote' shortcode
Update: YITH plugin framework
Fix: add_query_arg() and remove_query_arg() usage
Fix: Minor bugs

= 1.0.2 - Released on 21 April 2015 =
New: Attach PDF quote to the email
Update: Compatibility with YITH Infinite Scrolling
Update: YITH plugin framework
Fix: Template to overwrite

= 1.0.1 - Released: 31 March 2015 =
Update: YITH plugin framework

= 1.0.0 =
Initial release
