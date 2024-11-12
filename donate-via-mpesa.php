<?php
/*
Plugin Name: Donate via Mpesa
Description: Accepts donations via Lipa Na Mpesa and logs transactions.
Version: 1.0.0
Author: Lexacle Technologies
Text Domain: donate-via-mpesa
Domain Path: /languages
License: GPL2
Plugin URI: https://www.lexacle.com/wordpress/donate-via-mpesa
Author URI: https://www.lexacle.com
*/

// Include necessary files
include_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
include_once plugin_dir_path(__FILE__) . 'includes/form-handler.php';

// Initialize the plugin
function donate_via_mpesa_init() {
    // Register settings, create database table, etc.
    global $wpdb;
    $table_name = $wpdb->prefix . 'mpesa_donations';

    // Check if the table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id INT(11) NOT NULL AUTO_INCREMENT,
            phone VARCHAR(15) NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            merchant_request_id VARCHAR(255),
            checkout_request_id VARCHAR(255),
            mpesa_receipt_number VARCHAR(255),
            response_code VARCHAR(255),
            response_description TEXT,
            customer_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status INT(1) DEFAULT 0,
            PRIMARY KEY (id)
        );";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql); // Create the table
    }
}

function donate_via_mpesa_enqueue_styles() {
    wp_enqueue_style('tailwind-css', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');
}
add_action('admin_enqueue_scripts', 'donate_via_mpesa_enqueue_styles');
add_action('wp_enqueue_scripts', 'donate_via_mpesa_enqueue_styles');


function donate_via_mpesa_handle_callback($request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mpesa_donations'; // Your database table name
    
    // Get JSON data from the request body
    $data = json_decode($request->get_body(), true);

    // Check if 'stkCallback' and necessary fields are in the payload
    if (isset($data['Body']['stkCallback'])) {
        $callbackData = $data['Body']['stkCallback'];

        $merchant_request_id = sanitize_text_field($callbackData['MerchantRequestID']);
        $checkout_request_id = sanitize_text_field($callbackData['CheckoutRequestID']);
        $result_code = sanitize_text_field($callbackData['ResultCode']);

        // Check if ResultCode is 0 (successful transaction)
        if ($result_code == 0) {
            
            $transaction_amount = sanitize_text_field($callbackData['CallbackMetadata']['Item'][0]['Value']);
            $mpesa_receipt_number = sanitize_text_field($callbackData['CallbackMetadata']['Item'][1]['Value']);

            // Check if a record with the given merchantRequestID and checkoutRequestID exists
            $record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE merchant_request_id = %s AND checkout_request_id = %s",
                $merchant_request_id,
                $checkout_request_id
            ));

            if ($record) {
                // Update the status and store the MpesaReceiptNumber
                $updated = $wpdb->update(
                    $table_name,
                    [
                        'status' => 1, // Success status
                        'amount' => $transaction_amount, // Store amount from callback payload
                        'mpesa_receipt_number' => $mpesa_receipt_number // Store the receipt number
                    ],
                    [
                        'merchant_request_id' => $merchant_request_id,
                        'checkout_request_id' => $checkout_request_id
                    ]
                );

                // Check if the update was successful
                if ($updated !== false) {
                    $callback_url = get_option('donate_via_mpesa_callback_url');  // Assuming you've saved this in the options table or elsewhere

                    if (!empty($callback_url)) {
                        // Send the data to the callback URL via a POST request
                        $response = wp_remote_post($callback_url, [
                            'method' => 'POST',
                            'body' => json_encode($callbackData),
                            'headers' => [
                                'Content-Type' => 'application/json',
                            ],
                        ]);

                        // Check if the request was successful
                        if (is_wp_error($response)) {
                            error_log('Error sending callback: ' . $response->get_error_message());
                        } else {
                            error_log("Callback sent successfully to: $callback_url");
                        }
                    } else {
                        error_log('Callback URL is not set in the settings.');
                    }
                    return new WP_REST_Response(['success' => 'Transaction updated successfully'], 200);
                } else {
                    return new WP_REST_Response(['error' => 'Failed to update transaction'], 500);
                }
            } else {
                // No matching record found
                return new WP_REST_Response(['error' => 'No matching record found'], 404);
            }
        }
    }

    return new WP_REST_Response(['error' => 'Invalid payload'], 400);
}




add_action('rest_api_init', function () {
    register_rest_route('dvm-signal/v1', '/callback', array(
        'methods' => 'POST',
        'callback' => 'donate_via_mpesa_handle_callback',
        'permission_callback' => '__return_true', // Allow public access
    ));
});


register_activation_hook(__FILE__, 'donate_via_mpesa_init');
