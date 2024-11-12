<?php
function donate_via_mpesa_form()
{
    $response_message = '';
    if (isset($_POST['submit_donation'])) {
        // Retrieve settings and initiate the API request
        // Log transaction in the `donate_via_mpesa` table
        $phone = sanitize_text_field($_POST['phone_number']);
        $amount = sanitize_text_field($_POST['donation_amount']);

        if (substr($phone, 0, 1) === '0') {
            $phone = '254' . substr($phone, 1);  // Replace leading '0' with '254'
        } elseif (substr($phone, 0, 3) !== '254') {
            $phone = '254' . $phone;  // Prefix with '254' if not already present
        }

        $response = process_donation_request($phone, $amount);

        // Handle the response (for now, redirect or display a message)
        if ($response['status'] == 200) {
            if ($response['response']['errorMessage']) {
                $response_message = '<p class="text-red-500">
            <svg class="inline w-5 h-5 text-red-500 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
        <circle cx="12" cy="12" r="10" fill="currentColor" />
        <path d="M12 7a1 1 0 011 1v5a1 1 0 01-2 0V8a1 1 0 011-1zm0 9a1 1 0 110 2 1 1 0 010-2z" fill="#fff"/>
    </svg>
            Request Failed! ' . esc_html($response['response']['errorMessage']) . '</p>';
            } else {
                $response_message = '<p class="text-green-600">
                <svg class="inline w-5 h-5 text-green-500 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
        <circle cx="12" cy="12" r="10" fill="currentColor" />
        <path d="M10 14.6l-2.3-2.3a1 1 0 10-1.4 1.4l3 3a1 1 0 001.4 0l7-7a1 1 0 00-1.4-1.4L10 14.6z" fill="#fff"/>
    </svg>
                Request successful. Please check your phone to complete the transaction.</p>';
                save_payment_response($phone, $amount, $response['response']);
            }
        } else {
            $response_message = '<p class="text-blue-500">
            <svg class="inline w-5 h-5 text-blue-500 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
        <circle cx="12" cy="12" r="10" fill="currentColor" />
        <path d="M12 8.5a1 1 0 100-2 1 1 0 000 2zm1 9h-2v-7h2v7z" fill="#fff"/>
    </svg>
            ' . esc_html($response['response']['ResponseDescription']) . '</p>';
        }
    }
    ob_start();
    ?>
    <form method="post" action="" class="flex flex-col gap-4 border border-gray-200 p-4 w-full">
    <div>
        <p class="italic text-xs text-gray-400">Mpesa Donations</p></div>
        <div class="w-full flex flex-col sm:flex-row items-end items-end justify-between gap-4">
        <div>
        <label for="phone_number">Phone Number</label>
        <input type="text" name="phone_number" id="phone_number" minlength="10" maxlength="10" pattern="\d{10}"  oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);"  class="w-full px-4 py-2 border border-gray-400 focus:outline-none" placeholder="0701234567" required />
        </div>
        <div>
        <label for="amount">Amount</label>
        <input type="number" name="donation_amount" id="amount" class="w-full px-4 py-2 border border-gray-400 focus:outline-none" required>
        </div>
        <div>
        <button type="submit" name="submit_donation" class="cursor-pointer w-full transition-colors duration-300 bg-gray-800 hover:bg-green-600 text-white px-4 py-2">Donate</button>
        </div>
        </div>
        <div class="text-sm">
        <?php echo ($response_message); ?>
        </div>
        <div><p class="italic text-xs text-gray-400 text-right">Powered by <a href="https://wpfoss.com" target="_blank" class="text-blue-600 focus:outline-none">WP FOSS</a></p></div>
    </form>
    <?php
    return ob_get_clean();
}

add_shortcode('donate_via_mpesa', 'donate_via_mpesa_form');

function process_donation_request($phone, $amount)
{
    // Fetch parameters
    $mode = get_option('donate_via_mpesa_mode');
    $shortCode = get_option('donate_via_mpesa_short_code');  // Retrieved from options table
    $passKey = get_option('donate_via_mpesa_pass_key');
    $callbackURL = get_option('donate_via_mpesa_callback_url');
    $consumerKey = get_option('donate_via_mpesa_consumer_key');
    $consumerSecret = get_option('donate_via_mpesa_consumer_secret');
    $currentTime = date('YmdHis');
    $password = base64_encode($shortCode . $passKey . $currentTime);
    $basicAuth = base64_encode($consumerKey . ':' . $consumerSecret);
    $webhook_url = home_url('/wp-json/dvm-signal/v1/callback');

    // Get the access token
    $authResponse = get_access_token($basicAuth, $mode);
    if ($authResponse === false || !isset($authResponse['access_token'])) {
        return ['status' => 500, 'ResponseDescription' => 'Error Access token not found.'];
    }

    // Create payment data
    $paymentData = [
        'BusinessShortCode' => $shortCode,
        'Password' => $password,
        'Timestamp' => $currentTime,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => intval($amount),
        'PartyA' => $phone,
        'PartyB' => $shortCode,
        'PhoneNumber' => $phone,
        'CallBackURL' => $webhook_url,
        'AccountReference' => $phone,
        'TransactionDesc' => 'Donate via Mpesa'
    ];

    // Make the STK Push request
    $paymentResponse = make_stk_push_request($authResponse['access_token'], $paymentData, $mode);
    if ($paymentResponse === false) {
        return ['status' => 500, 'message' => 'Error: Payment request failed.'];
    }

    // Save response to database
    // save_payment_response($phone, $amount, $paymentResponse);

    return ['status' => 200, 'response' => $paymentResponse];
}

// Function to get access token from Mpesa API
function get_access_token($basicAuth, $mode)
{
    if ($mode === 'production') {
        $url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    } else {
        $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    }
    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Basic ' . $basicAuth
        ]
    ]);
    if (is_wp_error($response)) {
        return false;
    }

    return json_decode(wp_remote_retrieve_body($response), true);
}

// Function to make the STK Push request
function make_stk_push_request($access_token, $paymentData, $mode)
{
    if ($mode === 'production') {
        $url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    } else {
        $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    }

    $response = wp_remote_post($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode($paymentData)
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    return json_decode(wp_remote_retrieve_body($response), true);
}

// Function to save payment response data to the WordPress database
function save_payment_response($phone, $amount, $paymentResponse)
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'mpesa_donations';  // Create a table to store donations if needed
    $wpdb->insert($table_name, [
        'phone' => $phone,
        'amount' => $amount,
        'merchant_request_id' => $paymentResponse['MerchantRequestID'],
        'checkout_request_id' => $paymentResponse['CheckoutRequestID'],
        'response_code' => $paymentResponse['ResponseCode'],
        'response_description' => $paymentResponse['ResponseDescription'],
        'customer_message' => $paymentResponse['CustomerMessage'],
        'status' => 0
    ]);
}
