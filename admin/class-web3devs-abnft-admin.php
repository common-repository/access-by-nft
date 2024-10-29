<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://accessbynft.com
 * @since      1.0.0
 *
 * @package    Web3devs_ABNFT
 * @subpackage Web3devs_ABNFT/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Web3devs_ABNFT
 * @subpackage Web3devs_ABNFT/admin
 * @author     Web3devs <wordpress@web3devs.com>
 */
class Web3devs_ABNFT_Admin {

	private $networks = [
		'near:testnet',
		'near:mainnet',
	];

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action('admin_menu', array( $this, 'addPluginAdminMenu' ), 9);
		add_action('admin_init', array( $this, 'registerAndBuildFields' ));
		add_filter('pre_update_option_web3devs_abnft_configured_coins_setting', array($this, 'handleSaveOptions'));
		add_filter('pre_update_option_web3devs_abnft_denial_page_setting', array($this, 'handleSaveDenialPage'));
	}

	public function addPluginAdminMenu() {
		//add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
		add_menu_page( $this->plugin_name, 'Access by NFT', 'administrator', $this->plugin_name, array( $this, 'displayPluginAdminDashboard' ), 'dashicons-superhero', 26 );

		//add_submenu_page( '$parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
		add_submenu_page( $this->plugin_name, 'Access by NFT Settings', 'Settings', 'administrator', $this->plugin_name.'-settings', array( $this, 'displayPluginAdminSettings' ));
	}

	public function displayPluginAdminDashboard() {
		require_once 'partials/'.$this->plugin_name.'-admin-display.php';
  	}

	public function displayPluginAdminSettings() {
		if(isset($_GET['error_message'])){
			add_action('admin_notices', array($this,'web3devsABNFTSettingsMessages'));
			do_action('admin_notices', wp_kses($_GET['error_message']));
		}
		$coins = get_option('web3devs_abnft_configured_coins_setting');

		require_once 'partials/'.$this->plugin_name.'-admin-settings-display.php';
	}

	public function web3devsABNFTSettingsMessages($error_message){
		switch ($error_message) {
			case '1':
				$message = __( 'There was an error adding this setting. Please try again.  If this persists, shoot us an email.', 'my-text-domain' );
				$err_code = esc_attr( 'web3devs_abnft_configured_coins_setting' );
				$setting_field = 'web3devs_abnft_configured_coins_setting';
				break;
		}
		$type = 'error';
		add_settings_error(
			$setting_field,
			$err_code,
			$message,
			$type
		);
	}

	public function registerAndBuildFields() {
		/**
		 * First, we add_settings_section. This is necessary since all future settings must belong to one.
		 * Second, add_settings_field
		 * Third, register_setting
		 */
		add_settings_section(
			// ID used to identify this section and with which to register options
			'web3devs_abnft_general_section',
			// Title to be displayed on the administration page
			'',
			// Callback used to render the description of the section
			array( $this, 'web3devs_abnft_display_general_account' ),
			// Page on which to add this section of options
			'web3devs_abnft_general_settings'
		);
		add_settings_field(
			'web3devs_abnft_configured_coins_setting',
			'Add Token',
			array( $this, 'web3devs_abnft_render_configured_coins_setting_field' ),
			'web3devs_abnft_general_settings',
			'web3devs_abnft_general_section',
			array (
				'type'      		=> 'input',
				'subtype'   		=> 'text',
				'id'    			=> 'web3devs_abnft_configured_coins_setting',
				'name'      		=> 'web3devs_abnft_configured_coins_setting',
				'required' 			=> 'true',
				'get_options_list' 	=> '',
				'value_type'		=> 'normal',
				'wp_data' 			=> 'option'
			),
		);
		register_setting(
			'web3devs_abnft_general_settings',
			'web3devs_abnft_configured_coins_setting',
			[
				'type' => 'array'
			]
		);

		add_settings_section(
			// ID used to identify this section and with which to register options
			'web3devs_abnft_denial_page_section',
			// Title to be displayed on the administration page
			'',
			// Callback used to render the description of the section
			array( $this, 'web3devs_abnft_display_denial_page' ),
			// Page on which to add this section of options
			'web3devs_abnft_denial_page_settings'
		);
		add_settings_field(
			'web3devs_abnft_denial_page_setting',
			'Set denial page',
			array( $this, 'web3devs_abnft_render_denial_page_settings_field' ),
			'web3devs_abnft_denial_page_settings',
			'web3devs_abnft_denial_page_section',
			array (
				'type'      		=> 'input',
				'subtype'   		=> 'text',
				'id'    			=> 'web3devs_abnft_denial_page_setting',
				'name'      		=> 'web3devs_abnft_denial_page_setting',
				'required' 			=> 'true',
				'get_options_list' 	=> '',
				'value_type'		=> 'normal',
				'wp_data' 			=> 'option'
			),
		);
		register_setting(
			'web3devs_abnft_denial_page_settings',
			'web3devs_abnft_denial_page_setting',
			[
				'type' => 'string'
			]
		);

		add_settings_section(
			// ID used to identify this section and with which to register options
			'web3devs_abnft_api_key_section',
			// Title to be displayed on the administration page
			'',
			// Callback used to render the description of the section
			array( $this, 'web3devs_abnft_display_api_key' ),
			// Page on which to add this section of options
			'web3devs_abnft_api_key_settings'
		);
		add_settings_field(
			'web3devs_abnft_api_key_setting',
			'Set API KEY',
			array( $this, 'web3devs_abnft_render_api_key_settings_field' ),
			'web3devs_abnft_api_key_settings',
			'web3devs_abnft_api_key_section',
			array (
				'type'      		=> 'input',
				'subtype'   		=> 'text',
				'id'    			=> 'web3devs_abnft_api_key_setting',
				'name'      		=> 'web3devs_abnft_api_key_setting',
				'required' 			=> 'true',
				'get_options_list' 	=> '',
				'value_type'		=> 'normal',
				'wp_data' 			=> 'option'
			),
		);
		register_setting(
			'web3devs_abnft_api_key_settings',
			'web3devs_abnft_api_key_setting',
			[
				'type' => 'string'
			]
		);
	}

	public function web3devs_abnft_display_general_account() {
		// echo '<p>These settings apply to all Plugin Name functionality.</p>';
	}

	public function web3devs_abnft_display_denial_page() {
		// echo '<p>These settings apply to all Plugin Name functionality.</p>';
	}

	private function validateContractAddress($address) {
		return true;

		// if(!preg_match('/^0\.0\.[0-9]+\:[0-9\*]+$/m', $address)) {
		// 	return false;
		// }

		// return true;
	}

	public function handleSaveOptions($value) {
		if (isset($value['new'])) { //adding new coin
			$coins = get_option('web3devs_abnft_configured_coins_setting');
			if (empty($coins)) {
				$coins = [];
			}

			$value['new']['symbol'] = sanitize_text_field($value['new']['symbol']);
			$value['new']['contract'] = sanitize_text_field($value['new']['contract']);
			$value['new']['network'] = sanitize_text_field($value['new']['network']);

			foreach ($coins as $coin) {
				if ($coin['contract'] === $value['new']['contract'] && $coin['network'] === $value['new']['network']) {
					add_settings_error(
						'web3devs_abnft_general_settings',
						esc_attr('settings_updated'),
						'Token with that token id already exists',
						'error'
					);
					return $coins;
				}
			}

			if (empty($value['new']['symbol'])) {
				add_settings_error(
					'web3devs_abnft_general_settings',
					esc_attr('settings_updated'),
					'Token Symbol is required!',
					'error'
				);
				return $coins;
			}

			if (!$this->validateContractAddress($value['new']['contract'])) {
				add_settings_error(
					'web3devs_abnft_general_settings',
					esc_attr('settings_updated'),
					'Incorrect token id! See NOTES below!',
					'error'
				);
				return $coins;
			}

			if (empty($value['new']['network']) || !in_array($value['new']['network'], $this->networks)) {
				add_settings_error(
					'web3devs_abnft_general_settings',
					esc_attr('settings_updated'),
					'Network is required!',
					'error'
				);
				return $coins;
			}
			$coins[] = $value['new'];

			return $coins;
		}

		if (isset($value['remove'])) { //removing a coin
			$coins = get_option('web3devs_abnft_configured_coins_setting');
			foreach ($coins as $key => $coin) {
				if ($coin['contract'] === $value['remove']['contract']) {
					unset($coins[$key]);
				}
			}

			return $coins;
		}
		
		return $value;
	}

	public function handleSaveDenialPage($value) {
		return $value;
	}

	public function web3devs_abnft_render_configured_coins_setting_field($args) {
		?>
		</tr>
		<tr>
			<table>
				<thead>
					<tr>
						<td class="has-row-actions column-primary">
							<label for="<?php echo esc_attr($args['id'].'_new_network'); ?>">Network</label>
						</td>
						<td class="has-row-actions column-primary">
							<label for="<?php echo esc_attr($args['id'].'_new_contract'); ?>">Token ID</label>
						</td>
						<td class="has-row-actions column-primary">
							<label for="<?php echo esc_attr($args['id'].'_new_symbol'); ?>">Label</label>
						</td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="has-row-actions column-primary">
							<select id="<?php echo esc_attr($args['id'].'_new_network'); ?>" name="<?php echo esc_attr($args['name'].'[new][network]'); ?>">
								<option value=""></option>
								<option value="near:testnet">NEAR Testnet</option>
								<option value="near:mainnet">NEAR Mainnet</option>
							</select>
						</td>
						<td class="has-row-actions column-primary">
							<input id="<?php echo esc_attr($args['id'].'_new_contract'); ?>" type="text" name="<?php echo esc_attr($args['name'].'[new][contract]'); ?>" value="">
						</td>
						<td class="has-row-actions column-primary">
							<input id="<?php echo esc_attr($args['id'].'_new_symbol'); ?>" type="text" name="<?php echo esc_attr($args['name'].'[new][symbol]'); ?>" value="">
						</td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="3">
							<strong>NOTE!</strong>
							<div>
								<p>For individual tokens, use token id in format <strong>TOKEN_ID:SERIAL_NUMBER</strong>, ex. <strong>cowboytest.mintspace2.testnet:108</strong></p>
							</div>
							<div>
								You can find the token id in your Wallet's address bar.
							</div>
						</td>
					</tr>
				</tfoot>
			</table>
		</tr>
		<?php
	}

	public function web3devs_abnft_render_denial_page_settings_field($args) {
		$selected = get_option('web3devs_abnft_denial_page_setting');
		$pages = get_pages();
		?>
		<tr>
			<td class="has-row-actions column-primary">
				<label for="<?php echo esc_attr($args['id']); ?>">Denial page</label>
				<select id="<?php echo esc_attr($args['id']); ?>" name="<?php echo esc_attr($args['name']); ?>">
					<option value="">Select page</option>
				<?php foreach ($pages as $page): ?>
					<option value="<?php echo esc_attr($page->ID); ?>" <?php echo $selected == $page->ID ? 'selected' : '' ?>><?php echo esc_html($page->post_title); ?></option>
				<?php endforeach ;?>
				</select>
			</td>
		</tr>
		<?php
	}

	public function web3devs_abnft_render_api_key_settings_field($args) {
		$value = get_option('web3devs_abnft_api_key_setting');
		$pages = get_pages();
		?>
		<tr>
			<td class="has-row-actions column-primary">
				<label for="<?php echo esc_attr($args['id']); ?>">API KEY</label>
				<input id="<?php echo esc_attr($args['id']); ?>" type="text" name="<?php echo esc_attr($args['name']); ?>" value="<?php echo esc_attr($value); ?>">
			</td>
		</tr>
		<?php
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/web3devs-abnft-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/web3devs-abnft-admin.js', array( 'jquery' ), $this->version, false );
	}
}
