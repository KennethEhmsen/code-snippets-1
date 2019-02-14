<?php

namespace Code_Snippets;

/**
 * Base class for a snippets admin menu
 */
class Admin_Menu {

	/**
	 * The snippet page short name
	 * @var string
	 */
	public $name;

	/**
	 * The label shown in the admin menu
	 * @var string
	 */
	public $label;

	/**
	 * The text used for the page title
	 * @var string
	 */
	public $title;

	/**
	 * Constructor
	 *
	 * @param string $name  The snippet page short name
	 * @param string $label The label shown in the admin menu
	 * @param string $title The text used for the page title
	 */
	public function __construct( $name, $label, $title ) {
		$this->name = $name;
		$this->label = $label;
		$this->title = $title;
	}

	/**
	 * Register action and filter hooks
	 */
	public function run() {
		if ( ! code_snippets()->admin->is_compact_menu() ) {
			add_action( 'admin_menu', array( $this, 'register' ) );
			add_action( 'network_admin_menu', array( $this, 'register' ) );
		}
	}

	/**
	 * Add a sub-menu to the Snippets menu
	 * @uses add_submenu_page() to register a submenu
	 *
	 * @param string $slug The slug of the menu
	 * @param string $label The label shown in the admin menu
	 * @param string $title The page title
	 */
	public function add_menu( $slug, $label, $title ) {
		$hook = add_submenu_page(
			code_snippets()->get_menu_slug(),
			$title,
			$label,
			code_snippets()->get_cap(),
			$slug,
			array( $this, 'render' )
		);

		add_action( 'load-' . $hook, array( $this, 'load' ) );
	}

	/**
	 * Register the admin menu
	 */
	public function register() {
		$this->add_menu( code_snippets()->get_menu_slug( $this->name ), $this->label, $this->title );
	}

	/**
	 * Render the content of a vew template
	 *
	 * @param string $name Name of view template to render
	 */
	protected function render_view( $name ) {
		include dirname( PLUGIN_FILE ) . '/php/views/' . $name . '.php';
	}

	/**
	 * Render the menu
	 */
	public function render() {
		$this->render_view( $this->name );
	}

	/**
	 * Print the status and error messages
	 */
	protected function print_messages() {}

	/**
	 * Retrieve a result message based on a posted status
	 *
	 * @param array  $messages    List of possible messages to display.
	 * @param string $request_var Name of $_REQUEST variable to check.
	 * @param string $class       Class to use on buttons. Default 'updated'.
	 *
	 * @return string|bool The result message if a valid status was received, otherwise false
	 */
	protected function get_result_message( $messages, $request_var = 'result', $class = 'updated' ) {

		if ( empty( $_REQUEST[ $request_var ] ) ) {
			return false;
		}

		$result = $_REQUEST[ $request_var ];

		if ( isset( $messages[ $result ] ) ) {
			return sprintf(
				'<div id="message" class="%2$s fade"><p>%1$s</p></div>',
				$messages[ $result ], $class
			);
		}

		return false;
	}

	/**
	 * Print a result message based on a posted status
	 *
	 * @param array  $messages    List of possible messages to display.
	 * @param string $request_var Name of $_REQUEST variable to check.
	 * @param string $class       Class to use on buttons. Default 'updated'.
	 */
	protected function show_result_message( $messages, $request_var = 'result', $class = 'updated' ) {
		$message = $this->get_result_message( $messages, $request_var, $class );

		if ( $message ) {
			echo wp_kses_post( $message );
		}
	}

	/**
	 * Executed when the admin page is loaded
	 */
	public function load() {
		/* Make sure the user has permission to be here */
		if ( ! current_user_can( code_snippets()->get_cap() ) ) {
			wp_die( esc_html__( 'You are not authorized to access this page.', 'code-snippets' ) );
		}

		/* Create the snippet tables if they don't exist */
		$db = code_snippets()->db;
		$db->create_missing_table( $db->ms_table );
		$db->create_missing_table( $db->table );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue scripts and stylesheets for the admin page, if necessary
	 */
	public function enqueue_assets() {}

	/**
	 * Render a list of links to other pages in the page title
	 *
	 * @param array $actions List of actions to render as links, as array values.
	 */
	protected function page_title_actions( $actions ) {

		foreach ( $actions as $action ) {
			if ( 'settings' === $action && ! isset( code_snippets()->admin->menus['settings'] ) ) {
				continue;
			}

			printf( '<a href="%s" class="page-title-action">', esc_url( code_snippets()->get_menu_url( $action ) ) );

			switch ( $action ) {
				case 'manage':
					echo esc_html_x( 'Manage', 'snippets', 'code-snippets' );
					break;
				case 'add':
					echo esc_html_x( 'Add New', 'snippet', 'code-snippets' );
					break;
				case 'import':
					echo esc_html_x( 'Import', 'snippets', 'code-snippets' );
					break;
				case 'settings':
					echo esc_html_x( 'Settings', 'snippets', 'code-snippets' );
					break;
			}

			echo '</a>';
		}
	}
}
