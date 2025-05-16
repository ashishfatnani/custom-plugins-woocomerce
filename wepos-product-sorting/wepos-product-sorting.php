<?php
/*
Plugin Name: WePOS Product Sorting
Description: Adds sorting functionality to WePOS product list
Version: 1.1
Author: Ashish
*/

// Register settings
add_action('admin_init', 'wepos_sorting_settings');
function wepos_sorting_settings() {
    register_setting('wepos_sorting', 'wepos_sorting_orderby', ['default' => 'title']);
    register_setting('wepos_sorting', 'wepos_sorting_order', ['default' => 'asc']);
}

// Add admin menu under "Settings"
add_action('admin_menu', 'wepos_sorting_menu');
function wepos_sorting_menu() {
    add_options_page(
        'WePOS Sorting Settings',
        'WePOS Sorting',
        'manage_options',
        'wepos-sorting',
        'wepos_sorting_page'
    );
}

// Render admin settings page
function wepos_sorting_page() {
    ?>
    <div class="wrap">
        <h1>WePOS Sorting Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wepos_sorting');
            do_settings_sections('wepos_sorting');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Order By</th>
                    <td>
                        <select name="wepos_sorting_orderby">
                            <option value="title" <?php selected(get_option('wepos_sorting_orderby'), 'title'); ?>>Title</option>
                            <option value="date" <?php selected(get_option('wepos_sorting_orderby'), 'date'); ?>>Date</option>
                            <option value="price" <?php selected(get_option('wepos_sorting_orderby'), 'price'); ?>>Price</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Order</th>
                    <td>
                        <select name="wepos_sorting_order">
                            <option value="asc" <?php selected(get_option('wepos_sorting_order'), 'asc'); ?>>Ascending</option>
                            <option value="desc" <?php selected(get_option('wepos_sorting_order'), 'desc'); ?>>Descending</option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Apply sorting to WePOS product queries
add_filter('wepos_products_query_args', 'wepos_add_custom_sorting');
function wepos_add_custom_sorting($args) {
    $orderby = get_option('wepos_sorting_orderby', 'title');
    $order   = get_option('wepos_sorting_order', 'asc');

    // Handle price sorting specifically
    if ($orderby === 'price') {
        $args['orderby']  = 'meta_value_num';
        $args['meta_key'] = '_price';
    } else {
        $args['orderby'] = $orderby;
    }

    $args['order'] = $order;

    return $args;
}
