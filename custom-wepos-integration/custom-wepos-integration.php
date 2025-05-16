<?php
/**
 * Plugin Name: Custom WePOS Integration
 * Description: Customizes WePOS functionality and adds product sorting
 * Version: 1.2
 * Author: Ashish
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom scripts and styles for WePOS
 */
function register_wepos_custom_assets() {
    wp_register_script(
        'wepos-customizations', 
        plugin_dir_url(__FILE__) . 'wepos-customizations.js',
        array('jquery'),
        '1.2.' . time(), // Add timestamp to prevent caching during development
        true
    );
    
    wp_register_style(
        'wepos-custom-styles',
        plugin_dir_url(__FILE__) . 'wepos-custom-styles.css',
        array(),
        '1.2.' . time() // Add timestamp to prevent caching during development
    );
}
add_action('wp_loaded', 'register_wepos_custom_assets');

/**
 * Detect WePOS screens and load customizations
 */
function load_wepos_customizations() {
    // Check if we're on a WePOS page
    $is_wepos_page = false;
    
    // Check URL parameters
    if (isset($_GET['page']) && $_GET['page'] == 'wepos') {
        $is_wepos_page = true;
    }
    
    // Check URL path
    if (strpos($_SERVER['REQUEST_URI'], 'wepos') !== false) {
        $is_wepos_page = true;
    }
    
    // If not on WePOS page, exit
    if (!$is_wepos_page) {
        return;
    }
    
    // Enqueue our scripts and styles
    wp_enqueue_script('wepos-customizations');
    wp_enqueue_style('wepos-custom-styles');
    
    // Add inline script with dynamic nonce for AJAX
    $script_data = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wepos_nonce'),
    );
    
    wp_localize_script('wepos-customizations', 'weposCustom', $script_data);
    
    // Add fallback inline script if JS file doesn't load
    inject_inline_wepos_script();
}
add_action('wp_enqueue_scripts', 'load_wepos_customizations');
add_action('admin_enqueue_scripts', 'load_wepos_customizations');

/**
 * Fallback inline script in case the external script fails to load
 */
function inject_inline_wepos_script() {
    ?>
    <script>
    console.log('WEPOS CUSTOMIZATION PLUGIN LOADED - VERSION 1.2 (INLINE)');
    
    // Wait for jQuery to be available
    function waitForJQuery() {
        if (typeof jQuery !== 'undefined') {
            console.log('jQuery is available, initializing WePOS customizations');
            initWithJQuery(jQuery);
        } else {
            console.log('jQuery not available yet, waiting...');
            setTimeout(waitForJQuery, 500);
        }
    }
    
    // Initialize with jQuery when available
    function initWithJQuery($) {
        
        // Define the main initialization function
        function initWeposCustomizations() {
            console.log('Attempting to initialize WePOS customizations...');
            
            // Function to add sorting dropdown
            function addSortingDropdown() {
                console.log('Looking for categories dropdown...');
                if ($('.select-dropdown-wrap').length > 0) {
                    console.log('Found categories dropdown, adding sorting dropdown');
                    
                    // Check if our dropdown already exists
                    if ($('#wepos-sort-products').length > 0) {
                        console.log('Sorting dropdown already exists');
                        return;
                    }
                    
                    const sortingHtml = `
                        <div class="select-dropdown-wrap wepos-sorting-wrap" style="margin-left: 10px;">
                            <select id="wepos-sort-products" class="form-control">
                                <option value="">Sort Products</option>
                                <option value="title-asc">Name A-Z</option>
                                <option value="title-desc">Name Z-A</option>
                                <option value="price-asc">Price Low to High</option>
                                <option value="price-desc">Price High to Low</option>
                                <option value="date-desc">Newest First</option>
                            </select>
                        </div>
                    `;
                    
                    $('.select-dropdown-wrap').after(sortingHtml);
                    
                    // Handle sorting changes
                    $('#wepos-sort-products').on('change', function() {
                        const sortValue = $(this).val();
                        if (!sortValue) return;
                        
                        console.log('Sort value selected:', sortValue);
                        const [orderby, order] = sortValue.split('-');
                        console.log(`Sorting products by ${orderby} in ${order} order`);
                        
                        // Get all product elements
                        const productContainer = $('.products');
                        const productItems = $('.wepos-product-wrap');
                        
                        console.log(`Found ${productItems.length} products to sort`);
                        
                        if (productItems.length) {
                            // Sort the products
                            const sortedItems = productItems.toArray().sort(function(a, b) {
                                let aValue, bValue;
                                
                                if (orderby === 'title') {
                                    aValue = $(a).find('.product-title').text().toLowerCase();
                                    bValue = $(b).find('.product-title').text().toLowerCase();
                                } else if (orderby === 'price') {
                                    aValue = parseFloat($(a).find('.product-price').text().replace(/[^0-9.-]+/g, '')) || 0;
                                    bValue = parseFloat($(b).find('.product-price').text().replace(/[^0-9.-]+/g, '')) || 0;
                                } else if (orderby === 'date') {
                                    // If product has data-id, use that for sorting by newest
                                    aValue = parseInt($(a).data('id') || 0);
                                    bValue = parseInt($(b).data('id') || 0);
                                }
                                
                                if (order === 'asc') {
                                    return aValue > bValue ? 1 : -1;
                                } else {
                                    return aValue < bValue ? 1 : -1;
                                }
                            });
                            
                            // Reappend sorted elements
                            $(sortedItems).each(function() {
                                productContainer.append(this);
                            });
                            
                            console.log('Products sorted successfully');
                        }
                    });
                    
                    return true;
                } else {
                    console.log('Categories dropdown not found, will retry');
                    return false;
                }
            }
            
            // Function to add customer reference field
            function addCustomerReferenceField() {
                console.log('Looking for Pay Now button...');
                if ($('.pay-btn-wrap').length > 0) {
                    console.log('Found Pay Now button, adding custom field');
                    
                    // Check if our field already exists
                    if ($('#customer_reference').length > 0) {
                        console.log('Reference field already exists');
                        return;
                    }
                    
                    const checkoutFieldHtml = `
                        <div class="wepos-custom-fields" style="margin: 15px 0; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Customer Reference #</label>
                            <input type="text" id="customer_reference" name="customer_reference" placeholder="Enter reference number" 
                                style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                        </div>
                    `;
                    
                    $('.pay-btn-wrap').before(checkoutFieldHtml);
                    
                    // Save the reference when Pay Now is clicked
                    $('.pay-btn').off('click.customRef').on('click.customRef', function() {
                        const reference = $('#customer_reference').val();
                        if (reference) {
                            console.log('Saving customer reference:', reference);
                            // Store reference in localStorage to access it after order creation
                            localStorage.setItem('wepos_customer_reference', reference);
                            
                            // Listen for order completion (this is a simplified approach)
                            const checkForOrderId = setInterval(function() {
                                const orderIdElement = $('.wepos-receipt-order-id');
                                if (orderIdElement.length) {
                                    const orderId = orderIdElement.text().replace(/[^0-9]/g, '');
                                    if (orderId) {
                                        clearInterval(checkForOrderId);
                                        console.log('Order created with ID:', orderId);
                                        
                                        // Send AJAX request to save reference
                                        $.ajax({
                                            url: weposCustom.ajax_url,
                                            type: 'POST',
                                            data: {
                                                action: 'save_wepos_reference',
                                                nonce: weposCustom.nonce,
                                                order_id: orderId,
                                                reference: reference
                                            },
                                            success: function(response) {
                                                console.log('Reference saved via AJAX:', response);
                                                localStorage.removeItem('wepos_customer_reference');
                                            },
                                            error: function(err) {
                                                console.error('Failed to save reference:', err);
                                            }
                                        });
                                    }
                                }
                            }, 1000);
                        }
                    });
                    
                    return true;
                } else {
                    console.log('Pay Now button not found, will retry');
                    return false;
                }
            }
            
            // Function to add product badges
            function addProductBadges() {
                console.log('Adding product badges...');
                
                // Get all product elements
                const productItems = $('.wepos-product-item');
                
                if (productItems.length === 0) {
                    console.log('No products found, will retry');
                    return false;
                }
                
                console.log(`Found ${productItems.length} products`);
                
                productItems.each(function() {
                    const $item = $(this);
                    
                    // Check if already processed
                    if ($item.data('badges-added')) {
                        return;
                    }
                    
                    // These classes would need to be customized based on how your WePOS marks these products
                    if ($item.hasClass('outofstock') || $item.data('stock-status') === 'outofstock') {
                        $item.find('.product-title').before('<span class="out-of-stock-badge">Out of Stock</span>');
                    }
                    
                    if ($item.hasClass('onsale') || $item.data('on-sale')) {
                        $item.find('.product-title').before('<span class="on-sale-badge">Sale!</span>');
                    }
                    
                    // Mark as processed
                    $item.data('badges-added', true);
                });
                
                return true;
            }
            
            // Attempt initialization with multiple retries
            let attemptsLeft = 10;
            let checkInterval;
            
            function attemptInitialization() {
                console.log(`Initialization attempt ${10 - attemptsLeft + 1} of 10`);
                
                let sortingAdded = addSortingDropdown();
                let referenceAdded = addCustomerReferenceField();
                let badgesAdded = addProductBadges();
                
                if (sortingAdded && referenceAdded && badgesAdded) {
                    console.log('All WePOS customizations completed successfully!');
                    clearInterval(checkInterval);
                } else {
                    console.log('Some customizations not completed yet, will retry');
                    attemptsLeft--;
                    
                    if (attemptsLeft <= 0) {
                        console.log('Failed to initialize all WePOS customizations after multiple attempts');
                        clearInterval(checkInterval);
                    }
                }
            }
            
            // Initial attempt
            attemptInitialization();
            
            // Set up interval for repeated attempts
            checkInterval = setInterval(attemptInitialization, 2000);
        }
        
        // Wait for DOM ready
        $(document).ready(function() {
            console.log('DOM ready, waiting for WePOS interface to load...');
            
            // Start with initial delay
            setTimeout(function() {
                initWeposCustomizations();
            }, 1000);
            
            // Also try to initialize when URL changes (for SPA navigation)
            let lastUrl = location.href;
            new MutationObserver(() => {
                const url = location.href;
                if (url !== lastUrl) {
                    lastUrl = url;
                    console.log('URL changed, reinitializing WePOS customizations');
                    setTimeout(initWeposCustomizations, 1000);
                }
            }).observe(document, {subtree: true, childList: true});
        });
    })(jQuery);
    </script>
    <?php
}

/**
 * Handle AJAX request to save customer reference
 */
function handle_save_customer_reference() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wepos_nonce')) {
        wp_send_json_error('Invalid security token');
        return;
    }
    
    if (isset($_POST['order_id']) && isset($_POST['reference'])) {
        $order_id = intval($_POST['order_id']);
        $reference = sanitize_text_field($_POST['reference']);
        
        if ($order_id > 0 && !empty($reference)) {
            update_post_meta($order_id, 'customer_reference', $reference);
            wp_send_json_success('Reference saved successfully');
        } else {
            wp_send_json_error('Invalid order ID or reference');
        }
    } else {
        wp_send_json_error('Missing required parameters');
    }
}
add_action('wp_ajax_save_wepos_reference', 'handle_save_customer_reference');
add_action('wp_ajax_nopriv_save_wepos_reference', 'handle_save_customer_reference');

/**
 * Save reference from localStorage after order creation
 */
function save_wepos_custom_reference($order_id) {
    // This is a fallback method for the AJAX approach
    if (is_admin()) {
        return;
    }
    
    // Check if this is a POS order
    $is_pos_order = get_post_meta($order_id, '_wepos_order', true);
    
    if ($is_pos_order || isset($_POST['_wepos_order'])) {
        // Try to get reference from POST data first
        $reference = isset($_POST['customer_reference']) ? sanitize_text_field($_POST['customer_reference']) : '';
        
        if ($reference) {
            update_post_meta($order_id, 'customer_reference', $reference);
        }
    }
}
add_action('woocommerce_checkout_update_order_meta', 'save_wepos_custom_reference', 20);
add_action('woocommerce_new_order', 'save_wepos_custom_reference', 20);

/**
 * Add reference field to order details page (admin)
 */
function add_reference_to_order_details($order) {
    $reference = get_post_meta($order->get_id(), 'customer_reference', true);
    if ($reference) {
        echo '<p><strong>Customer Reference:</strong> ' . esc_html($reference) . '</p>';
    }
}
add_action('woocommerce_admin_order_data_after_billing_address', 'add_reference_to_order_details', 10, 1);

/**
 * Display reference on customer order view
 */
function add_reference_to_customer_order($order) {
    $reference = get_post_meta($order->get_id(), 'customer_reference', true);
    if ($reference) {
        echo '<p><strong>Reference:</strong> ' . esc_html($reference) . '</p>';
    }
}
add_action('woocommerce_order_details_after_order_table', 'add_reference_to_customer_order', 10, 1);

/**
 * Create plugin files on activation
 */
function create_wepos_custom_files() {
    // Create JS file
    $js_content = <<<EOT
/**
 * WePOS Custom Functionality
 * Version: 1.2
 */
console.log('WEPOS CUSTOMIZATION PLUGIN LOADED - VERSION 1.2 (EXTERNAL)');

// Wait for jQuery to be available
function waitForJQuery() {
    if (typeof jQuery !== 'undefined') {
        console.log('jQuery is available, initializing WePOS customizations');
        initWithJQuery(jQuery);
    } else {
        console.log('jQuery not available yet, waiting...');
        setTimeout(waitForJQuery, 500);
    }
}

// Initialize with jQuery when available
function initWithJQuery($) {
    
    // Define the main initialization function
    function initWeposCustomizations() {
        console.log('Attempting to initialize WePOS customizations...');
        
        // Function to add sorting dropdown
        function addSortingDropdown() {
            console.log('Looking for categories dropdown...');
            if ($('.select-dropdown-wrap').length > 0) {
                console.log('Found categories dropdown, adding sorting dropdown');
                
                // Check if our dropdown already exists
                if ($('#wepos-sort-products').length > 0) {
                    console.log('Sorting dropdown already exists');
                    return;
                }
                
                const sortingHtml = `
                    <div class="select-dropdown-wrap wepos-sorting-wrap" style="margin-left: 10px;">
                        <select id="wepos-sort-products" class="form-control">
                            <option value="">Sort Products</option>
                            <option value="title-asc">Name A-Z</option>
                            <option value="title-desc">Name Z-A</option>
                            <option value="price-asc">Price Low to High</option>
                            <option value="price-desc">Price High to Low</option>
                            <option value="date-desc">Newest First</option>
                        </select>
                    </div>
                `;
                
                $('.select-dropdown-wrap').after(sortingHtml);
                
                // Handle sorting changes
                $('#wepos-sort-products').on('change', function() {
                    const sortValue = $(this).val();
                    if (!sortValue) return;
                    
                    console.log('Sort value selected:', sortValue);
                    const [orderby, order] = sortValue.split('-');
                    console.log(`Sorting products by ${orderby} in ${order} order`);
                    
                    // Get all product elements
                    const productContainer = $('.products');
                    const productItems = $('.wepos-product-wrap');
                    
                    console.log(`Found ${productItems.length} products to sort`);
                    
                    if (productItems.length) {
                        // Sort the products
                        const sortedItems = productItems.toArray().sort(function(a, b) {
                            let aValue, bValue;
                            
                            if (orderby === 'title') {
                                aValue = $(a).find('.product-title').text().toLowerCase();
                                bValue = $(b).find('.product-title').text().toLowerCase();
                            } else if (orderby === 'price') {
                                aValue = parseFloat($(a).find('.product-price').text().replace(/[^0-9.-]+/g, '')) || 0;
                                bValue = parseFloat($(b).find('.product-price').text().replace(/[^0-9.-]+/g, '')) || 0;
                            } else if (orderby === 'date') {
                                // If product has data-id, use that for sorting by newest
                                aValue = parseInt($(a).data('id') || 0);
                                bValue = parseInt($(b).data('id') || 0);
                            }
                            
                            if (order === 'asc') {
                                return aValue > bValue ? 1 : -1;
                            } else {
                                return aValue < bValue ? 1 : -1;
                            }
                        });
                        
                        // Reappend sorted elements
                        $(sortedItems).each(function() {
                            productContainer.append(this);
                        });
                        
                        console.log('Products sorted successfully');
                    }
                });
                
                return true;
            } else {
                console.log('Categories dropdown not found, will retry');
                return false;
            }
        }
        
        // Function to add customer reference field
        function addCustomerReferenceField() {
            console.log('Looking for Pay Now button...');
            if ($('.pay-btn-wrap').length > 0) {
                console.log('Found Pay Now button, adding custom field');
                
                // Check if our field already exists
                if ($('#customer_reference').length > 0) {
                    console.log('Reference field already exists');
                    return;
                }
                
                const checkoutFieldHtml = `
                    <div class="wepos-custom-fields">
                        <label>Customer Reference #</label>
                        <input type="text" id="customer_reference" name="customer_reference" placeholder="Enter reference number">
                    </div>
                `;
                
                $('.pay-btn-wrap').before(checkoutFieldHtml);
                
                // Save the reference when Pay Now is clicked
                $('.pay-btn').off('click.customRef').on('click.customRef', function() {
                    const reference = $('#customer_reference').val();
                    if (reference) {
                        console.log('Saving customer reference:', reference);
                        // Store reference in localStorage to access it after order creation
                        localStorage.setItem('wepos_customer_reference', reference);
                        
                        // Listen for order completion (this is a simplified approach)
                        const checkForOrderId = setInterval(function() {
                            const orderIdElement = $('.wepos-receipt-order-id');
                            if (orderIdElement.length) {
                                const orderId = orderIdElement.text().replace(/[^0-9]/g, '');
                                if (orderId) {
                                    clearInterval(checkForOrderId);
                                    console.log('Order created with ID:', orderId);
                                    
                                    // Send AJAX request to save reference
                                    $.ajax({
                                        url: weposCustom.ajax_url,
                                        type: 'POST',
                                        data: {
                                            action: 'save_wepos_reference',
                                            nonce: weposCustom.nonce,
                                            order_id: orderId,
                                            reference: reference
                                        },
                                        success: function(response) {
                                            console.log('Reference saved via AJAX:', response);
                                            localStorage.removeItem('wepos_customer_reference');
                                        },
                                        error: function(err) {
                                            console.error('Failed to save reference:', err);
                                        }
                                    });
                                }
                            }
                        }, 1000);
                    }
                });
                
                return true;
            } else {
                console.log('Pay Now button not found, will retry');
                return false;
            }
        }
        
        // Function to add product badges
        function addProductBadges() {
            console.log('Adding product badges...');
            
            // Get all product elements
            const productItems = $('.wepos-product-item');
            
            if (productItems.length === 0) {
                console.log('No products found, will retry');
                return false;
            }
            
            console.log(`Found ${productItems.length} products`);
            
            productItems.each(function() {
                const $item = $(this);
                
                // Check if already processed
                if ($item.data('badges-added')) {
                    return;
                }
                
                // These classes would need to be customized based on how your WePOS marks these products
                if ($item.hasClass('outofstock') || $item.data('stock-status') === 'outofstock') {
                    $item.find('.product-title').before('<span class="out-of-stock-badge">Out of Stock</span>');
                }
                
                if ($item.hasClass('onsale') || $item.data('on-sale')) {
                    $item.find('.product-title').before('<span class="on-sale-badge">Sale!</span>');
                }
                
                // Mark as processed
                $item.data('badges-added', true);
            });
            
            return true;
        }
        
        // Attempt initialization with multiple retries
        let attemptsLeft = 10;
        let checkInterval;
        
        function attemptInitialization() {
            console.log(`Initialization attempt ${10 - attemptsLeft + 1} of 10`);
            
            let sortingAdded = addSortingDropdown();
            let referenceAdded = addCustomerReferenceField();
            let badgesAdded = addProductBadges();
            
            if (sortingAdded && referenceAdded && badgesAdded) {
                console.log('All WePOS customizations completed successfully!');
                clearInterval(checkInterval);
            } else {
                console.log('Some customizations not completed yet, will retry');
                attemptsLeft--;
                
                if (attemptsLeft <= 0) {
                    console.log('Failed to initialize all WePOS customizations after multiple attempts');
                    clearInterval(checkInterval);
                }
            }
        }
        
        // Initial attempt
        attemptInitialization();
        
        // Set up interval for repeated attempts
        checkInterval = setInterval(attemptInitialization, 2000);
    }
    
    // Wait for DOM ready
    $(document).ready(function() {
        console.log('DOM ready, waiting for WePOS interface to load...');
        
        // Start with initial delay
        setTimeout(function() {
            initWeposCustomizations();
        }, 1000);
        
        // Also try to initialize when URL changes (for SPA navigation)
        let lastUrl = location.href;
        new MutationObserver(() => {
            const url = location.href;
            if (url !== lastUrl) {
                lastUrl = url;
                console.log('URL changed, reinitializing WePOS customizations');
                setTimeout(initWeposCustomizations, 1000);
            }
        }).observe(document, {subtree: true, childList: true});
    });
}

// Start the jQuery detection process
waitForJQuery();
EOT;

    // Create CSS file
    $css_content = <<<EOT
/**
 * WePOS Custom Styles
 * Version: 1.2
 */

/* Custom styles for WePOS */
.wepos-product-sorting {
    margin: 10px 0;
    padding: 5px;
    display: inline-block;
}

.wepos-product-sorting select {
    padding: 5px 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.out-of-stock-badge {
    background: #f44336;
    color: white;
    padding: 2px 5px;
    border-radius: 3px;
    font-size: 12px;
    margin-right: 5px;
    display: inline-block;
}

.on-sale-badge {
    background: #4CAF50;
    color: white;
    padding: 2px 5px;
    border-radius: 3px;
    font-size: 12px;
    margin-right: 5px;
    display: inline-block;
}

.wepos-custom-fields {
    margin: 15px 0;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
}

.wepos-custom-fields label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.wepos-custom-fields input {
    width: 100%;
    padding: 8px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

/* Make sure our dropdown is visible */
.wepos-sorting-wrap {
    z-index: 100;
    position: relative;
}
EOT;

    // Get plugin directory
    $plugin_dir = plugin_dir_path(__FILE__);
    
    // Write JS file
    file_put_contents($plugin_dir . 'wepos-customizations.js', $js_content);
    
    // Write CSS file
    file_put_contents($plugin_dir . 'wepos-custom-styles.css', $css_content);
}
register_activation_hook(__FILE__, 'create_wepos_custom_files');