<?php get_header(); ?>

<div id="primary" class="content-area">
  <main id="main" class="site-main container">
    <h2>Welcome to Our Cigar Store</h2>
    <p>Browse our selection of premium cigars below:</p>
    <?php
    // For a WooCommerce shop, this can display the shop page:
    if ( function_exists( 'woocommerce_content' ) ) {
      woocommerce_content();
    } else {
      // Fallback: simple loop if no WooCommerce is available.
      if ( have_posts() ) :
          while ( have_posts() ) : the_post();
              the_title( '<h2>', '</h2>' );
              the_content();
          endwhile;
      else :
          echo '<p>No content found.</p>';
      endif;
    }
    '<p>No content found ksdnfkdsnfkdsnfkdsnfkdsnfndsfds.</p>';
    ?>
  </main>
</div>

<?php get_footer(); ?>
