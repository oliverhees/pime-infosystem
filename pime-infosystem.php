<?php
/*
	Plugin Name: Pistis Info System
	Plugin URI: ###
	Description: Displays custom Infos from Pistis Media
	Version: 1.0
	Requires at least: 5.4.1
	Requires PHP: 7.0
	Author: Oliver Hees
	Author URI: ###
	Text Domain: pime-notice
*/

if (! defined('ABSPATH')) {
	exit;
}

class PIME_Admin_Notice {

	/**
	 * The notice message.
	 *
	 * @var    string
	 * @access private
	 * @since  1.1
	 */
	private $message;

	/**
	 * The notice style.
	 *
	 * @var    string
	 * @access private
	 * @since  1.1
	 */
	private $style;

	/**
	 * The notice enable status.
	 *
	 * @var    string
	 * @access private
	 * @since  1.1
	 */
	private $enable;

	public function __construct() {
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_action_link']);
		add_action('admin_menu', [$this, 'add_settings_page'], 100);
		add_action('admin_init', [$this, 'settings_init']);
		add_action('admin_notices', [$this, 'add_admin_notice']);
		add_action('admin_head', [$this, 'print_css']);
	}

	/**
	 * Adds link to plugin settings.
	 *
	 * @since 1.1
	 * @access public
	 * @return array Links
	 */
	public function add_action_link($links) {
		$custom_link = [
			'<a href="' . admin_url('options-general.php?page=admin_notice') . '">Settings</a>',
		];
		return array_merge($custom_link, $links);
	}

	/**
	 * Adds plugin settings page.
	 *
	 * @since 1.1
	 * @access public
	 * @return void
	 */
	public function add_settings_page() {

		add_submenu_page(
			'options-general.php',
			'Admin Notice',
			'Admin Notice',
			'manage_options',
			'admin_notice',
			[$this, 'render_settings_page']
		);

	}

	/**
	 * Registers settings, sections, and fields.
	 *
	 * @since 1.1
	 * @access public
	 * @return void
	 */
	public function settings_init() {

		register_setting(
			'pime_admin_notice_settings_group',
			'pime_admin_notice_msg',
			[$this, 'sanitize_wysiwyg']
		);

		register_setting(
			'pime_admin_notice_settings_group',
			'pime_admin_notice_style',
			[$this, 'sanitize_option']
		);

		register_setting(
			'pime_admin_notice_settings_group',
			'pime_admin_notice_enable',
			[$this, 'sanitize_option']
		);

		add_settings_section(
			'pime_admin_notice_settings',
			'Settings',
			[$this, 'settings_info'],
			'admin_notice'
		);

		add_settings_field(
			'pime_admin_notice_enable',
			'Enable Notice',
			[$this, 'enable_field'],
			'admin_notice',
			'pime_admin_notice_settings'
		);

		add_settings_field(
			'pime_admin_notice_msg',
			'Message',
			[$this, 'message_field'],
			'admin_notice',
			'pime_admin_notice_settings'
		);

		add_settings_field(
			'pime_admin_notice_style',
			'Style',
			[$this, 'style_field'],
			'admin_notice',
			'pime_admin_notice_settings'
		);
	}

	/**
	 * Prints the settings page.
	 *
	 * @since 1.1
	 * @access public
	 * @return void
	 */
	public function render_settings_page() {
		$this->message = get_option('pime_admin_notice_msg');
		$this->style = get_option('pime_admin_notice_style');
		$this->enable = get_option('pime_admin_notice_enable');
		?>
		<div class="wrap">
			<h2>Admin Notice</h2>
			<form method="post" action="options.php">
			<?php
				settings_fields('pime_admin_notice_settings_group');
				do_settings_sections('admin_notice');
				submit_button('Save');
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * Prints the settings page description.
	 *
	 * @since 1.1
	 * @return void
	 */
	public function settings_info() {
		return null;
	}

	/**
	 * Prints the Enable Notice setting field.
	 *
	 * @since 1.1
	 * @access public
	 * @return void
	 */
	public function enable_field() {

		if (isset($this->enable)) {
			$enable = esc_attr($this->enable);
		} else {
			$enable = '';
		}

		$checked = ($enable === '1') ? 'checked="checked"' : '';

		printf(
			'<label><input id="pime_admin_notice_enable" value="1" name="pime_admin_notice_enable" type="checkbox" %s>%s</label><br>',
			$checked,
			__('Enable', 'admin-notice')
		);
	}

	/**
	 * Prints the Message setting field.
	 *
	 * @since 1.1
	 * @access public
	 * @return void
	 */
	public function message_field() {

		if (isset($this->message)) {
			$msg = wp_kses_post($this->message);
		} else {
			$msg = '';
		}

		wp_editor($msg, 'pime_admin_notice_msg', [
			'textarea_name' => 'pime_admin_notice_msg',
			'media_buttons' => false,
			'textarea_rows' => 6,
			'quicktags'     => true,
			'teeny'         => true,
		]);
	}

	/**
	 * Prints the Style setting field.
	 *
	 * @since 1.1
	 * @access public
	 * @return void
	 */
	public function style_field() {

		if (isset($this->style)) {
			$style = esc_attr($this->style);
		} else {
			$style = '';
		}

		$values = ['error', 'info', 'success', 'warning'];
		$options = '';

		foreach ($values as $value) {
			$selected = ($value === $style) ? 'selected="selected"' : '';
			$options .= sprintf(
				'<option value="%s" %s>%s</option>',
				esc_attr($value),
				$selected,
				esc_html(ucfirst($value))
			);
		}

		printf(
			'<select id="pime_admin_notice_style" name="pime_admin_notice_style">%s</select>',
			$options
		);
	}

	/**
	 * Sanitizes radio and select fields.
	 *
	 * @since 1.1
	 * @access public
	 * @param string $input The value to sanitize.
	 * @return string The sanitized value.
	 */
	public function sanitize_option($input) {
		$new_input = '';

		if ($input !== null) {
			$new_input = sanitize_text_field($input);
		}

		return $new_input;
	}

	/**
	 * Sanitizes WYSIWYG fields.
	 *
	 * @since 1.1
	 * @access public
	 * @param string $input The value to sanitize.
	 * @return string The sanitized value.
	 */
	public function sanitize_wysiwyg($input = null) {
		$new_input = '';

		if ($input !== null) {
			$new_input = wp_kses_post($input);
		}

		return $new_input;
	}

	/**
	 * Prints the admin notice.
	 *
	 * @since 1.1
	 * @access public
	 * @return void
	 */
	function add_admin_notice() {
		$msg = get_option('pime_admin_notice_msg');
		$enabled = get_option('pime_admin_notice_enable');
		$style = get_option('pime_admin_notice_style');
		$class = "pime-admin-notice notice notice-{$style}";

		$enabled = apply_filters('admin_notice_enable', $enabled);

		if ($msg !== '' && boolval($enabled) === true) {

			printf(
				'<div class="%s"><p>%s</p></div>',
				esc_attr($class),
				wp_kses_post(wpautop($msg))
			);
		}
	}

	/**
	 * Prints required CSS.
	 *
	 * @since 1.1
	 * @access public
	 * @return void
	 */
	function print_css() {

		echo "<style>
.pime-admin-notice ul {
	list-style: disc;
	margin-left: 2em;
}

.pime-admin-notice blockquote p {
	padding-left: 0.75em;
	border-left: 2px solid #444;
	font-style: italic;
}

.pime-admin-notice p:empty  {
    display: none;
}
</style>\n";
	}
}

new PIME_Admin_Notice();
new Plugin_Updater();