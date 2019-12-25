<?php
/**
 * Settings class file.
 *
 * @package WordPress Plugin Template/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class.
 */
class Easy_Symlinks_Settings {

	/**
	 * The single instance of Easy_Symlinks_Settings.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $instance = null;

	/**
	 * The main plugin object.
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 *
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	/**
	 * Load sanitation.
	 *
	 * @return string
	 */
	public function sanitize_thishtml() {
		$sanitisation = new Easy_Symlinks_Admin_API();
		return $sanitisation;
	}

	/**
	 * Constructor function.
	 *
	 * @param object $parent Parent object.
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;

		$this->base = 'caes_';

		// Initialise settings.
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings page to menu.
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page.
		add_filter(
			'plugin_action_links_' . plugin_basename( $this->parent->file ),
			array(
				$this,
				'add_settings_link',
			)
		);

		// Configure placement of plugin settings page. See readme for implementation.
		add_filter( $this->base . 'menu_settings', array( $this, 'configure_settings' ) );
	}

	/**
	 * Initialise settings
	 *
	 * @return void
	 */
	public function init_settings() {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu
	 *
	 * @return void
	 */
	public function add_menu_item() {

		$args = $this->menu_settings();

		// Do nothing if wrong location key is set.
		if ( is_array( $args ) && isset( $args['location'] ) && function_exists( 'add_' . $args['location'] . '_page' ) ) {
			switch ( $args['location'] ) {
				case 'options':
				case 'submenu':
					$page = add_submenu_page( $args['parent_slug'], $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'] );
					break;
				case 'menu':
					$page = add_menu_page( $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'], $args['icon_url'], $args['position'] );
					break;
				default:
					return;
			}
		}
	}

	/**
	 * Prepare default settings page arguments
	 *
	 * @return mixed|void
	 */
	private function menu_settings() {
		return apply_filters(
			$this->base . 'menu_settings',
			array(
				'location'    => 'options', // Possible settings: options, menu, submenu.
				'parent_slug' => 'options-general.php',
				'page_title'  => __( 'Easy Symlink Settings', 'easy-symlinks' ),
				'menu_title'  => __( 'Easy Symlink Settings', 'easy-symlinks' ),
				'capability'  => 'manage_options',
				'menu_slug'   => $this->parent->token . '_settings',
				'function'    => array( $this, 'settings_page' ),
				'icon_url'    => '',
				'position'    => null,
			)
		);
	}

	/**
	 * Container for settings page arguments
	 *
	 * @param array $settings Settings array.
	 *
	 * @return array
	 */
	public function configure_settings( $settings = array() ) {
		return $settings;
	}

	/**
	 * Add settings link to plugin list table
	 *
	 * @param  array $links Existing links.
	 * @return array        Modified links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=' . $this->parent->token . '_settings&tab=add">' . __( 'Settings', 'easy-symlinks' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	/**
	 * Build settings fields
	 *
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {

		$links          = new Easy_Symlinks_Functions();
		$symlinks       = $links->display_symlinks();
		$symlinks_array = $links->get_symlinks();

		$settings['add'] = array(
			'title'       => __( 'Add Symlinks', 'easy-symlinks' ),
			'description' => '',
			'fields'      => array(
				array(
					'id'          => 'target',
					'label'       => __( 'Target', 'easy-symlinks' ),
					'description' => __( 'This should be existing, non-version controlled and in a writable path by your host like the wp-content/uploads. This should be a relative path to where your link is created. <br> ./uploads/cache if link is from /wp-content/cache <br> ./wp-content/uploads/rootfolder if link is from /rootfolder ', 'easy-symlinks' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'eg: ./uploads/cache', 'easy-symlinks' ),
				),
				array(
					'id'          => 'link',
					'label'       => __( 'Link', 'easy-symlinks' ),
					'description' => __( 'This should be non-existing as this one will be created. If tthe folder is existing, contents should be moved to the target first before symlinking.', 'easy-symlinks' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'eg: /wp-content/cache', 'easy-symlinks' ),
				),
			),
		);

		$settings['delete'] = array(
			'title'       => __( 'Delete Symlinks', 'easy-symlinks' ),
			'description' => __( 'Select the symlink pair that you want  to delete', 'easy-symlinks' ),
			'fields'      => array(
				array(
					'id'          => 'symlink_list_lastdelete',
					'label'       => __( 'Active symlinks', 'easy-symlinks' ),
					'description' => '',
					'type'        => 'select_multi',
					'options'     => $symlinks_array,
				),
			),
		);

		$settings = apply_filters( $this->parent->token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_settings() {
		if ( is_array( $this->settings ) ) {
			// Check posted/selected tab.
			$current_section = '';

			if ( isset( $_POST['tab'] ) ) {
				if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tab'] ) ) ) ) {
					$current_section = sanitize_text_field( wp_unslash( $_POST['tab'] ) );
				}
			} else {
				if ( isset( $_GET['tab'] ) && sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) {
					$current_section = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
				}
			}

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section !== $section ) {
					continue;
				}

				// Add section to page.
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->token . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field.
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field.
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->token . '_settings', $option_name, $validation );

					// Add field to page.
					add_settings_field(
						$field['id'],
						$field['label'],
						array( $this->parent->admin, 'display_field' ),
						$this->parent->token . '_settings',
						$section,
						array(
							'field'  => $field,
							'prefix' => $this->base,
						)
					);
				}

				if ( ! $current_section ) {
					break;
				}
			}
		}
	}

	/**
	 * Settings section.
	 *
	 * @param array $section Array of section ids.
	 * @return void
	 */
	public function settings_section( $section ) {
		$html         = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		$sanitisation = new Easy_Symlinks_Admin_API();
		echo wp_kses( $html, $sanitisation->allowed_htmls );
	}

	/**
	 * Load settings page content.
	 *
	 * @return void
	 */
	public function settings_page() {

		$links = new Easy_Symlinks_Functions();

		// Build page HTML.
		$nonce     = sanitize_text_field( wp_create_nonce( plugin_basename( __FILE__ ) ) );
		$html      = '<div class="wrap" id="' . $this->parent->token . '_settings">' . "\n";
			$html .= '<h2>' . __( 'Easy Symlink Settings', 'easy-symlinks' ) . '</h2>' . "\n";

			$tab = '';

		if ( ! isset( $_GET['caes_nonce'] ) ) {
			if ( isset( $_GET['page'] ) ) {
				if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) ) {
					if ( isset( $_GET['tab'] ) && sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) {
						$tab .= sanitize_text_field( wp_unslash( $_GET['tab'] ) );
					}
				}
			}
		} else {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['caes_nonce'] ) ) ) ) {
				if ( isset( $_GET['tab'] ) && sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) {
					$tab .= sanitize_text_field( wp_unslash( $_GET['tab'] ) );
				}
			}
		}

		// Show page tabs.
		if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

			$html .= '<h2 class="nav-tab-wrapper">' . "\n";

			$c = 0;
			foreach ( $this->settings as $section => $data ) {

				// Set tab class.
				$class = 'nav-tab';
				if ( ! isset( $_GET['tab'] ) ) {
					$button_label = 'Save Symlink';
					if ( 0 === $c ) {
						$class .= ' nav-tab-active';
					}
				} else {
					if ( isset( $_GET['tab'] ) && $section === $_GET['tab'] ) {
						$tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
						if ( 'delete' === $tab ) {
							$button_label = 'Delete Symlink';
						} else {
							$button_label = 'Save Symlink';
						}
						$class .= ' nav-tab-active';
					}
				}

				// Set tab link.
				$tab_link = add_query_arg(
					array(
						'tab' => $section,
					)
				);

				if ( isset( $_GET['settings-updated'] ) ) {
					$updated = sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) );

					$tab_link = remove_query_arg( 'settings-updated', $tab_link );
				}

				// Output tab.
				$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

				++$c;
			}

			$html .= '</h2>' . "\n";
		}

			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Get settings fields.
				ob_start();
				settings_fields( $this->parent->token . '_settings' );
				do_settings_sections( $this->parent->token . '_settings' );
				$html .= ob_get_clean();

				$html     .= '<p class="submit">' . "\n";
					$html .= '<input type="hidden" name="caes_nonce" id="caes_nonce" value="' . esc_html( $nonce ) . '" />';
					$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . $button_label . '" />' . "\n";
				$html     .= '</p>' . "\n";
			$html         .= '</form>' . "\n";
		$html             .= '</div>' . "\n";

		$sanitisation = new Easy_Symlinks_Admin_API();
		echo wp_kses( $html, $sanitisation->allowed_htmls );
	}

	/**
	 * Main Easy_Symlinks_Settings Instance
	 *
	 * Ensures only one instance of Easy_Symlinks_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Easy_Symlinks()
	 * @param object $parent Object instance.
	 * @return object Easy_Symlinks_Settings instance
	 */
	public static function instance( $parent ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $parent );
		}
		return self::$instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cloning of Easy_Symlinks_API is forbidden.' ) ), esc_attr( $this->parent->version ) );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Unserializing instances of Easy_Symlinks_API is forbidden.' ) ), esc_attr( $this->parent->version ) );
	} // End __wakeup()

}
