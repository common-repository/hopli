<?php
/**
* Plugin Name: Hopli
* Version: 0.0.2
* Requires at least: 5.8.1
* Requires PHP: 7.2
* Tested up to: 5.8.1
* Author: Hopli
* Author URI: https://hopli.io/
* Description: Allows you to insert the Hopli window to start making donations !
* License: GPLv2 or later
*/

/*  Copyright 2019 WPBeginner

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
* Hopli Class
*/
class Hopli {
    /**
     * Constructor
     */
    public function __construct()
    {
        $file_data = get_file_data( __FILE__, array( 'Version' => 'Version' ) );
        // Plugin Details
        $this->plugin                           = new stdClass;
        $this->plugin->name                     = 'hopli'; // Plugin Folder
        $this->plugin->displayName              = 'Hopli'; // Plugin Name
        $this->plugin->version                  = $file_data['Version'];
        $this->plugin->folder                   = plugin_dir_path( __FILE__ );
        $this->plugin->url                      = plugin_dir_url( __FILE__ );

				$this->plugin->db_welcome_dismissed_key = $this->plugin->name . '_welcome_dismissed_key';

        // Hooks
        add_action( 'admin_init', array( &$this, 'registerSettings' ) );
        add_action( 'admin_enqueue_scripts', array( &$this, 'initCodeMirror' ) );
        add_action( 'admin_menu', array( &$this, 'adminPanelsAndMetaBoxes' ) );
				add_action( 'admin_notices', array( &$this, 'dashboardNotices' ) );

				add_action( 'wp_ajax_' . $this->plugin->name . '_dismiss_dashboard_notices', array( &$this, 'dismissDashboardNotices' ) );

        // Frontend Hooks
        add_action( 'wp_head', array( &$this, 'hopliInsertHeader' ) );
    }

    /**
    * Register Settings that are the clientId and appId
    */
    function registerSettings() {
        register_setting( $this->plugin->name, 'hopli_client_id', 'trim' );
        register_setting( $this->plugin->name, 'hopli_app_id', 'trim' );
    }

    /**
	 * Enqueue and initialize CodeMirror for the form fields.
	 */
		function initCodeMirror() {
			// Make sure that we don't fatal error on WP versions before 4.9.
			if ( ! function_exists( 'wp_enqueue_code_editor' ) ) {
				return;
			}

			global $pagenow;

			if ( ! ( 'options-general.php' === $pagenow && isset( $_GET['page'] ) && 'hopli' === $_GET['page'] ) ) {
				return;
			}

			$editor_args = array( 'type' => 'text/html' );

			if ( ! current_user_can( 'unfiltered_html' ) || ! current_user_can( 'manage_options' ) ) {
				$editor_args['codemirror']['readOnly'] = true;
			}

			// Enqueue code editor and settings for manipulating HTML.
			$settings = wp_enqueue_code_editor( $editor_args );

			// Bail if user disabled CodeMirror.
			if ( false === $settings ) {
				return;
			}

			// Custom styles for the form fields.
			$styles = '.CodeMirror{ border: 1px solid #ccd0d4; }';

			wp_add_inline_style( 'code-editor', $styles );

			wp_add_inline_script( 'code-editor', sprintf( 'jQuery( function() { wp.codeEditor.initialize( "hopli_client_id", %s ); } );', wp_json_encode( $settings ) ) );
			wp_add_inline_script( 'code-editor', sprintf( 'jQuery( function() { wp.codeEditor.initialize( "hopli_app_id", %s ); } );', wp_json_encode( $settings ) ) );
		}

    /**
	* Register the plugin settings panel
	*/
	function adminPanelsAndMetaBoxes() {
		add_submenu_page( 'options-general.php', $this->plugin->displayName, $this->plugin->displayName, 'manage_options', $this->plugin->name, array( &$this, 'adminPanel' ) );
	}

    /**
	* Output the Administration Panel
	* Save POSTed data from the Administration Panel into a WordPress option
	*/
	function adminPanel() {
		/*
		 * Only users with manage_options can access this page.
		 *
		 * The capability included in add_settings_page() means WP should deal
		 * with this automatically but it never hurts to double check.
		 */
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.', 'hopli' ) );
		}

		// only users with `unfiltered_html` can edit scripts.
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$this->errorMessage = '<p>' . __( 'Sorry, only have read-only access to this page. Ask your administrator for assistance editing.', 'hopli' ) . '</p>';
		}

		// Save Settings
		if ( isset( $_REQUEST['submit'] ) ) {
			// Check permissions and nonce.
			if ( ! current_user_can( 'unfiltered_html' ) ) {
				// Can not edit scripts.
				wp_die( __( 'Sorry, you are not allowed to edit this page.', 'hopli' ) );
			} elseif ( ! isset( $_REQUEST[ $this->plugin->name . '_nonce' ] ) ) {
				// Missing nonce
				$this->errorMessage = __( 'nonce field is missing. Settings NOT saved.', 'hopli' );
			} elseif ( ! wp_verify_nonce( $_REQUEST[ $this->plugin->name . '_nonce' ], $this->plugin->name ) ) {
				// Invalid nonce
				$this->errorMessage = __( 'Invalid nonce specified. Settings NOT saved.', 'hopli' );
			} elseif ( ! preg_match( "/^[a-fA-F0-9]{24}$/", sanitize_text_field( $_REQUEST['hopli_client_id'] ) ) ) {
				// Validate the client ID
				$this->errorMessage = __( 'This does not look like a valid client ID.', 'hopli' );
			} elseif ( ! preg_match( "/^[a-fA-F0-9]{24}$/", sanitize_text_field( $_REQUEST['hopli_app_id'] ) ) ) {
				// Validate the app ID
				$this->errorMessage = __( 'This does not look like a valid app ID.', 'hopli' );
			} else {
				// Save
				// $_REQUEST has already been slashed by wp_magic_quotes in wp-settings
				// so do nothing before saving
				update_option( 'hopli_client_id', sanitize_text_field( wp_unslash( $_REQUEST['hopli_client_id'] ) ) );
				update_option( 'hopli_app_id', sanitize_text_field( wp_unslash( $_REQUEST['hopli_app_id'] ) ) );
				update_option( $this->plugin->db_welcome_dismissed_key, 1 );
				$this->message = __( 'Settings Saved.', 'hopli' );
			}
		}

		// Get latest settings
		$this->settings = array(
			'hopli_client_id' => esc_html( wp_unslash( get_option( 'hopli_client_id' ) ) ),
			'hopli_app_id' => esc_html( wp_unslash( get_option( 'hopli_app_id' ) ) ),
		);

		// Load Settings Form
		include_once( $this->plugin->folder . '/views/settings.php' );
	}

    /**
	 * Show relevant notices for the plugin
	 */
	function dashboardNotices() {
		global $pagenow;

		if (
			! get_option( $this->plugin->db_welcome_dismissed_key )
			&& current_user_can( 'manage_options' )
		) {
			if ( ! ( 'options-general.php' === $pagenow && isset( $_GET['page'] ) && 'hopli' === $_GET['page'] ) ) {
				$setting_page = admin_url( 'options-general.php?page=' . $this->plugin->name );
				// load the notices view
				include_once( $this->plugin->folder . '/views/dashboard-notices.php' );
			}
		}
	}

    /**
     * Insert in the head the script for Hopli to work
     */
    public function hopliInsertHeader() {
			global $wp;

			// check if woo commerce is active
			if ( in_array( 'woocommerce/woocommerce.php', get_option('active_plugins'))) {
				// to remove silent error when we are not on the page and 'WC()->query->get_query_vars()' is not populated
				if ( array_key_exists( WC()->query->get_query_vars()[ 'order-received' ], $wp->query_vars ) ) {
					$is_order_received = $wp->query_vars[ WC()->query->get_query_vars()[ 'order-received' ] ];
				}
			}

			// enqueue the scripts
			wp_enqueue_script( 'hopli-js', esc_html( 'https://cdn.hopli.io/bundle.js' ), array(), null );
			wp_enqueue_style( 'hopli-css', esc_html( 'https://cdn.hopli.io/bundle.css' ), array(), null );

			// add filter to change the script attributes
			add_filter('script_loader_tag', array( &$this, 'addDefer' ), 10, 2);


			?>
				<meta name="hopli-client-id" content="<?php echo esc_html( $this->output( 'hopli_client_id' ) ); ?>">
				<meta name="hopli-app-id" content="<?php echo esc_html( $this->output( 'hopli_app_id' ) ); ?>">
				<?php
					if (isset ( $is_order_received ) ) {
						?>
							<meta name="is-order-received-url" content="true">
						<?php
					}
				?>
			<?php
    }

    /**
    * Outputs the given setting, if conditions are met
    *
    * @param string $setting Setting Name
    * @return output
    */
    private function output( $setting ) {
        // Ignore admin, feed, robots or trackbacks
        if ( is_admin() || is_feed() || is_robots() || is_trackback() ) {
            return;
        }

        // provide the opportunity to Ignore Hopli - both headers and footers via filters
        if ( apply_filters( 'disable_hopli', false ) ) {
            return;
        }

        // Get meta
        $meta = get_option( $setting );
        if ( empty( $meta ) ) {
            return;
        }
        if ( trim( $meta ) === '' ) {
            return;
        }

        echo esc_html( wp_unslash( $meta ) );
    }

		/**
		 * Add defer attributes to handles in the $deferrable_handles variable.
		 * 
		 * @param string $tag The <script> tag for the enqueued script.
		 * @param string $handle The script's registered handle.
		 */
		public function addDefer( $tag, $handle ) {
			// Add multiple defers
			$deferrable_handles = [
				'hopli-js',
			];

			if( in_array( $handle, $deferrable_handles ) ) {
				$tag = str_replace( ' src', ' defer="defer" src', $tag );
			}

			return $tag;
		}

		/**
		 * Only useful for debugging purpose
		 * 
		 * @param string $what Anything
		 */
		function echo_log( $what ) {
			echo '<pre>'.esc_html ( print_r( $what, true ) ).'</pre>';
		}
}

$hopli = new Hopli();