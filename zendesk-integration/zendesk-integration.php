<?php
/**
 * Plugin Name: WooCommerce to Zendesk Integration
 * Description: Creates Zendesk tickets automatically when new WooCommerce orders are placed
 * Version: 1.0
 * Author: Ashish
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add settings page to WooCommerce
add_filter('woocommerce_settings_tabs_array', 'add_zendesk_settings_tab', 50);
function add_zendesk_settings_tab($settings_tabs) {
    $settings_tabs['zendesk_integration'] = __('Zendesk Integration', 'woocommerce');
    return $settings_tabs;
}

// Add settings to the Zendesk tab
add_action('woocommerce_settings_tabs_zendesk_integration', 'zendesk_integration_settings');
function zendesk_integration_settings() {
    woocommerce_admin_fields(get_zendesk_integration_settings());
}

// Save settings
add_action('woocommerce_update_options_zendesk_integration', 'update_zendesk_integration_settings');
function update_zendesk_integration_settings() {
    woocommerce_update_options(get_zendesk_integration_settings());
}

// Define the settings
function get_zendesk_integration_settings() {
    $settings = array(
        'section_title' => array(
            'name'     => __('Zendesk Integration Settings', 'woocommerce'),
            'type'     => 'title',
            'desc'     => __('Enter your Zendesk API credentials and configure ticket creation settings.', 'woocommerce'),
            'id'       => 'wc_zendesk_integration_section_title'
        ),
        'enabled' => array(
            'name'     => __('Enable Integration', 'woocommerce'),
            'type'     => 'checkbox',
            'desc'     => __('Enable Zendesk ticket creation for new orders', 'woocommerce'),
            'id'       => 'wc_zendesk_integration_enabled'
        ),
        'domain' => array(
            'name'     => __('Zendesk Domain', 'woocommerce'),
            'type'     => 'text',
            'desc'     => __('Your Zendesk domain (e.g., yourcompany.zendesk.com)', 'woocommerce'),
            'id'       => 'wc_zendesk_integration_domain',
            'placeholder' => 'yourcompany.zendesk.com'
        ),
        'email' => array(
            'name'     => __('Zendesk Email', 'woocommerce'),
            'type'     => 'email',
            'desc'     => __('Email address associated with your Zendesk account', 'woocommerce'),
            'id'       => 'wc_zendesk_integration_email',
            'placeholder' => 'your-email@example.com'
        ),
        'api_token' => array(
            'name'     => __('API Token', 'woocommerce'),
            'type'     => 'password',
            'desc'     => __('Your Zendesk API token (generate this in Zendesk Admin > Channels > API)', 'woocommerce'),
            'id'       => 'wc_zendesk_integration_api_token',
            'placeholder' => 'Your API Token'
        ),
        'ticket_subject' => array(
            'name'     => __('Ticket Subject', 'woocommerce'),
            'type'     => 'text',
            'desc'     => __('Subject line for created tickets. Use {order_id} as placeholder for order number.', 'woocommerce'),
            'id'       => 'wc_zendesk_integration_ticket_subject',
            'default'  => 'New Order #{order_id}',
            'placeholder' => 'New Order #{order_id}'
        ),
        'ticket_priority' => array(
            'name'     => __('Ticket Priority', 'woocommerce'),
            'type'     => 'select',
            'desc'     => __('Default priority for created tickets', 'woocommerce'),
            'id'       => 'wc_zendesk_integration_ticket_priority',
            'options'  => array(
                'low'    => __('Low', 'woocommerce'),
                'normal' => __('Normal', 'woocommerce'),
                'high'   => __('High', 'woocommerce'),
                'urgent' => __('Urgent', 'woocommerce')
            ),
            'default'  => 'normal'
        ),
        'section_end' => array(
            'type'     => 'sectionend',
            'id'       => 'wc_zendesk_integration_section_end'
        )
    );
    
    return $settings;
}

// Hook into WooCommerce order creation
add_action('woocommerce_checkout_order_processed', 'create_zendesk_ticket_for_order', 10, 1);
add_action('woocommerce_new_order', 'create_zendesk_ticket_for_order', 10, 1);

// Function to create Zendesk ticket
function create_zendesk_ticket_for_order($order_id) {
    // Check if integration is enabled
    if (get_option('wc_zendesk_integration_enabled') !== 'yes') {
        return;
    }
    
    // Check if we already created a ticket for this order
    if (get_post_meta($order_id, '_zendesk_ticket_created', true) === 'yes') {
        return;
    }
    
    // Get order details
    $order = wc_get_order($order_id);
    if (!$order) {
        error_log("WooCommerce to Zendesk: Unable to find order #$order_id");
        return;
    }
    
    // Get customer details
    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    $customer_email = $order->get_billing_email();
    $customer_phone = $order->get_billing_phone();
    
    // Get order items
    $order_items = $order->get_items();
    $items_description = "Order Items:\n";
    foreach ($order_items as $item) {
        $product_name = $item->get_name();
        $quantity = $item->get_quantity();
        $price = $order->get_currency() . ' ' . $item->get_total();
        $items_description .= "- $product_name x $quantity ($price)\n";
    }
    
    // Prepare shipping address
    $shipping_address = $order->get_formatted_shipping_address();
    if (empty($shipping_address)) {
        $shipping_address = "Same as billing address";
    }
    
    // Build ticket description
    $ticket_description = "New Order Details:\n\n";
    $ticket_description .= "Order Number: #$order_id\n";
    $ticket_description .= "Order Date: " . $order->get_date_created()->date_i18n(get_option('date_format') . ' ' . get_option('time_format')) . "\n";
    $ticket_description .= "Order Status: " . $order->get_status() . "\n\n";
    $ticket_description .= "$items_description\n";
    $ticket_description .= "Order Total: " . $order->get_currency() . ' ' . $order->get_total() . "\n\n";
    $ticket_description .= "Customer Details:\n";
    $ticket_description .= "Name: $customer_name\n";
    $ticket_description .= "Email: $customer_email\n";
    $ticket_description .= "Phone: $customer_phone\n\n";
    $ticket_description .= "Billing Address:\n" . $order->get_formatted_billing_address() . "\n\n";
    $ticket_description .= "Shipping Address:\n$shipping_address\n\n";
    $ticket_description .= "Payment Method: " . $order->get_payment_method_title() . "\n";
    $ticket_description .= "Customer Notes: " . $order->get_customer_note() . "\n";
    
    // Get Zendesk settings
    $zendesk_domain = get_option('wc_zendesk_integration_domain');
    $zendesk_email = get_option('wc_zendesk_integration_email');
    $zendesk_api_token = get_option('wc_zendesk_integration_api_token');
    $ticket_priority = get_option('wc_zendesk_integration_ticket_priority', 'normal');
    
    // Parse subject template
    $subject_template = get_option('wc_zendesk_integration_ticket_subject', 'New Order #{order_id}');
    $ticket_subject = str_replace('{order_id}', $order_id, $subject_template);
    
    // Create ticket data
    $data = array(
        'ticket' => array(
            'requester' => array(
                'name' => $customer_name,
                'email' => $customer_email
            ),
            'subject' => $ticket_subject,
            'comment' => array(
                'body' => $ticket_description
            ),
            'priority' => $ticket_priority,
            'tags' => array('woocommerce', 'new_order')
        )
    );
    
    // Send API request to Zendesk
    $response = send_zendesk_api_request($zendesk_domain, $zendesk_email, $zendesk_api_token, $data);
    
    if ($response) {
        // Mark order as having a ticket
        update_post_meta($order_id, '_zendesk_ticket_created', 'yes');
        
        // Get ticket ID from response
        $zendesk_response = json_decode($response, true);
        if (isset($zendesk_response['ticket']['id'])) {
            $ticket_id = $zendesk_response['ticket']['id'];
            update_post_meta($order_id, '_zendesk_ticket_id', $ticket_id);
            
            // Add order note
            $order->add_order_note("Zendesk ticket #$ticket_id created for this order.");
        }
    }
}

// Function to handle Zendesk API request
function send_zendesk_api_request($domain, $email, $api_token, $data) {
    if (empty($domain) || empty($email) || empty($api_token)) {
        error_log('WooCommerce to Zendesk: Missing API credentials');
        return false;
    }
    
    $payload = json_encode($data);
    
    $args = array(
        'method'      => 'POST',
        'timeout'     => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking'    => true,
        'headers'     => array(
            'Content-Type'  => 'application/json',
            'Content-Length' => strlen($payload)
        ),
        'body'        => $payload,
        'cookies'     => array(),
        'sslverify'   => false,
        'user-agent'  => 'WooCommerce-Zendesk-Integration/1.0'
    );
    
    // Add authentication
    $auth = base64_encode("$email/token:$api_token");
    $args['headers']['Authorization'] = "Basic $auth";
    
    // Send request
    $response = wp_remote_post("https://$domain/api/v2/tickets.json", $args);
    
    // Check for errors
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        error_log("WooCommerce to Zendesk: API Error - $error_message");
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code < 200 || $response_code >= 300) {
        error_log("WooCommerce to Zendesk: API Error - HTTP $response_code - $response_body");
        return false;
    }
    
    return $response_body;
}

// Add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'zendesk_integration_settings_link');
function zendesk_integration_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=zendesk_integration') . '">'.__('Settings').'</a>';
    array_unshift($links, $settings_link);
    return $links;
}