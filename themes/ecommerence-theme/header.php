<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<header class="site-header">
  <div class="container">
    <!-- Logo -->
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
      <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>" alt="<?php bloginfo('name'); ?> Logo">
    </a>
    <!-- Site Title & Description -->
    <h1><?php bloginfo( 'name' ); ?></h1>
    <p><?php bloginfo( 'description' ); ?></p>
    <!-- Navigation -->
    <nav class="main-navigation">
      <?php
      wp_nav_menu( array(
          'theme_location' => 'primary',
          'menu_id'        => 'primary-menu',
      ) );
      ?>
    </nav>
    <!-- Authentication Links -->
    <div class="auth-links">
      <?php if ( is_user_logged_in() ) : ?>
          <a href="<?php echo esc_url( admin_url() ); ?>">Dashboard</a> |
          <a href="<?php echo esc_url( wp_logout_url() ); ?>">Logout</a>
      <?php else : ?>
          <a href="<?php echo esc_url( wp_login_url() ); ?>">Login</a> |
          <a href="<?php echo esc_url( wp_registration_url() ); ?>">Register</a>
      <?php endif; ?>
    </div>
  </div>
</header>
