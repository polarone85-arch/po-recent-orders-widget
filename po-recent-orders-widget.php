<?php
/**
 * Plugin Name: PO Recent Orders Dashboard Widget
 * Description: Displays recent WooCommerce orders with custom order numbers in the WordPress dashboard.
 * Version: 1.0.0
 * Author: PolarOne
 * Text Domain: po-recent-orders-widget
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_dashboard_setup', 'po_add_recent_orders_dashboard_widget');

function po_add_recent_orders_dashboard_widget() {
    if (!current_user_can('manage_woocommerce') && !current_user_can('administrator')) {
        return;
    }

    wp_add_dashboard_widget(
        'po_recent_orders_widget',
        __('Recent WooCommerce Orders', 'po-recent-orders-widget'),
        'po_render_recent_orders_dashboard_widget'
    );

    global $wp_meta_boxes;

    if (isset($wp_meta_boxes['dashboard']['normal']['core']['po_recent_orders_widget'])) {
        $normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
        $widget_backup = array('po_recent_orders_widget' => $normal_dashboard['po_recent_orders_widget']);
        unset($normal_dashboard['po_recent_orders_widget']);

        $wp_meta_boxes['dashboard']['normal']['core'] = array_merge($widget_backup, $normal_dashboard);
    }
}

function po_render_recent_orders_dashboard_widget() {
    if (!class_exists('WooCommerce')) {
        echo '<p>' . esc_html__('WooCommerce is not active.', 'po-recent-orders-widget') . '</p>';
        return;
    }

    $orders = wc_get_orders([
        'limit'   => 15,
        'orderby' => 'date',
        'order'   => 'DESC',
    ]);

    if (empty($orders)) {
        echo '<p>' . esc_html__('No orders found.', 'po-recent-orders-widget') . '</p>';
        return;
    }

    echo '<table style="width:100%; border-collapse:collapse; text-align:left;">';
    echo '<thead>
            <tr style="border-bottom:1px solid #ccc;">
                <th style="padding:6px 0;">' . esc_html__('Order', 'po-recent-orders-widget') . '</th>
                <th style="padding:6px 0; width:45px;">' . esc_html__('Date', 'po-recent-orders-widget') . '</th>
                <th style="padding:6px 0;">' . esc_html__('Customer', 'po-recent-orders-widget') . '</th>
                <th style="padding:6px 0;">' . esc_html__('Status', 'po-recent-orders-widget') . '</th>
                <th style="padding:6px 0;">' . esc_html__('Total', 'po-recent-orders-widget') . '</th>
            </tr>
          </thead>
          <tbody>';

    foreach ($orders as $order) {
        $order_id   = $order->get_id();
        $edit_url   = $order->get_edit_order_url();
        $custom_num = $order->get_meta('_unique_order_number');
        $display_id = !empty($custom_num) ? $custom_num : $order->get_order_number();

        $date_created = $order->get_date_created();
        $date_formatted = $date_created ? $date_created->date('d/m') : '';

        $customer = trim($order->get_formatted_billing_full_name());
        if (empty($customer)) {
            $customer = __('Guest', 'po-recent-orders-widget');
        }

        echo '<tr style="border-bottom:1px solid #eee;">';
        echo '<td style="padding:6px 0;"><a href="' . esc_url($edit_url) . '">#' . esc_html($display_id) . '</a></td>';
        echo '<td style="padding:6px 0; width:45px;">' . esc_html($date_formatted) . '</td>';
        echo '<td style="padding:6px 0;">' . esc_html($customer) . '</td>';
        echo '<td style="padding:6px 0;">' . esc_html(wc_get_order_status_name($order->get_status())) . '</td>';
        echo '<td style="padding:6px 0;">' . wp_kses_post($order->get_formatted_order_total()) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}