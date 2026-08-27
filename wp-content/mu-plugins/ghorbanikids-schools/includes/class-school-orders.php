<?php
/**
 * Class GK_School_Orders
 * Handles WooCommerce Order Hooks for B2B School Subscription Fulfillment
 */
if (!defined('ABSPATH')) exit;

class GK_School_Orders {

    public static function init() {
        add_action('woocommerce_order_status_completed', [__CLASS__, 'fulfill_school_order']);
        add_action('woocommerce_order_status_processing', [__CLASS__, 'fulfill_school_order']);
    }

    public static function fulfill_school_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        // Check if already processed
        if ($order->get_meta('_gk_school_processed')) {
            return;
        }

        $user_id = $order->get_customer_id();
        if (!$user_id) {
            // Guest checkout: try to find user by email or create account
            $email = $order->get_billing_email();
            $user = get_user_by('email', $email);
            $user_id = $user ? $user->ID : 0;
        }

        if (!$user_id) return;

        global $wpdb;
        $table_orgs = $wpdb->prefix . 'gk_organizations';

        $total_capacity_added = 0;

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $capacity = intval(get_post_meta($product_id, '_gk_school_plan_capacity', true));
            if ($capacity > 0) {
                $qty = $item->get_quantity();
                $total_capacity_added += ($capacity * $qty);
            }
        }

        if ($total_capacity_added > 0) {
            $existing_org = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_orgs WHERE manager_user_id = %d", $user_id));

            $billing_first_name = $order->get_billing_first_name();
            $billing_last_name  = $order->get_billing_last_name();
            $billing_phone      = $order->get_billing_phone();
            $billing_city       = $order->get_billing_city();
            $default_name       = 'مهدکودک ' . ($billing_last_name ?: $billing_first_name ?: 'من');

            if ($existing_org) {
                $new_limit = intval($existing_org->student_limit) + $total_capacity_added;
                $current_exp = strtotime($existing_org->expires_at);
                $new_exp = ($current_exp && $current_exp > time()) ? ($current_exp + (365 * 86400)) : (time() + (365 * 86400));

                $wpdb->update($table_orgs, [
                    'student_limit' => $new_limit,
                    'expires_at'    => date('Y-m-d H:i:s', $new_exp),
                    'status'        => 'active'
                ], ['id' => $existing_org->id], ['%d', '%s', '%s'], ['%d']);
            } else {
                $wpdb->insert($table_orgs, [
                    'name'            => $default_name,
                    'phone'           => $billing_phone,
                    'city'            => $billing_city,
                    'manager_user_id' => $user_id,
                    'student_limit'   => $total_capacity_added,
                    'expires_at'      => date('Y-m-d H:i:s', time() + (365 * 86400)),
                    'status'          => 'active'
                ], ['%s', '%s', '%s', '%d', '%d', '%s', '%s']);
            }

            // Grant school manager meta
            update_user_meta($user_id, '_gk_is_school_admin', 1);

            // Mark order as fulfilled
            $order->update_meta_data('_gk_school_processed', 1);
            $order->save();
        }
    }
}