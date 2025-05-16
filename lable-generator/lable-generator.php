<?php
/*
Plugin Name: EasyPost Shipping Label Generator for WooCommerce
Description: Uses the WooCommerce order ID to fetch order and shipping details (which can be edited) and then creates a shipment via the EasyPost API to generate a shipping label. It also performs basic address validation and allows you to enter parcel dimensions.
Version: 1.3.1
Author: Ashish
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Retrieve the EasyPost API key from an environment variable.
// Set this in your server configuration or in wp-config.php using putenv().
define( 'EASYPOST_API_ENDPOINT', 'https://api.easypost.com/v2/' );
define( 'EASYPOST_API_KEY', 'EZTK5b488ab102a64f7e97b36077354cdab9htPe5FLfKXfMIwyiB8asuA' );

// Define your store's from-address details.
define( 'STORE_NAME', 'Cigar Chief' );
define( 'STORE_ADDRESS_1', '9th Avenue' );
define( 'STORE_ADDRESS_2', '111 8th Ave' );
define( 'STORE_CITY', 'New York' );
define( 'STORE_STATE', 'NY' );
define( 'STORE_POSTCODE', '10011' );
define( 'STORE_COUNTRY', 'US' );

// Optionally, define your Loomis carrier account ID if you want to restrict shipments to Loomis.
// Leave empty if you don't want to restrict.
define( 'LOOMIS_CARRIER_ACCOUNT', '' );

/**
 * Helper function to make requests to the EasyPost API.
 */
function easypost_api_request( $endpoint, $payload ) {
    $url  = EASYPOST_API_ENDPOINT . $endpoint;
    $args = array(
        'method'  => 'POST',
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( EASYPOST_API_KEY . ':' ),
        ),
        'body'    => wp_json_encode( $payload ),
        'timeout' => 30,
    );
    
    $response = wp_remote_post( $url, $args );
    if ( is_wp_error( $response ) ) {
        return $response;
    }
    $code = wp_remote_retrieve_response_code( $response );
    if ( $code < 200 || $code >= 300 ) {
        return new WP_Error( 'api_error', 'EasyPost API error: ' . wp_remote_retrieve_body( $response ) );
    }
    $body = wp_remote_retrieve_body( $response );
    return json_decode( $body, true );
}

/**
 * Add an admin menu page.
 */
add_action( 'admin_menu', 'easypost_label_generator_menu' );
function easypost_label_generator_menu() {
    add_menu_page(
        'EasyPost Label Generator',
        'EasyPost Labels',
        'manage_options',
        'easypost-label-generator',
        'easypost_label_generator_page',
        'dashicons-media-document'
    );
}

/**
 * Basic address validation.
 */
function is_address_valid( $address ) {
    $required = array( 'first_name', 'last_name', 'address_1', 'city', 'state', 'postcode', 'country' );
    foreach ( $required as $field ) {
        if ( empty( $address[ $field ] ) ) {
            return "The field '{$field}' is required.";
        }
    }
    return true;
}

/**
 * Main admin page handler.
 */
function easypost_label_generator_page() {
    ?>
    <div class="wrap">
        <h1>EasyPost Shipping Label Generator</h1>
        <?php
        // Determine current step based on submitted form.
        $step = isset( $_POST['step'] ) ? sanitize_text_field( $_POST['step'] ) : '';

        // Step 1: Search for an Order by ID.
        if ( empty( $step ) || $step === 'search' ) {
            ?>
            <form method="post">
                <?php wp_nonce_field( 'easypost_label_generator_action', 'easypost_label_generator_nonce' ); ?>
                <input type="hidden" name="step" value="search" />
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">WooCommerce Order ID</th>
                        <td><input type="number" name="order_id" required /></td>
                    </tr>
                </table>
                <?php submit_button( 'Fetch Shipping Info' ); ?>
            </form>
            <?php

            // Process submitted order ID.
            if ( isset( $_POST['order_id'] ) && check_admin_referer( 'easypost_label_generator_action', 'easypost_label_generator_nonce' ) ) {
                $order_id = absint( $_POST['order_id'] );
                $order    = wc_get_order( $order_id );
                if ( ! $order ) {
                    echo '<div class="error"><p>Order not found.</p></div>';
                } else {
                    // Display basic order details.
                    echo '<hr>';
                    echo '<h2>Order Details</h2>';
                    echo '<p><strong>Order Number:</strong> ' . esc_html( $order->get_order_number() ) . '</p>';
                    echo '<p><strong>Date:</strong> ' . esc_html( $order->get_date_created()->date('Y-m-d H:i:s') ) . '</p>';
                    echo '<p><strong>Total:</strong> ' . wp_kses_post( $order->get_formatted_order_total() ) . '</p>';

                    // Retrieve both billing and shipping addresses.
                    $billing_address = $order->get_address( 'billing' );
                    $shipping_address = $order->get_address( 'shipping' );
                    if ( empty( $shipping_address['address_1'] ) ) {
                        $shipping_address = $billing_address;
                    }

                    // Display the addresses in a nice two-column layout.
                    echo '<div style="display: flex; gap: 20px; margin: 20px 0;">';

                    // Billing Address Card.

                     // If billing address is missing key details, use store default.
                     if ( empty( $billing_address['address_1'] ) ) {
                        $billing_address = array(
                            'first_name' => STORE_NAME,
                            'last_name'  => '',
                            'address_1'  => STORE_ADDRESS_1,
                            'address_2'  => STORE_ADDRESS_2,
                            'city'       => STORE_CITY,
                            'state'      => STORE_STATE,
                            'postcode'   => STORE_POSTCODE,
                            'country'    => STORE_COUNTRY
                        );
                    }
                    echo '<div style="flex: 1; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9;">';
                    echo '<h3 style="margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Billing Address</h3>';
                    echo '<p>' . esc_html( $billing_address['first_name'] . ' ' . $billing_address['last_name'] ) . '</p>';
                    echo '<p>' . esc_html( $billing_address['address_1'] ) . '</p>';
                    if ( ! empty( $billing_address['address_2'] ) ) {
                        echo '<p>' . esc_html( $billing_address['address_2'] ) . '</p>';
                    }
                    echo '<p>' . esc_html( $billing_address['city'] . ', ' . $billing_address['state'] . ' ' . $billing_address['postcode'] ) . '</p>';
                    echo '<p>' . esc_html( $billing_address['country'] ) . '</p>';
                    echo '</div>';

                    // Shipping Address Card.
                    echo '<div style="flex: 1; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9;">';
                    echo '<h3 style="margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Shipping Address</h3>';
                    echo '<p>' . esc_html( $shipping_address['first_name'] . ' ' . $shipping_address['last_name'] ) . '</p>';
                    echo '<p>' . esc_html( $shipping_address['address_1'] ) . '</p>';
                    if ( ! empty( $shipping_address['address_2'] ) ) {
                        echo '<p>' . esc_html( $shipping_address['address_2'] ) . '</p>';
                    }
                    echo '<p>' . esc_html( $shipping_address['city'] . ', ' . $shipping_address['state'] . ' ' . $shipping_address['postcode'] ) . '</p>';
                    echo '<p>' . esc_html( $shipping_address['country'] ) . '</p>';
                    echo '</div>';

                    echo '</div>';

                    // Display order items if available.
                    $items = $order->get_items();
                    if ( ! empty( $items ) ) {
                        echo '<h3>Items in Order:</h3>';
                        echo '<ul>';
                        foreach ( $items as $item ) {
                            echo '<li>' . esc_html( $item->get_name() ) . ' x ' . esc_html( $item->get_quantity() ) . '</li>';
                        }
                        echo '</ul>';
                    }
                    
                    // Now display the form for editing shipping info and entering parcel dimensions.
                    echo '<hr>';
                    echo '<h2>Edit Shipping & Parcel Information</h2>';
                    ?>
                    <form method="post">
                        <?php wp_nonce_field( 'easypost_label_generator_action', 'easypost_label_generator_nonce' ); ?>
                        <input type="hidden" name="step" value="edit" />
                        <input type="hidden" name="order_id" value="<?php echo esc_attr( $order_id ); ?>" />
                        <!-- Shipping Address Fields (pre-populated with shipping address) -->
                        <table class="form-table">
                            <tr>
                                <th><label for="first_name">First Name</label></th>
                                <td><input type="text" name="first_name" id="first_name" value="<?php echo esc_attr( $shipping_address['first_name'] ); ?>" required /></td>
                            </tr>
                            <tr>
                                <th><label for="last_name">Last Name</label></th>
                                <td><input type="text" name="last_name" id="last_name" value="<?php echo esc_attr( $shipping_address['last_name'] ); ?>" required /></td>
                            </tr>
                            <tr>
                                <th><label for="address_1">Address 1</label></th>
                                <td><input type="text" name="address_1" id="address_1" value="<?php echo esc_attr( $shipping_address['address_1'] ); ?>" required /></td>
                            </tr>
                            <tr>
                                <th><label for="address_2">Address 2</label></th>
                                <td><input type="text" name="address_2" id="address_2" value="<?php echo esc_attr( $shipping_address['address_2'] ); ?>" /></td>
                            </tr>
                            <tr>
                                <th><label for="city">City</label></th>
                                <td><input type="text" name="city" id="city" value="<?php echo esc_attr( $shipping_address['city'] ); ?>" required /></td>
                            </tr>
                            <tr>
                                <th><label for="state">State</label></th>
                                <td><input type="text" name="state" id="state" value="<?php echo esc_attr( $shipping_address['state'] ); ?>" required /></td>
                            </tr>
                            <tr>
                                <th><label for="postcode">Postcode</label></th>
                                <td><input type="text" name="postcode" id="postcode" value="<?php echo esc_attr( $shipping_address['postcode'] ); ?>" required /></td>
                            </tr>
                            <tr>
                                <th><label for="country">Country</label></th>
                                <td><input type="text" name="country" id="country" value="<?php echo esc_attr( $shipping_address['country'] ); ?>" required /></td>
                            </tr>
                            <!-- Parcel Dimension Fields -->
                            <tr>
                                <th><label for="parcel_length">Parcel Length (in inches)</label></th>
                                <td><input type="number" step="0.1" name="parcel_length" id="parcel_length" required /></td>
                            </tr>
                            <tr>
                                <th><label for="parcel_width">Parcel Width (in inches)</label></th>
                                <td><input type="number" step="0.1" name="parcel_width" id="parcel_width" required /></td>
                            </tr>
                            <tr>
                                <th><label for="parcel_height">Parcel Height (in inches)</label></th>
                                <td><input type="number" step="0.1" name="parcel_height" id="parcel_height" required /></td>
                            </tr>
                            <tr>
                                <th><label for="parcel_weight">Parcel Weight (in ounces)</label></th>
                                <td><input type="number" step="0.1" name="parcel_weight" id="parcel_weight" required /></td>
                            </tr>
                        </table>
                        <?php submit_button( 'Validate & Generate Label' ); ?>
                    </form>
                    <?php
                }
            }
        } elseif ( $step === 'edit' && isset( $_POST['order_id'] ) && check_admin_referer( 'easypost_label_generator_action', 'easypost_label_generator_nonce' ) ) {
            // Step 2: Process the edited shipping info and create a shipment via EasyPost.
            $order_id = absint( $_POST['order_id'] );
            $shipping = array(
                'first_name' => sanitize_text_field( $_POST['first_name'] ),
                'last_name'  => sanitize_text_field( $_POST['last_name'] ),
                'address_1'  => sanitize_text_field( $_POST['address_1'] ),
                'address_2'  => sanitize_text_field( $_POST['address_2'] ),
                'city'       => sanitize_text_field( $_POST['city'] ),
                'state'      => sanitize_text_field( $_POST['state'] ),
                'postcode'   => sanitize_text_field( $_POST['postcode'] ),
                'country'    => sanitize_text_field( $_POST['country'] ),
            );
            
            $validation = is_address_valid( $shipping );
            if ( $validation !== true ) {
                echo '<div class="error"><p>Address error: ' . esc_html( $validation ) . '</p></div>';
                return;
            }
            
            // Retrieve parcel dimensions from the form.
            $parcel_length = isset( $_POST['parcel_length'] ) ? floatval( $_POST['parcel_length'] ) : 10;
            $parcel_width  = isset( $_POST['parcel_width'] ) ? floatval( $_POST['parcel_width'] ) : 7;
            $parcel_height = isset( $_POST['parcel_height'] ) ? floatval( $_POST['parcel_height'] ) : 5;
            $parcel_weight = isset( $_POST['parcel_weight'] ) ? floatval( $_POST['parcel_weight'] ) : 32;
            
            // Create the recipient ("to_address") via EasyPost with address verification.
            $to_address_payload = array(
                'name'    => trim( $shipping['first_name'] . ' ' . $shipping['last_name'] ),
                'street1' => $shipping['address_1'],
                'street2' => $shipping['address_2'],
                'city'    => $shipping['city'],
                'state'   => $shipping['state'],
                'zip'     => $shipping['postcode'],
                'country' => $shipping['country'],
                'verify'  => array( "delivery" )
            );
            
            $to_address = easypost_api_request( 'addresses', $to_address_payload );
            if ( is_wp_error( $to_address ) ) {
                echo '<div class="error"><p>To address error: ' . esc_html( $to_address->get_error_message() ) . '</p></div>';
                return;
            }
            
            // Create the sender ("from_address") using your store's details.
            $from_address_payload = array(
                'company' => STORE_NAME,
                'street1' => STORE_ADDRESS_1,
                'street2' => STORE_ADDRESS_2,
                'city'    => STORE_CITY,
                'state'   => STORE_STATE,
                'zip'     => STORE_POSTCODE,
                'country' => STORE_COUNTRY,
            );
            
            $from_address = easypost_api_request( 'addresses', $from_address_payload );
            if ( is_wp_error( $from_address ) ) {
                echo '<div class="error"><p>From address error: ' . esc_html( $from_address->get_error_message() ) . '</p></div>';
                return;
            }
            
            // Create a parcel using the submitted dimensions and weight.
            $parcel_payload = array(
                'length' => $parcel_length,
                'width'  => $parcel_width,
                'height' => $parcel_height,
                'weight' => $parcel_weight
            );
            
            $parcel = easypost_api_request( 'parcels', $parcel_payload );
            if ( is_wp_error( $parcel ) ) {
                echo '<div class="error"><p>Parcel error: ' . esc_html( $parcel->get_error_message() ) . '</p></div>';
                return;
            }
            
            // Create a shipment using the created addresses and parcel.
            $shipment_payload = array(
                'shipment' => array(
                    'to_address'   => $to_address,
                    'from_address' => $from_address,
                    'parcel'       => $parcel,
                )
            );
            
            // If Loomis carrier account is set, restrict shipment to it.
            if ( defined('LOOMIS_CARRIER_ACCOUNT') && LOOMIS_CARRIER_ACCOUNT ) {
                $shipment_payload['shipment']['carrier_accounts'] = array( LOOMIS_CARRIER_ACCOUNT );
            }
            
            $shipment = easypost_api_request( 'shipments', $shipment_payload );
            if ( is_wp_error( $shipment ) ) {
                echo '<div class="error"><p>Shipment error: ' . esc_html( $shipment->get_error_message() ) . '</p></div>';
                return;
            }
            
            // Log the shipment response for debugging.
            error_log( 'EasyPost Shipment: ' . print_r( $shipment, true ) );
            
            // Ensure rates were returned.
            if ( empty( $shipment['rates'] ) ) {
                echo '<div class="error"><p>No rates returned.</p></div>';
                return;
            }
            
            // Choose the best rate.
            $selected_rate = null;
            foreach ( $shipment['rates'] as $rate ) {
                // If Loomis is set, filter by Loomis carrier.
                if ( defined('LOOMIS_CARRIER_ACCOUNT') && LOOMIS_CARRIER_ACCOUNT ) {
                    if ( isset( $rate['carrier'] ) && $rate['carrier'] === 'Loomis' ) {
                        $selected_rate = $rate;
                        break;
                    }
                } else {
                    if ( is_null( $selected_rate ) || floatval( $rate['rate'] ) < floatval( $selected_rate['rate'] ) ) {
                        $selected_rate = $rate;
                    }
                }
            }
            
            if ( ! $selected_rate ) {
                echo '<div class="error"><p>No valid rate found.</p></div>';
                return;
            }
            
            // Purchase the shipment label using the selected rate, requesting a PNG image.
            $buy_payload = array(
                'rate'             => $selected_rate,
                'label_file_type'  => 'PNG'
            );
            $shipment_buy_endpoint = 'shipments/' . $shipment['id'] . '/buy';
            $bought_shipment = easypost_api_request( $shipment_buy_endpoint, $buy_payload );
            if ( is_wp_error( $bought_shipment ) ) {
                echo '<div class="error"><p>Buy shipment error: ' . esc_html( $bought_shipment->get_error_message() ) . '</p></div>';
                return;
            }
            
            if ( isset( $bought_shipment['postage_label']['label_url'] ) ) {
                $label_url = $bought_shipment['postage_label']['label_url'];
                echo '<div class="updated">';
                echo '<p>Label generated successfully!</p>';
                echo '<img src="' . esc_url( $label_url ) . '" alt="Shipping Label" style="max-width: 400px;" />';
                echo '</div>';
            } else {
                echo '<div class="error"><p>Label URL not found in API response.</p></div>';
            }
        }
        ?>
    </div>
    <?php
}
