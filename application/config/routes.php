<?php
defined('BASEPATH') or exit('No direct script access allowed');

//Food

$route['default_controller']                   = 'rider_ctrl/check_connection_controller';
$route['404_override']                            = '';
$route['translate_uri_dashes']                 = FALSE;
$route['validate_login']                        = 'rider_ctrl/validatelogin_controller';
$route['get_customer_orders']                    = 'rider_ctrl/get_customer_orders_controller';
$route['update_intransit_status']                = 'rider_ctrl/update_ontransit_status_controller';
$route['update_delivery_status']                = 'rider_ctrl/update_delivery_status_controller';
$route['get_history_items']                    = 'rider_ctrl/get_history_items_controller';
$route['get_reports_delivered_items']         = 'rider_ctrl/get_reports_delivered_items_controller';
$route['get_reports_undelivered_items']     = 'rider_ctrl/get_reports_delivered_items_controller';
$route['get_items_breakdown']                    = 'rider_ctrl/get_items_breakdown_controller';
$route['verify_old_password']                    = 'rider_ctrl/verify_old_password_controller';
$route['change_password']                        = 'rider_ctrl/change_password_controller';
$route['update_viewed_status']                    = 'rider_ctrl/update_viewed_status_controller';
$route['view_rider_details']                    = 'rider_ctrl/view_rider_details_controller';
$route['count_transactions_and_history']    = 'rider_ctrl/count_transactions_and_history_controller';
$route['get_customer_details']                    = 'rider_ctrl/get_customer_details_controller';
$route['get_tenant_timeframe2']                = 'rider_ctrl/get_tenant_timeframe2_controller';
$route['update_cancelled_status']                = 'rider_ctrl/update_cancelled_status_controller';
$route['get_reports_cancelled_items']            = 'rider_ctrl/get_reports_cancelled_items_controller';
$route['save_image']                            = 'rider_ctrl/save_image_controller';
$route['get_addons_breakdown']                    = 'rider_ctrl/get_addons_breakdown_controller';
$route['get_discount_type']                    = 'rider_ctrl/get_discount_type_controller';
$route['update_confirmed_status']                = 'rider_ctrl/update_confirmed_status_controller';
$route['update_discount_cancelled_status']     = 'rider_ctrl/update_discount_cancelled_status_controller';
$route['submit_discount']                        = 'rider_ctrl/submit_discount_controller';
$route['validateloginwithencryption']            = 'rider_ctrl/validateloginwithencryption_controller';
$route['sendmessage']                            = 'rider_ctrl/sendmessage_controller';
$route['savenewuser']                            = 'rider_ctrl/savenewuser_controller';
$route['validateUsername']                        = 'rider_ctrl/validateUsername_controller';
$route['search_credential']                    = 'rider_ctrl/search_credential_controller';
$route['search_otp']                            = 'rider_ctrl/search_otp_controller';
$route['update_password']                        = 'rider_ctrl/update_password_controller';
$route['validate_login_with_security']            = 'rider_ctrl/validate_login_with_security_controller';
$route['update_rider_blocked_status']            = 'rider_ctrl/update_rider_blocked_status_controller';
$route['load_messages']                        = 'rider_ctrl/load_messages_controller';
$route['get_chatbox_usertype']                    = 'rider_ctrl/get_chatbox_usertype_controller';
$route['get_chatbox_users']                    = 'rider_ctrl/get_chatbox_users_controller';
$route['get_tenants']                            = 'rider_ctrl/get_tenants_controller';
$route['get_tenants_users']                    = 'rider_ctrl/get_tenants_users_controller';
$route['sample_encryption']                    = 'rider_ctrl/sample_encryption_controller';
$route['load_messages_from_transaction']     = 'rider_ctrl/load_messages_from_transaction_controller';
$route['sendmessage_from_transaction']            = 'rider_ctrl/sendmessage_from_transaction_controller';
$route['remove_message']                        = 'rider_ctrl/remove_message_controller';
$route['update_online_status_to_offline']     = 'rider_ctrl/update_online_status_to_offline_controller';

//new routes
$route['getTickets']                         = 'rider_ctrl/get_tickets_ctrl';

//Grocery
$route['gc_get_customer_orders']                = 'Gc_rider_ctrl/gc_get_customer_orders_controller';
$route['gc_get_items_breakdown']                = 'Gc_rider_ctrl/gc_get_items_breakdown_controller';
$route['gc_update_delivery_status']            = 'Gc_rider_ctrl/gc_update_delivery_status_controller';
$route['gc_update_cancelled_status']            = 'Gc_rider_ctrl/gc_update_cancelled_status_controller';
$route['gc_get_customer_details']                = 'Gc_rider_ctrl/gc_get_customer_details_controller';
$route['gc_update_viewed_status']                = 'Gc_rider_ctrl/gc_update_viewed_status_controller';
$route['gc_get_tenant_timeframe']                = 'Gc_rider_ctrl/gc_get_tenant_timeframe_controller';
$route['gc_get_history_items']                    = 'Gc_rider_ctrl/gc_get_history_items_controller';
$route['gc_get_reports_delivered_items']     = 'Gc_rider_ctrl/gc_get_reports_delivered_items_controller';
$route['gc_get_reports_undelivered_items']     = 'Gc_rider_ctrl/gc_get_reports_delivered_items_controller';
