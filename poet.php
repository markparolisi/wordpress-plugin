<?php
/**
 * Plugin Name: Po.et
 * Plugin URI:  https://github.com/poetapp/wordpress-plugin
 * Description: Automatically post to Po.et from WordPress using Frost
 * Version:     1.0.1
 * Version:     1.0.1
 * Author URI:  https://po.et
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: poet
 * Domain Path: /languages
 *
 * @package Poet
 */

namespace Poet;

defined( 'ABSPATH' ) or exit;

/**
 * Class Plugin
 *
 * @package Poet
 */
class Plugin {

	/**
	 * Holds plugin file location
	 *
	 * @var string
	 */
	private $plugin_path;

	/**
	 * Hold the singleton instance
	 *
	 * @var \Poet\Plugin
	 */
	private static $instance;

	/**
	 * Initialization method
	 *
	 * Used to make an object of the plugin class
	 *
	 * Singleton
	 */
	public static function init() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Poet constructor.
	 */
	private function __construct() {
		$dir = dirname( __FILE__ );
		require_once( $dir . '/includes/class-poet-consumer.php' );

		$this->plugin_path = plugin_basename( __FILE__ );

		register_activation_hook( $this->plugin_path, [ $this, 'activate' ] );
		register_deactivation_hook( $this->plugin_path, [ $this, 'deactivate' ] );
		register_uninstall_hook( $this->plugin_path, [ $this, 'uninstall' ] );
		add_filter( 'plugin_action_links_' . $this->plugin_path, [ $this, 'add_settings_link' ] );
		add_action( 'poet_set_default_values_on_activation', [ $this, 'set_default_values' ] );
		add_action( 'admin_menu', [ $this, 'add_options_page' ] );
		add_action( 'admin_init', [ $this, 'register_setting' ] );
		add_action( 'save_post', [ $this, 'post_article' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'styles' ] );
		add_shortcode( 'poet-badge', [ $this, 'poet_badge_handler' ] );
	}


	/**
	 * Activation method
	 * Runs on plugin activation
	 */
	public function activate() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Setting default values action to set settings default values on activation/reactivation.
		do_action( 'poet_set_default_values_on_activation' );
	}


	/**
	 * Deactivation method
	 * Runs on plugin deactivation
	 */
	public function deactivate() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		unregister_setting(
			'poet',
			'poet_option',
			[ $this, 'sanitize' ]
		);
	}

	/**
	 * Uninstallation method
	 *
	 * Runs on plugin deletion
	 */
	public function uninstall() {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			return;
		}

		delete_option( 'poet_option' );
	}

	/**
	 * Setting plugin options default values
	 */
	public function set_default_values() {
		$default = [
			'api_url' => 'https://api.frost.po.et/works',
			'token'   => '',
			'active'  => 1,
		];
		update_option( 'poet_option', $default );
	}

	/**
	 * Plugin settings page form
	 */
	public function create_options_page() {
		?>
		<div class="wrap">

			<form method="post" action="options.php">
				<?php
				settings_fields( 'poet' );
				do_settings_sections( $this->plugin_path );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Registration of settings page in WordPress options menu
	 */
	public function add_options_page() {
		add_options_page(
			__( 'Po.et', 'poet-wordpress-plugin' ),
			__( 'Po.et', 'poet-wordpress-plugin' ),
			'manage_options',
			$this->plugin_path,
			[ $this, 'create_options_page' ]
		);
	}

	/**
	 * Adding Settings link in plugins page
	 * The link redirects to plugin settings page
	 *
	 * @param array $links Include link to new settings page in menus.
	 *
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$url           = menu_page_url( plugin_basename( __FILE__ ), false );
		$settings_link = '<a href="' . $url . '">' . __( 'Settings', 'poet-wordpress-plugin' ) . '</a>';
		array_push( $links, $settings_link );

		return $links;
	}


	/**
	 * Settings option fields registration
	 */
	public function register_setting() {
		register_setting(
			'poet',
			'poet_option',
			[ $this, 'sanitize' ]
		);

		add_settings_section(
			'poet_setting_section_id',
			'',
			[ $this, 'print_section_info' ],
			$this->plugin_path
		);

		add_settings_field(
			'author',
			__( 'Author Name', 'poet-wordpress-plugin' ),
			[ $this, 'author_callback' ],
			$this->plugin_path,
			'poet_setting_section_id'
		);

		add_settings_field(
			'api_url',
			__( 'API URL', 'poet-wordpress-plugin' ),
			[ $this, 'api_url_callback' ],
			$this->plugin_path,
			'poet_setting_section_id'
		);

		add_settings_field(
			'token', __( 'API Token', 'poet-wordpress-plugin' ), [
				$this,
				'token_callback',
			], $this->plugin_path, 'poet_setting_section_id'
		);

		add_settings_field(
			'active',
			__( 'Automatically push posts to Po.et on save?', 'poet-wordpress-plugin' ),
			[ $this, 'active_callback' ],
			$this->plugin_path,
			'poet_setting_section_id'
		);
	}

	/**
	 * Prints instruction string in top of settings page
	 */
	public function print_section_info() {
		?>
		<a href="https://www.po.et/" target="_blank">
			<img src="<?php echo esc_url( plugins_url( 'poet-wordpress-plugin/assets/images/logo.svg', dirname( __FILE__ ) ) ); ?>"/>
		</a>
		<p>
			<strong>
				Enter Author Name, API URL, and Token (this will return to default value if the plugin deactivated
				and reactivated again)
			</strong>
		</p>
		<?php
	}

	/**
	 * Sanitizes option fields data
	 *
	 * @param array $input The form input.
	 *
	 * @return array
	 */
	public function sanitize( $input ) {
		$new_input = [];

		if ( isset( $input['author'] ) ) {
			$new_input['author'] = sanitize_text_field( $input['author'] );
		}

		if ( isset( $input['api_url'] ) ) {
			$new_input['api_url'] = esc_url_raw( $input['api_url'] );
		}

		if ( isset( $input['token'] ) ) {
			$new_input['token'] = sanitize_text_field( $input['token'] );
		}

		if ( isset( $input['active'] ) ) {
			$new_input['active'] = (int) $input['active'];
		}

		return $new_input;
	}

	/**
	 * Returns Author field input
	 */
	public function author_callback() {
		printf(
			'<input type="text" id="author" name="poet_option[author]" value="%s" />',
			isset( get_option( 'poet_option' )['author'] ) ? esc_attr( get_option( 'poet_option' )['author'] ) : ''
		);
	}

	/**
	 * Returns API URL field input
	 */
	public function api_url_callback() {
		printf(
			'<input type="text" id="api_url" name="poet_option[api_url]" value="%s"  size="50" required />',
			isset( get_option( 'poet_option' )['api_url'] ) ? esc_attr( get_option( 'poet_option' )['api_url'] ) : ''
		);
	}

	/**
	 * Returns Token field input
	 */
	public function token_callback() {
		printf(
			'<input type="text" id="token" name="poet_option[token]" value="%s" size="50" required />',
			isset( get_option( 'poet_option' )['token'] ) ? esc_attr( get_option( 'poet_option' )['token'] ) : ''
		);
	}

	/**
	 * Returns activation checkbox input
	 */
	public function active_callback() {
		$checked = isset( get_option( 'poet_option' )['active'] ) ? 1 : 0;
		echo '<input type="checkbox" id="active" name="poet_option[active]" ' . checked( 1, $checked, false ) . ' />';
	}

	/**
	 * Called on WordPress post saving (insertion/modifications)
	 *
	 * @param string|int $post_id WordPress post ID.
	 */
	public function post_article( $post_id ) {

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'publish_post' ) ) {
			return;
		}

		$active  = isset( get_option( 'poet_option' )['active'] ) ? 1 : 0;
		$api_url = ! empty( get_option( 'poet_option' )['api_url'] ) ? 1 : 0;
		$token   = ! empty( get_option( 'poet_option' )['token'] ) ? 1 : 0;
		$post    = get_post( $post_id );

		// Checking if plugin is activated in its settings page and the post status is publish to make sure it is not just a draft.
		if ( ! $active || ! $api_url || ! $token || 'publish' !== $post->post_status ) {
			return;
		}
		// Getting API credentials and author name set in plugin settings page.
		$author = isset( get_option( 'poet_option' )['author'] ) ? get_option( 'poet_option' )['author'] : '';
		$url    = isset( get_option( 'poet_option' )['api_url'] ) ? get_option( 'poet_option' )['api_url'] : '';
		$token  = isset( get_option( 'poet_option' )['token'] ) ? get_option( 'poet_option' )['token'] : '';

		$consumer = new \Poet\Consumer( $author, $url, $token, $post );

		// Posting the article to the API.
		try {
			$response              = $consumer->consume();
			$decoded_response_body = json_decode( $response['body'] );

			// Adding initial empty meta key for the poet work id.
			update_post_meta( $post_id, 'poet_work_id', '' );

			// Checking if the returned response body is a valid JSON string.
			if ( json_last_error() !== JSON_ERROR_SYNTAX
				 && is_object( $decoded_response_body )
				 && property_exists( $decoded_response_body, 'workId' ) ) {

				// Creating or updating poet work id meta to the returned work id.
				update_post_meta( $post_id, 'poet_work_id', $decoded_response_body->workId );
			}
		} catch ( \Exception $exception ) {
			// do nothing for now.
		}

	}

	/**
	 * Includes the badge stylesheets in template header
	 */
	public function styles() {
		if ( is_admin() ) {
			return;
		}

		$post = get_post();
		if ( ! is_singular() || ! has_shortcode( $post->post_content, 'poet-badge' ) ) {
			return;
		}

		wp_register_style( 'poet-badge-font', plugins_url( 'poet-wordpress-plugin/assets/styles/poet.css', dirname( __FILE__ ) ), [], false, 'screen' );
		wp_enqueue_style( 'poet-badge-font' );
		wp_register_style( 'poet-badge-style', 'https://fonts.googleapis.com/css?family=Roboto', [], false, 'screen' );
		wp_enqueue_style( 'poet-badge-style' );
	}

	/**
	 * Handles Verified by Po.et badge shortcode
	 */
	public function poet_badge_handler() {
		$shortcode_markup = '';
		$post             = get_post();

		if ( ! empty( $post ) ) {
			$quill_image_url       = plugins_url( 'poet-wordpress-plugin/assets/images/quill.svg', dirname( __FILE__ ) );
			$post_publication_date = get_the_modified_time( 'F jS Y, H:i', $post );
			$work_id               = get_post_meta( $post->ID, 'poet_work_id', true );

			ob_start();
			include_once dirname( __FILE__ ) . '/assets/templates/poet-badge-template.php';

			$shortcode_markup = ob_get_clean();
		}

		return $shortcode_markup;
	}
}

\Poet\Plugin::init();
