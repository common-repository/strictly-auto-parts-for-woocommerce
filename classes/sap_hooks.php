<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('woocommerce_new_order_item', 'wpsap_function_before_save_order', 10, 3);

function wpsap_function_before_save_order($item_id, $item, $order_id)
{
    if ($item->is_type('line_item')) {
        $product = wc_get_product($item->get_product_id());
        wc_add_order_item_meta($item_id, 'Weight', $product->get_weight());
        wc_add_order_item_meta($item_id, 'Length', $product->get_length());
        wc_add_order_item_meta($item_id, 'Width', $product->get_width());
        wc_add_order_item_meta($item_id, 'Height', $product->get_height());

        $ignoredSkuNumbers = get_option('wc_ignored_sap_sku');
        $ignoredSkuArray = [];
        $valid_sku = 1;
        if (!empty($ignoredSkuNumbers)) {
            $ignoredSkuArray = explode(",", $ignoredSkuNumbers);
        }
        if (in_array($product->get_sku(), $ignoredSkuArray)) {
            $valid_sku = 0;
        }

        wc_add_order_item_meta($item_id, 'valid_sku', $valid_sku);
    }
}

add_action('woocommerce_checkout_create_order', 'get_applied_coupons_on_checkout', 10, 2);

function get_applied_coupons_on_checkout($order, $data)
{
    $couponCode = get_option('wc_pick_code_for_sap');
    $isPickCode = 0;
    if (!empty($couponCode)) {
        $couponCodes = explode(',', $couponCode);
        foreach ($order->get_coupon_codes() as $coupon_code) {
            if (in_array($coupon_code, $couponCodes)) {
                $isPickCode = 1;
            }
        }
    }
    $order->update_meta_data('is_pickup_order', $isPickCode);
}

add_action('woocommerce_new_order', 'wpsap_order_details');

function wpsap_order_details($order_id)
{
    $order = wc_get_order($order_id);
    $is_pickup_order = $order->get_meta('is_pickup_order');

    if ($is_pickup_order == '') {
        $isPickCode = 0;
        $couponCode = get_option('wc_pick_code_for_sap');
        if (!empty($couponCode)) {
            $couponCodes = explode(',', $couponCode);
            foreach ($order->get_coupon_codes() as $coupon_code) {
                if (in_array($coupon_code, $couponCodes)) {
                    $isPickCode = 1;
                }
            }
        }
        $order->update_meta_data('is_pickup_order', $isPickCode);
    }

    $order->update_meta_data('ignored_international_sku', get_option('wc_ignored_world_sap_sku'));
    $order->save();
}

add_action('woocommerce_before_save_order_item', 'wpsap_modified_order_details');

function wpsap_modified_order_details($item)
{
    $weight = $item->get_meta('Weight');
    if (empty($weight)) {
        $product = $item->get_product();
        $weight = $product->get_weight();
        if ($weight) {
            $item->update_meta_data('Weight', $weight);
        }
    }
    $ignoredSkuNumbers = get_option('wc_ignored_sap_sku');
    $ignoredSkuArray = [];
    if (!empty($ignoredSkuNumbers)) {
        $ignoredSkuArray = explode(",", $ignoredSkuNumbers);
    }
    $product = new WC_Product($item['product_id']);
    $valid_sku = in_array($product->get_sku(), $ignoredSkuArray) ? false : true;
    $item->update_meta_data('valid_sku', $valid_sku);
    $dimensions = $item->get_meta('_length');
    if (empty($dimensions)) {
        $product = $item->get_product();
        $dimensions = $product->get_dimensions(false);
        if ($dimensions) {
            $item->update_meta_data('Length', $dimensions['length']);
            $item->update_meta_data('Width', $dimensions['width']);
            $item->update_meta_data('Height', $dimensions['height']);
        }
    }
}

add_filter('woocommerce_settings_tabs_array', 'wpsap_add_ig_sku_settings_tab', 50);

function wpsap_add_ig_sku_settings_tab($settings_tabs)
{
    $settings_tabs['sku_settings_tabs'] = __('SAP Settings', 'Strtictly-Auto-Parts');
    return $settings_tabs;
}

add_action('woocommerce_settings_tabs_sku_settings_tabs', 'wpsap_sku_settings_tabs');

function wpsap_sku_settings_tabs()
{
    woocommerce_admin_fields(wpsap_sku_settings());
}

function wpsap_sku_settings()
{
    $settings = array(
        'section_title' => array(
            'name' => __('SKU SETTINGS FOR SAP', 'Strtictly-Auto-Parts'),
            'type' => 'title',
            'desc' => '',
            'id' => 'wc_cxc_settings_tabs_section_title'
        ),
        'description' => array(
            'name' => __('Ignored SKU', 'Strtictly-Auto-Parts'),
            'type' => 'textarea',
            'id' => 'wc_ignored_sap_sku',
            'autoload' => false,
            'css' => 'min-width:300px;min-height:200px;margin-bottom: 10px;',
            'desc_tip' => __("Enter SKUs that you don't want to send to Strictly Auto Parts for fulfillment. Please make sure to not include any spaces in between SKUs and commas", 'Strtictly-Auto-Parts'),
        ),
        array(
            'name' => __('Ignored International SKU', 'Strtictly-Auto-Parts'),
            'type' => 'textarea',
            'id' => 'wc_ignored_world_sap_sku',
            'autoload' => false,
            'css' => 'min-width:300px;min-height:200px;margin-bottom: 10px;',
            'desc_tip' => __("Enter SKUs that you don't want to send to Strictly Auto Parts for fulfillment. Please make sure to not include any spaces in between SKUs and commas", 'Strtictly-Auto-Parts'),
        ),
        array(
            'name' => __('Code for pick up at SAP', 'Strtictly-Auto-Parts'),
            'type' => 'textarea',
            'id' => 'wc_pick_code_for_sap',
            'autoload' => false,
            'css' => 'min-width:300px;min-height:200px;margin-bottom: 10px;',
            'desc_tip' => __("Orders with this coupon code will be sent to SAP for pick up", 'Strtictly-Auto-Parts'),
        ),
        array(
            'name' => __('Email Subject', 'Strtictly-Auto-Parts'),
            'type' => 'textarea',
            'id' => 'wc_shipment_email_subject',
            'autoload' => false,
            'css' => 'min-width:200px;min-height:200px;margin-bottom: 10px;',
            'desc_tip' => '',
        ),
        array(
            'name' => __('Email Header message', 'Strtictly-Auto-Parts'),
            'type' => 'textarea',
            'id' => 'wc_shipment_email_header',
            'autoload' => false,
            'css' => 'min-width:200px;min-height:200px;margin-bottom: 10px;',
            'desc_tip' => '',
        ),
        array(
            'name' => __('Email message', 'Strtictly-Auto-Parts'),
            'type' => 'textarea',
            'id' => 'wc_shipment_email_content',
            'autoload' => false,
            'css' => 'min-width:200px;min-height:200px;margin-bottom: 10px;',
            'desc_tip' => '',
        ),
        array(
            'name' => __('Tracking email ', 'Strtictly-Auto-Parts'),
            'type' => 'select',
            'id' => 'wc_shipment_email_status',
            'options' => array(
                '0' => __('Off', 'Strtictly-Auto-Parts'),
                '1' => __('On', 'Strtictly-Auto-Parts')
            ),
            'autoload' => false,
            'desc_tip' => '',
        ),
        'section_end' => array(
            'type' => 'sectionend',
            'id' => 'wc_sku_settings_tabs_section_end'
        )
    );

    return apply_filters('wc_sku_settings_tabs_settings', $settings);
}

add_action('woocommerce_update_options_sku_settings_tabs', 'wpsap_sku_update_settings');

function wpsap_sku_update_settings()
{
    woocommerce_update_options(wpsap_sku_settings());
}

add_filter('woocommerce_hidden_order_itemmeta', "wpsap_hidden_order_itemmeta");

function wpsap_hidden_order_itemmeta($args)
{
    $args[] = 'valid_sku';
    $args[] = 'ignored_international_sku';
    $args[] = 'Weight';
    $args[] = 'Length';
    $args[] = 'Width';
    $args[] = 'Height';
    return $args;
}

add_filter('woocommerce_order_item_get_formatted_meta_data', 'wpsap_change_formatted_meta_data', 20, 2);

function wpsap_change_formatted_meta_data($meta_data, $item)
{
    $new_meta = array();
    $ignoredFld = ['valid_sku', 'Weight', 'Length', 'Width', 'Height', 'ignored_international_sku'];
    foreach ($meta_data as $id => $meta_array) {
        if (in_array($meta_array->key, $ignoredFld)) {
            continue;
        }
        $new_meta[$id] = $meta_array;
    }
    return $new_meta;
}

add_filter('manage_edit-shop_order_columns', 'wpsap_shop_order_column', 20);

function wpsap_shop_order_column($columns)
{
    $reordered_columns = array();
    foreach ($columns as $key => $column) {
        $reordered_columns[$key] = $column;
        if ($key == 'order_status') {
            $reordered_columns['shipped_by'] = __('Shipped By', 'Strtictly-Auto-Parts');
        }
    }
    return $reordered_columns;
}

add_action('manage_shop_order_posts_custom_column', 'wpsap_orders_list_column_content', 20, 2);

function wpsap_orders_list_column_content($column, $post_id)
{
    switch ($column) {
        case 'shipped_by' :
            $shipStatus = get_post_meta($post_id, '_shipped_by_sap', true);
            if (!empty($shipStatus) && is_string($shipStatus)) {
                $translatedStatus = esc_html__('%s', 'Strtictly-Auto-Parts');
                $translatedStatusValue = sprintf($translatedStatus, $shipStatus);
                echo $translatedStatusValue;
            } else {
                echo esc_html__('--', 'Strtictly-Auto-Parts');
            }
            break;
    }
}

function wpsap_register_shipped_order_status()
{
    register_post_status('wc-partiallyshipped', array(
        'label' => __('Partially Shipped', 'Strtictly-Auto-Parts'),
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Partially Shipped <span class="count">(%s)</span>', 'Partially Shipped <span class="count">(%s)</span>', 'Strtictly-Auto-Parts')
    ));

    register_post_status('wc-completelyshipped', array(
        'label' => __('Completely Shipped', 'Strtictly-Auto-Parts'),
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Completely Shipped <span class="count">(%s)</span>', 'Completely Shipped <span class="count">(%s)</span>', 'Strtictly-Auto-Parts')
    ));
    register_post_status('wc-pickupatsap', array(
        'label' => __('Pending pick up at SAP', 'Strtictly-Auto-Parts'),
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Pending pick up at SAP <span class="count">(%s)</span>', 'Pending pick up at SAP <span class="count">(%s)</span>', 'Strtictly-Auto-Parts')
    ));
    register_post_status('wc-pickedupatsap', array(
        'label' => __('Picked up from SAP', 'Strtictly-Auto-Parts'),
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Picked up from SAP <span class="count">(%s)</span>', 'Picked up from SAP <span class="count">(%s)</span>', 'Strtictly-Auto-Parts')
    ));
}

add_action('init', 'wpsap_register_shipped_order_status');

add_filter('wc_order_statuses', 'wpsap_order_status');

function wpsap_order_status($order_statuses)
{
    $order_statuses['wc-completelyshipped'] = _x('Completely Shipped', 'Order status', 'Strtictly-Auto-Parts');
    $order_statuses['wc-partiallyshipped'] = _x('Partially Shipped', 'Order status', 'Strtictly-Auto-Parts');
    $order_statuses['wc-pickupatsap'] = _x('Pending pick up at SAP', 'Order status', 'Strtictly-Auto-Parts');
    $order_statuses['wc-pickedupatsap'] = _x('Picked up from SAP', 'Order status', 'Strtictly-Auto-Parts');
    return $order_statuses;
}

add_action('woocommerce_order_note_added', 'wpsap_shipped_status_custom_notification', 10, 2);

function wpsap_shipped_status_custom_notification($comment_id, $order)
{
    $sendEmail = $order->get_meta('send_email');
    $emailStatus = get_option('wc_shipment_email_status');
    if (empty($emailStatus)) {
        return;
    }

    if (($order->get_status() === 'partiallyshipped' || $order->get_status() === 'completelyshipped') && $sendEmail == 1) {
        update_post_meta($order->get_id(), 'send_email', '');
        global $woocommerce;

        // Create a mailer

        $trackingLinks = '<div> ';
        $trackingUrls = $order->get_meta('tracking_urls');
        if (!empty($trackingUrls) && is_array($trackingUrls)) {
            foreach ($trackingUrls as $key => $trackingUrl) {
                // Constructing the tracking links
                $trackingLinks .= sprintf(
                        '<p style="margin: 10px auto;margin-left:-2px;text-align: center;"><img src="%s"/> %s</p>',
                        esc_url(plugin_dir_url(__FILE__) . '/package_2_FILL0_wght400_GRAD0_opsz24.png'),
                        sprintf(__('Package %s', 'Strtictly-Auto-Parts'), $key + 1)
                );
                $trackingLinks .= '<p style="text-align: center ; margin: 0 auto ; background-color: #000 ; padding: 18px 35px ; border-radius: 50px ; max-width: 140px;">';
                $trackingLinks .= sprintf(
                        '<a href="%s" style="color:#fff;font-family:Poppins,Helvetica,Arial,sans-serif;font-size:13px;font-weight:600;font-style:normal;letter-spacing:1px;line-height:20px;text-transform:uppercase;text-decoration:none;display:block" target="_blank">%s</a>',
                        esc_url($trackingUrl),
                        __('TRACK PACKAGE', 'Strtictly-Auto-Parts')
                );
                $trackingLinks .= "</p>";
            }
        }

        $trackingLinks .= '</div>';

        $mailer = $woocommerce->mailer();

        $products = [];
        foreach ($order->get_items() as $item) {
            $product = wc_get_product($item->get_product_id());
            $products[$product->get_sku()] = $item->get_name();
        }


        $productList = '<div style="text-align:center;"><p style="font-weight: 600;text-align:center;color: #000000;">' . esc_html__('CONTENTS', 'Strtictly-Auto-Parts') . '</p>';
        $shippedProducts = $order->get_meta('shipped_products');
        if (!empty($shippedProducts)) {
            $productListItems = [];
            foreach ($shippedProducts as $shippedProduct) {
                $productListItems[] = sprintf('%d X %s', $shippedProduct['qty'], $products[$shippedProduct['sku']]);
            }
            $productList .= implode('<br />', $productListItems);
        }

        $productList .= "</div>";

        $shippingAddress = array_filter($order->get_address("shipping"));
        $orderId = sprintf(__('Order #%s', 'Strtictly-Auto-Parts'), esc_html($order->get_order_number()));
        $message_body = sprintf(
                '<p style="text-align:center;font-size: 22px;font-weight: 600;color: #000000;"><b>%s</b></p>',
                $orderId
        );

        $message_body .= sprintf(
                '<p style="color: #000000; ">' .
                esc_html__('Hi %1$s,', 'Strtictly-Auto-Parts') . '</p>' .
                '<p style="color: #000000;">%2$s</p>',
                esc_html($shippingAddress['first_name']),
                esc_html(get_option('wc_shipment_email_content'))
        );

        $shippingAddress['first_name'] = ucfirst($shippingAddress['first_name'] . ' ' . $shippingAddress['last_name'] ?? '');
        unset($shippingAddress['last_name']);
        $emailShippingAddress = implode(", ", array_filter($shippingAddress));
        $tShippingAddress = esc_html__('%s', 'Strtictly-Auto-Parts');
        $translatedShippingAddress = sprintf($tShippingAddress, $emailShippingAddress);

        $message_body .= sprintf(
                '<table border="0" cellpadding="0" cellspacing="0" width="100%%">
        <tbody>
            <tr>
                <td valign="top">
                    <div id="body_content_inner" style="color: #000000;">
                        <table id="addresses" cellspacing="0" cellpadding="0" border="0" style="width: 100%%; vertical-align: top; padding: 0; color: #000000;">
                            <tbody>
                                <tr>
                                    <td style="padding: 0;">%s</td>
                                </tr> 
                                <tr>
                                    <td style="padding: 0;">
                                        <p style="margin: 0 0 16px; margin-left: -48px; margin-right: -48px; border-bottom: 2px solid #e5e5e5;">&nbsp;</p>                                    
                                    </td>
                                </tr>
                            </tbody>
                        </table> 
                    </div>
                </td>
            </tr>  
            <tr> 
                <td valign="top" style="padding: 0;">
                    <div style="text-align: center;">
                        <p style="font-weight: 600; color: #000000;">%s</p>
                        %s
                    </div>
                </td>
            </tr>
            <tr> 
                <td valign="top">%s</td>
            </tr>
            <tr>
                <td>
                    <p style="margin: 0 0 16px; margin-left: -25px; margin-right: -25px; border-bottom: 2px solid #e5e5e5;">&nbsp;</p>
                    <p>%s</p>
                </td>
            </tr>
        </tbody>
    </table>',
                $trackingLinks,
                esc_html__('SHIPPING ADDRESS', 'Strtictly-Auto-Parts'),
                $translatedShippingAddress,
                $productList,
                esc_html__('Please note if there are more packages in your order, you will get another tracking email separately.', 'Strtictly-Auto-Parts')
        );

        $emailHeader = get_option('wc_shipment_email_header');
        $emailHeaderMsg = ( empty($emailHeader) ) ? __("We have just dispatched a shipment to you!", 'Strtictly-Auto-Parts') : $emailHeader;
        $message = $mailer->wrap_message($emailHeaderMsg, $message_body);

        $emailSubject = get_option('wc_shipment_email_subject');
        if (!empty($emailSubject)) {
            if (strpos($emailSubject, '{order_number}') !== false) {
                $emailSubject = str_replace('{order_number}', $order->get_order_number(), $emailSubject);
            }
            if (strpos($emailSubject, '{customer_name}') !== false) {
                $emailSubject = str_replace('{customer_name}', $shippingAddress['first_name'], $emailSubject);
            }
        }
        $subject = ( empty($emailSubject) ) ? sprintf(__('Wohoo! Your order #%s has shipped', 'Strtictly-Auto-Parts'), $order->get_order_number()) : $emailSubject;
        $mailer->send($order->billing_email, $subject, $message);
    }
}

add_action('woocommerce_order_is_partially_refunded', 'wpsap_woocommerce_create_refund_action', 10, 3);

function wpsap_woocommerce_create_refund_action($is_partially_refunded, $order_id, $refund_id)
{
    $order = wc_get_order($order_id);
    $total_refunded_qty = [];
    foreach ($order->get_refunds() as $refund) {
        foreach ($refund->get_items() as $item_id => $item) {
            $refunded_qty = $item->get_quantity();
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $product_id_to_use = $variation_id ? $variation_id : $product_id;
            $product = wc_get_product($product_id_to_use);

            $sku = $product->get_sku();
            if (!isset($total_refunded_qty[$sku])) {
                $total_refunded_qty[$sku] = abs($refunded_qty);
            } else {
                $total_refunded_qty[$sku] = $total_refunded_qty[$sku] + abs($refunded_qty);
            }
        }
    }

    update_post_meta($order_id, "_total_refund_order_items", json_encode($total_refunded_qty));
    return $is_partially_refunded;
}

