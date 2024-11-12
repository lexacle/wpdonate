<?php
function donate_via_mpesa_menu()
{
    // Add the Donate via Mpesa parent menu (this will be the top-level menu)
    add_menu_page(
        'Donate via Mpesa Settings',  // Page title
        'Donate via Mpesa',  // Menu title
        'manage_options',  // Capability required to access
        'donate_via_mpesa',  // Slug for this menu
        'donate_via_mpesa_settings_page',  // Callback function to display the settings page
        'dashicons-money',  // Icon for the menu
        66  // Position of the menu item
    );

    // Add the first submenu (renaming "Donate via Mpesa" to "Settings")
    add_submenu_page(
        'donate_via_mpesa',  // Parent slug (same as the menu)
        'Settings',  // Page title for the settings page
        'Settings',  // Label in the submenu
        'manage_options',  // Capability required to access
        'donate_via_mpesa',  // Slug (same as parent menu)
        'donate_via_mpesa_settings_page'  // Callback function for settings page
    );

    // Add the second submenu for Transactions
    add_submenu_page(
        'donate_via_mpesa',  // Parent slug (same as the menu)
        'Transactions',  // Page title for the transactions page
        'Transactions',  // Label in the submenu
        'manage_options',  // Capability required to access
        'donate_via_mpesa_transactions',  // Slug for transactions page
        'donate_via_mpesa_transactions_page'  // Callback function for transactions page
    );
}

add_action('admin_menu', 'donate_via_mpesa_menu');

// Settings Page Form
function donate_via_mpesa_settings_page()
{
    ?>
    <div class="w-full p-4 sm:p-6">
        
        <div class="container">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
            <div class="bg-gray-200 rounded p-4">
            <h1 class="font-bold text-green-500 text-xl sm:text-2xl">Donate via Mpesa Settings</h1>
            <form method="post" action="options.php">
            <?php
            settings_fields('donate_via_mpesa_options_group');  // Group name defined below
            do_settings_sections('donate_via_mpesa');
            submit_button();
            ?>
            </form>
            </div>
            <div class="flex flex-col gap-4">
                <div class="bg-gray-200 rounded p-4 flex flex-col gap-2">
                <h3 class="flex flex-row justify-between"><span>Shortcode</span><button onclick="copyShortcode()" id="copy-text">Click to copy</button></h3>
                <div class="bg-black text-white p-5 rounded flex items-center justify-between">
                <span id="shortcode-text">[donate_via_mpesa]</span>
                <button onclick="copyShortcode()" class="ml-3">
                <svg id="copy-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24">
                    <path d="M17.5 14H19C20.1046 14 21 13.1046 21 12V5C21 3.89543 20.1046 3 19 3H12C10.8954 3 10 3.89543 10 5V6.5M5 10H12C13.1046 10 14 10.8954 14 12V19C14 20.1046 13.1046 21 12 21H5C3.89543 21 3 20.1046 3 19V12C3 10.8954 3.89543 10 5 10Z" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <!-- Success checkmark icon, initially hidden -->
                <svg id="check-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                </button>
                </div>
                </div>
                <div class="h-full bg-gray-200 rounded p-4 flex flex-col gap-2">
                <h3>Setup Instructions</h3>
            </div>
            </div>
            </div>
        </div>
        
    </div>

    <script>
function copyShortcode() {
    const shortcodeText = document.getElementById("shortcode-text").innerText;

// Use the Clipboard API to copy the text
navigator.clipboard.writeText(shortcodeText).then(function() {
    // Show success checkmark icon
    document.getElementById("copy-icon").classList.add("hidden");
    document.getElementById("check-icon").classList.remove("hidden");
    document.getElementById("copy-text").innerText = "Copied shortcode"
    // Revert back to copy icon after 5 seconds
    setTimeout(function() {
        document.getElementById("copy-icon").classList.remove("hidden");
        document.getElementById("check-icon").classList.add("hidden");
        document.getElementById("copy-text").innerText = "Click to copy"
    }, 2000);
}).catch(function(error) {
    console.error("Copy failed", error);
    alert("Failed to copy shortcode.");
});
}
</script>
    <?php
}

// Register Settings Fields
function donate_via_mpesa_register_settings()
{
    register_setting('donate_via_mpesa_options_group', 'donate_via_mpesa_mode');  // Mode (sandbox/production)
    register_setting('donate_via_mpesa_options_group', 'donate_via_mpesa_callback_url');  // Callback URL
    register_setting('donate_via_mpesa_options_group', 'donate_via_mpesa_consumer_key');  // Consumer Key
    register_setting('donate_via_mpesa_options_group', 'donate_via_mpesa_consumer_secret');  // Consumer Secret
    register_setting('donate_via_mpesa_options_group', 'donate_via_mpesa_short_code');  // Shortcode
    register_setting('donate_via_mpesa_options_group', 'donate_via_mpesa_pass_key');  // Passkey

    add_settings_section('donate_via_mpesa_section', 'Lipa Na Mpesa Account Setup and Configuration', null, 'donate_via_mpesa');

    // Mode Field (Sandbox/Production)
    add_settings_field(
        'donate_via_mpesa_mode',
        'Mode',
        'donate_via_mpesa_mode_callback',
        'donate_via_mpesa',
        'donate_via_mpesa_section'
    );

    // Shortcode Key Field
    add_settings_field(
        'donate_via_mpesa_short_code',
        'Shortcode',
        'donate_via_mpesa_short_code_callback',
        'donate_via_mpesa',
        'donate_via_mpesa_section'
    );

    // Consumer Key Field
    add_settings_field(
        'donate_via_mpesa_consumer_key',
        'Consumer Key',
        'donate_via_mpesa_consumer_key_callback',
        'donate_via_mpesa',
        'donate_via_mpesa_section'
    );

    // Consumer Secret Field
    add_settings_field(
        'donate_via_mpesa_consumer_secret',
        'Consumer Secret',
        'donate_via_mpesa_consumer_secret_callback',
        'donate_via_mpesa',
        'donate_via_mpesa_section'
    );

    // Password Field
    add_settings_field(
        'donate_via_mpesa_pass_key',
        'Passkey',
        'donate_via_mpesa_pass_key_callback',
        'donate_via_mpesa',
        'donate_via_mpesa_section'
    );

    // Callback URL Field
    add_settings_field(
        'donate_via_mpesa_callback_url',
        'Callback URL',
        'donate_via_mpesa_callback_url_callback',
        'donate_via_mpesa',
        'donate_via_mpesa_section'
    );
}

add_action('admin_init', 'donate_via_mpesa_register_settings');

// Callback Functions to Render Fields

function donate_via_mpesa_callback_url_callback()
{
    $value = get_option('donate_via_mpesa_callback_url');
    echo '<input type="text" name="donate_via_mpesa_callback_url" value="' . esc_attr($value) . '" class="regular-text" />';
}

function donate_via_mpesa_short_code_callback()
{
    $value = get_option('donate_via_mpesa_short_code');
    echo '<input type="text" name="donate_via_mpesa_short_code" value="' . esc_attr($value) . '" class="regular-text" />';
}

function donate_via_mpesa_pass_key_callback()
{
    $value = get_option('donate_via_mpesa_pass_key');
    echo '<input type="password" name="donate_via_mpesa_pass_key" value="' . esc_attr($value) . '" class="regular-text" />';
}

function donate_via_mpesa_consumer_key_callback()
{
    $value = get_option('donate_via_mpesa_consumer_key');
    echo '<input type="password" name="donate_via_mpesa_consumer_key" value="' . esc_attr($value) . '" class="regular-text" />';
}

function donate_via_mpesa_consumer_secret_callback()
{
    $value = get_option('donate_via_mpesa_consumer_secret');
    echo '<input type="password" name="donate_via_mpesa_consumer_secret" value="' . esc_attr($value) . '" class="regular-text" />';
}

function donate_via_mpesa_mode_callback()
{
    $value = get_option('donate_via_mpesa_mode');
    ?>
    <select name="donate_via_mpesa_mode" class="regular-text">
        <option value="sandbox" <?php selected($value, 'sandbox'); ?>>Sandbox</option>
        <option value="production" <?php selected($value, 'production'); ?>>Production</option>
    </select>
    <?php
}

function donate_via_mpesa_enqueue_datatables()
{
    // Enqueue DataTables CSS
    wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css');

    // Enqueue DataTables JS (ensure jQuery is already loaded)
    wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/2.1.8/js/dataTables.min.js', array('jquery'), null, true);
}

add_action('admin_enqueue_scripts', 'donate_via_mpesa_enqueue_datatables');

function donate_via_mpesa_transactions_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'mpesa_donations';

    $transactions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");
    ?>
    <div class="wrap">
        <h1 class="font-bold">Mpesa Donations</h1>
        <table class="wp-list-table widefat striped" id="donations_table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Phone Number</th>
                    <th>Amount</th>
                    <th>Transaction ID</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?php echo esc_html($transaction->id); ?></td>
                        <td><?php echo esc_html($transaction->phone); ?></td>
                        <td>KES. <?php echo esc_html($transaction->amount); ?></td>
                        <td><?php echo esc_html($transaction->mpesa_receipt_number?$transaction->mpesa_receipt_number:'N/A'); ?></td>
                        <td><?php echo esc_html(date('d M Y h:i a', strtotime($transaction->created_at))); ?></td>
                        <td>
                            <?php
                            if ($transaction->status === '1') {
                                // Green checkmark for success
                                echo '<svg class="inline w-5 h-5 text-green-500 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <circle cx="12" cy="12" r="10" fill="currentColor" />
                                        <path d="M10 14.6l-2.3-2.3a1 1 0 10-1.4 1.4l3 3a1 1 0 001.4 0l7-7a1 1 0 00-1.4-1.4L10 14.6z" fill="#fff"/>
                                      </svg> <span>Complete</span>';
                            } elseif ($transaction->status === '0') {
                                // Info circle for status 0
                                echo '<svg class="inline w-5 h-5 text-blue-500 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <circle cx="12" cy="12" r="10" fill="currentColor" />
                                        <path d="M12 9c-.552 0-1 .448-1 1v6c0 .552.448 1 1 1s1-.448 1-1V10c0-.552-.448-1-1-1zM12 7c-.552 0-1 .448-1 1s.448 1 1 1 1-.448 1-1-.448-1-1-1z" fill="#fff"/>
                                      </svg> <span>Pending</span>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#donations_table').DataTable({
                "paging": true,
                "searching": true,
                "ordering": false,
                "info": true,
                "responsive": true
            });
        });
    </script>
    <?php
}
