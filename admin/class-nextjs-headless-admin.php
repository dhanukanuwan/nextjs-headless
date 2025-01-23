<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://hashcodeab.se
 * @since      1.0.0
 *
 * @package    Nextjs_Headless
 * @subpackage Nextjs_Headless/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Nextjs_Headless
 * @subpackage Nextjs_Headless/admin
 * @author     Dhanuka Gunarathna <dhanuka@hashcodeab.se>
 */
class Nextjs_Headless_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Redirect frontend to admin.
	 *
	 * @since    1.0.0
	 */
	public function headless_redirect_frontend() {

		if ( ! is_admin() ) {
			wp_safe_redirect( wp_login_url(), 302 );
			die();
		}
	}

	/**
	 * Get main navigation endpoint.
	 *
	 * @since    1.0.0
	 */
	public function next_headless_get_main_nav_endpoint() {
		register_rest_route(
			'nextheadless/v1',
			'/getmainnav',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'next_headless_get_main_nav_endpoint_callback' ),
					'args'                => array(
						'menu_location' => array(
							'required' => true,
							'type'     => 'string',
						),
					),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	/**
	 * Get main navigation endpoint callback.
	 *
	 * @param    array $request request array.
	 * @since    1.0.0
	 */
	public function next_headless_get_main_nav_endpoint_callback( $request ) {

		$menu_location = $request->get_param( 'menu_location' );

		$menu_items = array();
		$success    = false;
		$message    = '';

		if ( ! empty( $menu_location ) ) {

			$locations = get_nav_menu_locations();

			if ( ! empty( $locations ) && isset( $locations[ $menu_location ] ) ) {

				$menu       = get_term( $locations[ $menu_location ], 'nav_menu' );
				$menu_items = wp_get_nav_menu_items( $menu->term_id );

				if ( ! empty( $menu_items ) && ! is_wp_error( $menu_items ) ) {
					$success = true;
				} else {
					$message = __( 'No menu items found in the menu', 'nextjs-headless' );
				}
			} else {
				$message = __( 'Menu location not found.', 'nextjs-headless' );
			}
		} else {
			$message = __( 'Menu location is not defined.', 'nextjs-headless' );
		}

		$response = rest_ensure_response(
			array(
				'menu_items' => $menu_items,
				'success'    => $success,
				'message'    => $message,
			)
		);

		return $response;
	}

	/**
	 * Get page hero data endpoint.
	 *
	 * @since    1.0.0
	 */
	public function next_headless_get_page_hero_endpoint() {
		register_rest_route(
			'nextheadless/v1',
			'/getpagehero',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'next_headless_get_page_hero_endpoint_callback' ),
					'args'                => array(
						'slug' => array(
							'required' => true,
							'type'     => 'string',
						),
					),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	/**
	 * Get page hero data endpoint callback.
	 *
	 * @param    array $request request array.
	 * @since    1.0.0
	 */
	public function next_headless_get_page_hero_endpoint_callback( $request ) {

		$slug = $request->get_param( 'slug' );

		$hero_data = array();

		$page_id     = '';
		$homepage_id = get_option( 'page_on_front' );

		if ( ! empty( $slug ) ) {

			$page_data = get_page_by_path( $slug );

			if ( ! empty( $page_data ) ) {

				$page_id = $page_data->ID;
			}
		} else {
			$page_id = $homepage_id;
		}

		if ( 'search-results' === $slug ) {
			$page_id = $homepage_id;
		}

		$hero_image        = get_field( 'hero_image', $page_id );
		$custom_page_title = get_field( 'custom_page_title', $page_id );
		$page_description  = get_field( 'page_description', $page_id );

		$use_video_background = get_field( 'use_video_background', $page_id );
		$hero_video_array     = get_field( 'hero_video', $page_id );
		$disable_hero_section = get_field( 'disable_hero_section', $page_id );

		$hero_video = null;

		if ( '1' == $use_video_background ) { //phpcs:ignore
			$hero_video = $hero_video_array;
		}

		$page_title = get_the_title( $page_id );

		if ( ! empty( $custom_page_title ) ) {
			$page_title = $custom_page_title;
		}

		$hero_image_output = array();

		if ( $hero_image && isset( $hero_image['url'] ) ) {
			$hero_image_output = array(
				'large' => $hero_image['url'],
			);
		}

		$hero_img_small = '';

		if ( isset( $hero_image['sizes']['large'] ) && ! empty( $hero_image['sizes']['large'] ) ) {
			$hero_img_small = $hero_image['sizes']['medium_large'];
		} elseif ( isset( $hero_image['url'] ) ) {
			$hero_img_small = $hero_image['url'];
		}

		if ( wp_is_mobile() ) {
			$hero_image_output['large'] = $hero_img_small;
		}

		$hero_image_output['small'] = $hero_img_small;

		$hero_image = $hero_image_output;

		if ( 'search-results' === $slug ) {
			$page_title = __( 'Sökresultat för:', 'nextjs-headless' );
		}

		$hero_data = array(
			'page_title'       => $page_title,
			'hero_image'       => $hero_image,
			'page_description' => $page_description,
			'hero_video'       => $hero_video,
			'disable_hero'     => $disable_hero_section,
			'site_language'    => get_locale(),
		);

		if ( $page_id === $homepage_id ) {

			$hero_tagline = get_field( 'hero_tagline' );
			$hero_title   = get_field( 'hero_title' );
			$hero_cta     = get_field( 'hero_cta' );
			$hero_images  = get_field( 'hero_images' );

			$hero_data['home_hero'] = array(
				'tagline' => $hero_tagline,
				'title'   => $hero_title,
				'cta'     => $hero_cta,
				'images'  => $hero_images,
			);

		}

		$response = rest_ensure_response(
			array(
				'hero_data' => $hero_data,
			)
		);

		return $response;
	}

	/**
	 * Add post excerpt to search results REST APi endpoint.
	 *
	 * @since    1.0.0
	 */
	public function next_headless_add_post_excerpt_to_search_results() {

		register_rest_field(
			'search-result',
			'excerpt',
			array(
				'get_callback' => array( $this, 'next_headless_get_post_excerpt' ),
			)
		);
	}

	/**
	 * Get post excerpt.
	 *
	 * @param    WP_post $post post array.
	 * @since    1.0.0
	 */
	public function next_headless_get_post_excerpt( $post ) {

		$post_excerpt = '';

		$post_data = get_post( $post['id'] );

		if ( ! empty( $post_data ) && ! is_wp_error( $post_data ) ) {
			$post_excerpt = wp_trim_words( wp_strip_all_tags( apply_filters( 'the_content', $post_data->post_content ) ), 80 );
		}

		return $post_excerpt;
	}

	/**
	 * Trigger Next JS revalidate request on post update.
	 *
	 * @param    integer $post_id .
	 * @param    WP_post $post post array.
	 * @since    1.0.0
	 */
	public function next_headless_trigger_revalidate_request( $post_id, $post ) {

		$allowed_post_types = array( 'page', 'post' );

		if ( ! in_array( $post->post_type, $allowed_post_types, true ) ) {
			return;
		}

		$token     = 'mE5tRHwPjbeek0zxy0lsbpfurrKslK7RBfoylySpUtRvmeAirVv7a928QwrtDGjl';
		$page_slug = get_page_uri( $post_id );

		$request_headers = array(
			'Content-Type' => 'application/json',
			'timeout'      => 60,
			'sslverify'    => false,
		);

		$request_url = 'http://209.38.131.64/api/revalidate/?token=' . rawurlencode( $token ) . '&page_slug=' . $page_slug;

		$options = array(
			'headers' => $request_headers,
		);

		wp_remote_post( $request_url, $options );
	}

	/**
	 * Trigger Next JS revalidate request on nav menu update.
	 *
	 * @param    integer $menu_id .
	 * @since    1.0.0
	 */
	public function next_headless_trigger_revalidate_request_on_menu_update( $menu_id ) {

		$menu_locations = get_nav_menu_locations();

		if ( ! empty( $menu_locations ) && isset( $menu_locations['primary_navigation'] ) ) {

			$primary_nav_id = (int) $menu_locations['primary_navigation'];

			if ( $primary_nav_id === (int) $menu_id ) {

				$token = 'mE5tRHwPjbeek0zxy0lsbpfurrKslK7RBfoylySpUtRvmeAirVv7a928QwrtDGjl';

				$request_headers = array(
					'Content-Type' => 'application/json',
					'timeout'      => 60,
					'sslverify'    => false,
				);

				$request_url = 'http://209.38.131.64/api/revalidate/?token=' . rawurlencode( $token ) . '&tag=main_nav';

				$options = array(
					'headers' => $request_headers,
				);

				wp_remote_post( $request_url, $options );
			}
		}
	}
}
