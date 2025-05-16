<?php
/**
 * The Template for displaying product archives (shop page).
 * Override by copying to yourtheme/woocommerce/archive-product.php.
 */

defined( 'ABSPATH' ) || exit;

get_header(); ?>

<div class="shop-container container">
  <header class="woocommerce-products-header">
    <?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
      <h1 class="page-title"><?php woocommerce_page_title(); ?></h1>
    <?php endif; ?>
    
    <?php do_action( 'woocommerce_archive_description' ); ?>
  </header>

  <?php if ( woocommerce_product_loop() ) : ?>

    <?php do_action( 'woocommerce_before_shop_loop' ); ?>

    <div class="products-grid">
      <?php
      woocommerce_product_loop_start();

      while ( have_posts() ) : the_post();

        /**
         * Get the content-product template.
         * This displays the product details for each product.
         */
        wc_get_template_part( 'content', 'product' );

      endwhile;

      woocommerce_product_loop_end();
      ?>
    </div>

    <?php do_action( 'woocommerce_after_shop_loop' ); ?>

  <?php else : ?>

    <?php do_action( 'woocommerce_no_products_found' ); ?>

  <?php endif; ?>
</div>

<?php get_footer(); ?>
