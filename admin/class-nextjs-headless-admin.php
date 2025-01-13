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
	 * Get organization data endpoint.
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
	 * Get organization data endpoint callback.
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
}
