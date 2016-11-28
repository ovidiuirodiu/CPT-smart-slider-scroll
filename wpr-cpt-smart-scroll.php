<?php
/**
 * Plugin Name: WPR add CPT smart scroll
 * Plugin URI: http://www.wpriders.com
 * Description: WPR add CPT smart scroll
 * Version: 1.0.0
 * Author: Ovidiu Irodiu from WPRiders
 * Author URI: http://www.wpriders.com
 * License: GPL2
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPR_cpt_smart_scroll' ) ) {
	/**
	 * Class WPR_cpt_smart_scroll
	 */
	class WPR_cpt_smart_scroll {
		/**
		 * WPR_cpt_smart_scroll constructor.
		 */
		function __construct() {
			add_action( 'wp_enqueue_scripts', array( &$this, 'wpr_enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( &$this, 'wpr_backend_enqueue_scripts' ) );
			add_action( 'init', array( &$this, 'wpr_cpt_register' ) );
			add_action( 'wp', array( &$this, 'wpr_redirect_to_url' ) );
			if ( is_admin() ) {
				add_action( 'admin_head', array( &$this, 'wpr_shortcode_button_init' ) );
				add_action( 'wp_ajax_wpr_get_sliders', array( &$this, 'wpr_get_sliders' ) );
			}
			add_shortcode( 'wpr-slides', array( &$this, 'wpr_get_slides' ) );
			add_action( 'wp_ajax_more_post_slides_ajax', array( &$this, 'wpr_more_post_slides_ajax' ) );
			add_action( 'wp_ajax_nopriv_more_post_slides_ajax', array( &$this, 'wpr_more_post_slides_ajax' ) );
		}

		/**
		 * Redirect to post/page url
		 */
		function wpr_redirect_to_url() {
			$current_url = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$posted      = substr( $current_url, 0, strpos( $current_url, "/slide/" ) );

			if ( strpos( $current_url, '/slide/' ) ) {
				wp_safe_redirect( $posted, 301 );
				exit;
			}

		}

		/**
		 * Enqueue scripts
		 */
		function wpr_enqueue_scripts() {
			wp_enqueue_style( 'wpr-plugin-style', plugin_dir_url( __FILE__ ) . 'assets/css/wpr-cpt-smart-scroll.css', array(), time() );

			if ( is_single() || is_page() ) {
				wp_enqueue_script( 'wpr-plugin-script', plugin_dir_url( __FILE__ ) . 'assets/js/wpr-cpt-smart-scroll.js', array( 'jquery' ), time(), true );

				$args = array(
					'nonce'        => wp_create_nonce( 'wpr-load-more-nonce' ),
					'ajax_url'     => admin_url( 'admin-ajax.php', 'http' ),
					'currenturl'   => get_permalink( get_the_ID() ),
					'currenttitle' => get_the_title(),
				);
				wp_localize_script( 'wpr-plugin-script', 'ajax_object', $args );
			}
		}

		/**
		 * Load back end scripts
		 */
		function wpr_backend_enqueue_scripts() {
			wp_enqueue_script( 'wpr-backend-plugin-script', plugin_dir_url( __FILE__ ) . 'assets/js/wpr-back-end.js', array( 'jquery' ), time(), true );
			wp_localize_script( 'wpr-backend-plugin-script', 'ajax_backend', array( 'ajax_url' => admin_url( 'admin-ajax.php', 'http' ) ) );
		}

		/**
		 * Register CPT smart
		 */
		function wpr_cpt_register() {
			$labels = array(
				'name'               => _x( 'Post slide', 'wpr-lang' ),
				'singular_name'      => _x( 'Post slide', 'wpr-lang' ),
				'add_new'            => _x( 'Add New', 'wpr_lang' ),
				'add_new_item'       => __( 'Add New slide' ),
				'edit_item'          => __( 'Edit Post slide' ),
				'new_item'           => __( 'New Post slide' ),
				'all_items'          => __( 'All Post slide' ),
				'view_item'          => __( 'View Post slide' ),
				'search_items'       => __( 'Search Post slide' ),
				'not_found'          => __( 'No post slide found' ),
				'not_found_in_trash' => __( 'No post slide found in the Trash' ),
				'parent_item_colon'  => '',
				'menu_name'          => 'Post slide',
			);
			$args   = array(
				'labels'            => $labels,
				'description'       => 'Holds our Post slide specific data',
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_in_nav_menus' => true,
				'show_in_admin_bar' => true,
				'menu_position'     => 5,
				'supports'          => array( 'title', 'editor', 'thumbnail' ),
				'has_archive'       => true,
				'hierarchical'      => false,
				'capability_type'   => 'post',
			);
			register_post_type( 'wpr_post_slide', $args );

			$labels = array(
				'name'              => _x( 'Category Sliders', 'wpr-lang' ),
				'singular_name'     => _x( 'Slider', 'wpr-lang' ),
				'search_items'      => __( 'Search Sliders' ),
				'all_items'         => __( 'All Sliders' ),
				'parent_item'       => __( 'Parent Slider' ),
				'parent_item_colon' => __( 'Parent Slider:' ),
				'edit_item'         => __( 'Edit Slider' ),
				'update_item'       => __( 'Update Slider' ),
				'add_new_item'      => __( 'Add New Slider' ),
				'new_item_name'     => __( 'New Slider' ),
			);

			register_taxonomy( 'wpr_post_slider', array( 'wpr_post_slide' ), array(
				'hierarchical'      => true,
				'labels'            => $labels,
				'show_ui'           => true,
				'query_var'         => true,
				'show_in_nav_menus' => true,
				'rewrite'           => array( 'slug' => 'post-slider', 'with_front' => false ),
			) );
		}

		/**
		 * Add button in TinyMCE
		 */
		function wpr_shortcode_button_init() {
			global $typenow;

			if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
				return;
			}

			if ( ! in_array( $typenow, array( 'post', 'page' ) ) ) {
				return;
			}

			// Check if WYSIWYG is enabled
			if ( 'true' === get_user_option( 'rich_editing' ) ) {
				//Add a callback to regiser our TinyMCE plugin
				add_filter( 'mce_external_plugins', array( &$this, 'wpr_register_tinymce_plugin' ) );

				// Add a callback to add our button to the TinyMCE toolbar
				add_filter( 'mce_buttons', array( &$this, 'wpr_add_tinymce_button' ) );
			}

		}

		/**
		 * Declare script for button
		 *
		 * @param $plugin_array
		 *
		 * @return mixed
		 */
		function wpr_register_tinymce_plugin( $plugin_array ) {
			$plugin_array['wpr_add_shortcode_button'] = plugin_dir_url( __FILE__ ) . 'assets/js/wpr-tinymce-shortcode-button.js';

			return $plugin_array;
		}

		/**
		 * Register button
		 *
		 * @param $buttons
		 *
		 * @return array
		 */
		function wpr_add_tinymce_button( $buttons ) {
			array_push( $buttons, 'wpr_add_shortcode_button' );

			return $buttons;
		}

		/**
		 * Load slider categories
		 */
		function wpr_get_sliders() {
			$args = array(
				'post_type' => 'wpr_post_slide',
				'taxonomy'  => 'wpr_post_slider',
			);

			$categories = get_categories( $args );
			$tiny_list  = array();
			foreach ( $categories as $cat ) :
				$tiny_list[] = array( 'text' => $cat->name, 'value' => $cat->term_id );
			endforeach;
			echo wp_json_encode( $tiny_list );
			wp_die();
		}

		/**
		 * Shortcode
		 *
		 * @return string
		 */
		function wpr_get_slides( $atts ) {
			$output = '';

			$get_current_url = get_permalink( get_the_ID() );

			/* Set up the default arguments. */
			$defaults = array(
				'wpr_slider'  => '',
				'wpr_default' => 3,
			);

			/* Parse the arguments. */
			extract( shortcode_atts( $defaults, $atts ) );

			$slider_cat     = $atts['wpr_slider'];
			$slides_default = $atts['wpr_default'];

			if ( ! empty( $slider_cat ) ) {
				$args = array(
					'post_type'      => 'wpr_post_slide',
					'post_status'    => 'publish',
					'posts_per_page' => $slides_default,
					'order'          => 'ASC',
					'orderby'        => 'menu_order',
					'tax_query'      => array(
						array(
							'taxonomy' => 'wpr_post_slider',
							'terms'    => $slider_cat,
							'field'    => 'term_id',
						),
					),
				);

				$posts = new WP_Query( $args );

				if ( $posts->have_posts() ) {
					$i = 0;
					$output .= '<div id="wpr-display-posts" class="wpr-display-posts" data-index="' . ( $posts->found_posts - 1 ) . '" data-post-number="' . $slides_default . '" data-post-slide="' . $slider_cat . '">';
					while ( $posts->have_posts() ) {
						$posts->the_post();
						$output .= '<div id="post-' . get_the_ID() . '" class="wpr-post-slide" data-url="' . $get_current_url . 'slide/' . ( $i + 1 ) . '">';

						$output .= '<div class="wpr-post-slide-number">' . ( $i + 1 ) . ' / ' . $posts->found_posts . '</div>';
						if ( has_post_thumbnail( get_the_ID() ) ) {
							$image = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'full' );
							$output .= '<div class="wpr-post-slide-image"><img src="' . $image[0] . '" /></div>';
						}
						$output .= '<div class="wpr-post-slide-content">' . wpautop( get_the_content() ) . '</div>';

						$output .= '</div>';
						$i ++;
					}
					$output .= '<div class="wpr-load-more"></div></div>';
				}
				wp_reset_postdata();
			}

			return $output;
		}

		/**
		 * Load more post slides
		 */
		function wpr_more_post_slides_ajax() {
			check_ajax_referer( 'wpr-load-more-nonce', 'nonce' );

			$get_current_url = esc_url( $_POST['currenturl'] );
			$slider_cat      = absint( $_POST['slider'] );
			$post_p_page     = absint( $_POST['ppp'] );
			$slides_nr       = absint( $_POST['slidenr'] ) + 1;
			$slideindex      = absint( $_POST['slideindex'] );

			$posts_ids       = array();
			foreach ( $_POST['currentittems'] as $postsid ) {
				$posts_ids[] = absint( $postsid );
			}

			$args = array(
				'post_type'      => 'wpr_post_slide',
				'post_status'    => 'publish',
				'post__not_in'   => $posts_ids,
				'posts_per_page' => $post_p_page,
				'order'          => 'ASC',
				'orderby'        => 'menu_order',
				'tax_query'      => array(
					array(
						'taxonomy' => 'wpr_post_slider',
						'terms'    => $slider_cat,
						'field'    => 'term_id',
					),
				),
			);

			//$args['paged'] = esc_attr( $_POST['page'] );

			ob_start();
			$loop = new WP_Query( $args );

			$output = '';
			if ( $loop->have_posts() ) {
				$i = 1;
				while ( $loop->have_posts() ) {
					$loop->the_post();

					$output .= '<div id="post-' . get_the_ID() . '" class="wpr-post-slide" data-url="' . $get_current_url . 'slide/' . ( $i + $slideindex ) . '">';
					$output .= '<div class="wpr-post-slide-number"> ' . ( $i + $slideindex ) . ' / ' . $slides_nr . '</div>';
					if ( has_post_thumbnail( get_the_ID() ) ) {
						$image = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'full' );
						$output .= '<div class="wpr-post-slide-image"><img src="' . $image[0] . '" /></div>';
					}
					$output .= '<div class="wpr-post-slide-content">' . wpautop( get_the_content() ) . '</div>';

					$output .= '</div>';
					$i ++;
				}
			}

			wp_reset_postdata();

			echo $output;

			wp_die();
		}
	}
}

$wpr_cpt_scroll = new WPR_cpt_smart_scroll();