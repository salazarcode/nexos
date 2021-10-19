<?php

/**
 * Theme setup.
 */

namespace App;

use function Roots\asset;

/**
 * Register the theme assets.
 *
 * @return void
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('sage/vendor.js', asset('scripts/vendor.js')->uri(), ['jquery'], null, true);
    wp_enqueue_script('sage/app.js', asset('scripts/app.js')->uri(), ['sage/vendor.js', 'jquery'], null, true);

    wp_add_inline_script('sage/vendor.js', asset('scripts/manifest.js')->contents(), 'before');

    if (is_single() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }

    wp_enqueue_style('sage/app.css', asset('styles/app.css')->uri(), false, null);
}, 100);

/**
 * Register the theme assets with the block editor.
 *
 * @return void
 */
add_action('enqueue_block_editor_assets', function () {
    if ($manifest = asset('scripts/manifest.asset.php')->get()) {
        wp_enqueue_script('sage/vendor.js', asset('scripts/vendor.js')->uri(), $manifest['dependencies'], null, true);
        wp_enqueue_script('sage/editor.js', asset('scripts/editor.js')->uri(), ['sage/vendor.js'], null, true);

        wp_add_inline_script('sage/vendor.js', asset('scripts/manifest.js')->contents(), 'before');
    }

    wp_enqueue_style('sage/editor.css', asset('styles/editor.css')->uri(), false, null);
}, 100);

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {
    /**
     * Enable features from Soil when plugin is activated
     * @link https://roots.io/plugins/soil/
     */
    add_theme_support('soil-clean-up');
    add_theme_support('soil-nav-walker');
    add_theme_support('soil-nice-search');
    add_theme_support('soil-relative-urls');

    /**
     * Enable plugins to manage the document title
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
     */
    add_theme_support('title-tag');

    /**
     * Register navigation menus
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    register_nav_menus([
        'primary_navigation' => __('Primary Navigation', 'sage')
    ]);

    /**
     * Enable post thumbnails
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support('post-thumbnails');

    /**
     * Add theme support for Wide Alignment
     * @link https://wordpress.org/gutenberg/handbook/designers-developers/developers/themes/theme-support/#wide-alignment
     */
    add_theme_support('align-wide');

    /**
     * Enable responsive embeds
     * @link https://wordpress.org/gutenberg/handbook/designers-developers/developers/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('responsive-embeds');

    /**
     * Enable HTML5 markup support
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
     */
    add_theme_support('html5', ['caption', 'comment-form', 'comment-list', 'gallery', 'search-form']);

    /**
     * Enable selective refresh for widgets in customizer
     * @link https://developer.wordpress.org/themes/advanced-topics/customizer-api/#theme-support-in-sidebars
     */
    add_theme_support('customize-selective-refresh-widgets');

    /**
     * Enable theme color palette support
     * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#block-color-palettes
     */
    add_theme_support('editor-color-palette', [
        [
            'name'  => __('Primary', 'sage'),
            'slug'  => 'primary',
            'color' => '#525ddc',
        ]
    ]);
}, 20);

/**
 * Register the theme sidebars.
 *
 * @return void
 */
add_action('widgets_init', function () {
    $config = [
        'before_widget' => '<section class="widget %1$s %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3>',
        'after_title' => '</h3>'
    ];

    register_sidebar([
        'name' => __('Primary', 'sage'),
        'id' => 'sidebar-primary'
    ] + $config);

    register_sidebar([
        'name' => __('Footer', 'sage'),
        'id' => 'sidebar-footer'
    ] + $config);
});


add_action( 'pre_get_posts', function( $query ){
    if( $query->is_category() && $query->is_main_query() ){
        $query->set( 'post_type', array( 'post' /** aquí se colocan los nuevos cpt que soporten cats */ ) );
    }
} );

add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );


add_action( 'wp_ajax_nopriv_create_user', function(){
    if( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'create_user' ) )
    wp_send_json_error( 'Ups, algo ha fallado. Por favor intentalo de nuevo más tarde.' );

    $username = $_POST['user'];
    $password = $_POST['pass'];
    $email    = $_POST['mail'];
    $name     = $_POST['name'];
    $nick     = $_POST['nick'];
 
    /**
     * IMPORTANT: You should make server side validation here!
     *
     */
 
    $userdata = array(
        'user_login' => $username,
        'user_pass'  => $password,
        'user_email' => $email,
        'first_name' => $name,
        'nickname'   => $nick,
    );
 
    $user_id = wp_insert_user( $userdata ) ;
 
    // Return
    if( !is_wp_error($user_id) ) {

        /**Obtenemos el ID del usuario */

        $user = get_user_by( 'email', $email );
        $user_id = $user->ID;

        //login
        wp_set_current_user($user_id, $username);
        wp_set_auth_cookie($user_id);
        add_action('wp_login', $username, 10, 2 );
        wp_send_json_success([
            'name' => $name, 
            'message' => 'El usuario ha sido registrado con éxito',
        ]);

    }elseif($user_id->get_error_message() == "Lo siento, ¡ese nombre de usuario ya existe!" || "Lo siento, ¡esa dirección de correo electrónico ya está en uso!"){
        $user = get_user_by( 'email', $email );
        $user_id = $user->ID;

        //login
        wp_set_current_user($user_id, $username);
        wp_set_auth_cookie($user_id);
        add_action('wp_login', $username, 10, 2 );
        wp_send_json_success([
            'name' => $name, 
            'message' => 'El usuario ya existía. Se ha iniciado la sesión',
        ]);

        
    }else {
        wp_send_json_error([$user_id->get_error_message()]);
    }
});