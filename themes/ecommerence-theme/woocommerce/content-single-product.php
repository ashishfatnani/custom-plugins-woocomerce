<?php
/**
 * The template for displaying single product content.
 * Override by copying to yourtheme/woocommerce/content-single-product.php.
 */

defined( 'ABSPATH' ) || exit;

global $product;

?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>
  
  <div class="product-gallery">
    <?php
      // Display product images.
      do_action( 'woocommerce_before_single_product_summary' );
    ?>
  </div>
  
  <div class="product-summary">
    <?php
      // Display product title, price, and short description.
      do_action( 'woocommerce_single_product_summary' );
    ?>
  </div>

  <div class="product-details">
    <?php
      // Display full product description.
      the_content();
    ?>
  </div>
</div>
