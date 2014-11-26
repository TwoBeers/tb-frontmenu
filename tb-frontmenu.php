<?php
/**
 * Plugin Name: TB Front Menu
 * Plugin URI: https://github.com/TwoBeers/tb-frontmenu
 * Description: a cool squared menu
 * Author: Twobeers
 * Author URI: http://www.twobeers.net/
 * Version: 1.0
 * License: GNU General Public License, version 2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

class TBFrontmenu {

	var $plugin_vars = array(
		'name'			=> 'TB FrontMenu',
		'handle'		=> 'tb-frontmenu',
		'slug'			=> 'tb_frontmenu',
		'option_key'	=> 'tb_frontmenu_options',
		'image_size'	=> array(),
	);

	//holds the plugin options. can be accessed from outside the class
	public static $plugin_options = array();


	/**
	 * Constructor
	 */
	function __construct() {

		load_plugin_textdomain( 'tb_frontmenu', '', dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		add_action( 'after_setup_theme'						, array( $this, 'setup' ) );
		add_action( 'wp_enqueue_scripts'					, array( $this, 'front_scripts' ) );
		add_action( 'wp_head'								, array( $this, 'custom_css' ) );
		add_action( 'admin_init'							, array( $this, 'options_init' ) );
		add_action( 'admin_menu'							, array( $this, 'add_page' ) );
		add_action( $this->plugin_vars['slug'] . '_display'	, array( $this, 'display' ) ); //tb_frontmenu_display

	}


	function setup() {

		$this->plugin_vars['image_size'] = apply_filters( $this->plugin_vars['slug'] . '_image_size', array( 'name' => 'tb-frontpage-thumb', 'width' => 640, 'height' => 360 ) );

		add_image_size( $this->plugin_vars['image_size']['name'], $this->plugin_vars['image_size']['width'], $this->plugin_vars['image_size']['height'], true );

		register_nav_menus( array( $this->plugin_vars['handle'] => $this->plugin_vars['name'] ) );

		// Load our options for use in any method.
		$this->get_plugin_options();

	}


	/**
	 * add stylesheets and js scripts in front side
	 */
	function front_scripts() {

		wp_enqueue_style( $this->plugin_vars['handle'], plugins_url( 'css/front-style.css', __FILE__ ), array( 'dashicons' ), '1.0', 'screen' );
		wp_enqueue_style( $this->plugin_vars['handle'] . '-responsive', plugins_url( 'css/front-responsive.css', __FILE__ ), array( 'dashicons' ), '1.0', 'screen and (max-width: ' . self::$plugin_options['threshold'] . 'px)' );
		wp_enqueue_script( $this->plugin_vars['handle'] . '-script', plugins_url( 'js/front-script.js', __FILE__ ), array( 'jquery', 'jquery-effects-core', 'hoverIntent' ) );
		// Now we can localize the script with our data.
		$data = array(
			'threshold' => self::$plugin_options['threshold'],
			'test_mode' => current_user_can( 'manage_options' ) ? self::$plugin_options['test_mode'] : 0,
		);
		wp_localize_script( $this->plugin_vars['handle'] . '-script', $this->plugin_vars['slug'] . '_data', $data );

	}


	/**
	 * print the menu
	 */
	function display() {

		if ( ! apply_filters( $this->plugin_vars['slug'] . '_visibility', $this->is_visible() ) ) return;

		wp_nav_menu( array(
			'container'			=> current_theme_supports( 'html5' ) ? 'nav' : 'div',
			'container_id'		=> $this->plugin_vars['handle'] . '-wrap',
			'menu_id'			=> $this->plugin_vars['handle'],
			'menu_class'		=> $this->plugin_vars['handle'] . '-nav-menu no-js current-theme-' . sanitize_html_class( strtolower( wp_get_theme() ), 'unknown' ),
			'fallback_cb'		=> false,
			'theme_location'	=> $this->plugin_vars['handle'],
			'depth'				=> 2,
			'walker'			=> new TBFrontmenu_Walker,
		) );

	}


	/**
	 * check if a menu is assigned or not, and return the id
	 */
	function is_location_set() {
		if ( ( $locations = get_nav_menu_locations() ) && isset( $locations[$this->plugin_vars['handle']] ) && $locations[$this->plugin_vars['handle']] )
			return $locations[$this->plugin_vars['handle']];
		return false;
	}


	/**
	 * check if the menu is visible
	 */
	function is_visible() {
		if (
			( is_front_page() && self::$plugin_options['show_on_front'] ) ||
			( is_page() && self::$plugin_options['show_on_page'] ) ||
			( is_single() && self::$plugin_options['show_on_post'] ) ||
			( ( is_archive() || is_search() || is_home() ) && self::$plugin_options['show_on_blog'] )
		) return true;

		return false;

	}


	/**
	 * Register the form setting for our options array.
	 *
	 * This function is attached to the admin_init action hook.
	 *
	 * This call to register_setting() registers a validation callback, validate(),
	 * which is used when the option is saved, to ensure that our option values are properly
	 * formatted, and safe.
	 */
	function options_init() {

		// Register our option group.
		register_setting(
			$this->plugin_vars['option_key'],					// Options group, see settings_fields() call in render_page()
			$this->plugin_vars['option_key'],					// Database option, see get_plugin_options()
			array( $this, 'validate' )							// The sanitization callback, see validate()
		);

		// Register our settings field group.
		add_settings_section(
			'general',											// Unique identifier for the settings section
			'',													// Section title (we don't want one)
			'__return_false',									// Section callback (we don't want anything)
			'plugin_options'									// Menu slug, used to uniquely identify the page; see add_page()
		);

		// Register our individual settings fields.
		add_settings_field(
			'layout',											// Unique identifier for the field for this section
			__( 'layout', 'tb_frontmenu' ).'<div id="frontmenu-alert"></div>',							// Setting field label
			array( $this, 'settings_field_layout' ),			// Function that renders the settings field
			'plugin_options',									// Menu slug, used to uniquely identify the page; see add_page()
			'general'											// Settings section. Same as the first argument in the add_settings_section() above
		);

		// Register our individual settings fields.
		add_settings_field(
			'margin',											// Unique identifier for the field for this section
			__( 'margin between blocks (px)', 'tb_frontmenu' ),		// Setting field label
			array( $this, 'settings_field_margin' ),			// Function that renders the settings field
			'plugin_options',									// Menu slug, used to uniquely identify the page; see add_page()
			'general'											// Settings section. Same as the first argument in the add_settings_section() above
		);

		// Register our individual settings fields.
		add_settings_field(
			'items',											// Unique identifier for the field for this section
			__( 'items', 'tb_frontmenu' ),							// Setting field label
			array( $this, 'settings_field_items' ),				// Function that renders the settings field
			'plugin_options',									// Menu slug, used to uniquely identify the page; see add_page()
			'general'											// Settings section. Same as the first argument in the add_settings_section() above
		);

		// Register our individual settings fields.
		add_settings_field(
			'visibility',										// Unique identifier for the field for this section
			__( 'visibility', 'tb_frontmenu' ),						// Setting field label
			array( $this, 'settings_field_visibility' ),		// Function that renders the settings field
			'plugin_options',									// Menu slug, used to uniquely identify the page; see add_page()
			'general'											// Settings section. Same as the first argument in the add_settings_section() above
		);

		// Register our individual settings fields.
		add_settings_field(
			'threshold',										// Unique identifier for the field for this section
			__( 'responsive threshold', 'tb_frontmenu' ),				// Setting field label
			array( $this, 'settings_field_threshold' ),				// Function that renders the settings field
			'plugin_options',									// Menu slug, used to uniquely identify the page; see add_page()
			'general'											// Settings section. Same as the first argument in the add_settings_section() above
		);

	}


	/**
	 * add js script to admin side
	 */
	function options_page_scripts() {

		wp_enqueue_media();

		wp_enqueue_script( $this->plugin_vars['handle'] . '-options-page-script', plugins_url( 'js/admin-script.js', __FILE__ ), array( 'jquery', 'jquery-ui-slider', 'jquery-ui-accordion', 'wp-color-picker' ), '1.0', true );

		// Now we can localize the script with our data.
		$data = array(
			'image_width'			=> $this->plugin_vars['image_size']['width'],
			'image_height'			=> $this->plugin_vars['image_size']['height'],
			'slider_value'			=> self::$plugin_options['margin'],
			'admin_menu_href'		=> get_admin_url( '', 'nav-menus.php?action=locations' ),
			'assign_menu'			=> __( 'Menu not assigned', 'tb_frontmenu' ),
			'confirm_to_defaults'	=> __( 'Are you really sure you want to revert all the settings to their default values?', 'tb_frontmenu' ),
			'small_image_alert'		=> sprintf( __( 'image must be bigger than %s px', 'tb_frontmenu' ), $this->plugin_vars['image_size']['width'] . 'x' . $this->plugin_vars['image_size']['height'] ),
		);
		wp_localize_script( $this->plugin_vars['handle'] . '-options-page-script', $this->plugin_vars['slug'] . '_data', $data );

	}


	/**
	 * add stylesheet to admin side
	 */
	function options_page_style() {

		wp_enqueue_style( $this->plugin_vars['handle'] . '-options-page-style', plugins_url( 'css/admin-style.css', __FILE__ ), array( 'wp-color-picker' ) );
		wp_enqueue_style( 'jquery-ui-slider', plugins_url( 'css/admin-jquery-ui.structure.css', __FILE__ ) );

	}


	/**
	 * Add our plugin options page to the admin menu.
	 *
	 * This function is attached to the admin_menu action hook.
	 */
	function add_page() {
		$plugin_page = add_theme_page(
			$this->plugin_vars['name'],					// Name of page
			$this->plugin_vars['name'],					// Label in menu
			'manage_options',							// Capability required
			$this->plugin_vars['handle'] . '-options',	// Menu slug, used to uniquely identify the page
			array(&$this, 'options_page')				// Function that renders the options page
		);
		add_action( 'admin_print_scripts-' . $plugin_page, array( $this, 'options_page_scripts' ) );
		add_action( 'admin_print_styles-' . $plugin_page, array( $this, 'options_page_style' ) );
	}


	/**
	 * Returns the default options.
	 */
	function get_default_options() {
		$default_options = array(
			'show_on_front'		=> 1,
			'show_on_blog'		=> 1,
			'show_on_page'		=> 0,
			'show_on_post'		=> 0,
			'layout'			=> '2,3',
			'margin'			=> 6,
			'items_default'		=> array( 'bg_color' => '#000000', 'txt_color' => '#ffffff', 'img_id' => '' ),
			'items'				=> array(),
			'threshold'			=> 960,
			'test_mode'			=> 0,
		);

		return apply_filters( $this->plugin_vars['slug'] . '_default_options', $default_options );
	}


	/**
	 * Returns the options array.
	 */
	function get_plugin_options() {

		if ( isset( $_GET[$this->plugin_vars['slug'] . '_erase'] ) ) {
			check_admin_referer();
			$_SERVER['REQUEST_URI'] = remove_query_arg( $this->plugin_vars['slug'] . '_erase', $_SERVER['REQUEST_URI'] );
			delete_option( $this->plugin_vars['option_key'] );
		}
		//get the options, merging together the array from the db (if any) and the array of default values
		$opt = wp_parse_args( get_option( $this->plugin_vars['option_key'], array() ), $this->get_default_options() );
		self::$plugin_options = $opt;

		//fill the values for each item in menu
		$this->get_items_settings();

	}


	/**
	 * Renders the setting field.
	 */
	function settings_field_visibility() {
		?>
		<label for="show-on-front">
			<input type="checkbox" value="1" name="<?php echo $this->plugin_vars['option_key']; ?>[show_on_front]" id="show-on-front" <?php checked( self::$plugin_options['show_on_front'] ); ?> />
			<?php _e( 'front page', 'tb_frontmenu' );  ?>
		</label>
		<br />
		<label for="show-on-blog">
			<input type="checkbox" value="1" name="<?php echo $this->plugin_vars['option_key']; ?>[show_on_blog]" id="show-on-blog" <?php checked( self::$plugin_options['show_on_blog'] ); ?> />
			<?php _e( 'blog', 'tb_frontmenu' );  ?>
		</label>
		<br />
		<label for="show-on-page">
			<input type="checkbox" value="1" name="<?php echo $this->plugin_vars['option_key']; ?>[show_on_page]" id="show-on-page" <?php checked( self::$plugin_options['show_on_page'] ); ?> />
			<?php _e( 'single page', 'tb_frontmenu' );  ?>
		</label>
		<br />
		<label for="show-on-post">
			<input type="checkbox" value="1" name="<?php echo $this->plugin_vars['option_key']; ?>[show_on_post]" id="show-on-post" <?php checked( self::$plugin_options['show_on_post'] ); ?> />
			<?php _e( 'single post', 'tb_frontmenu' );  ?>
		</label>
		<?php
	}


	/**
	 * Renders the setting field.
	 */
	function settings_field_margin() {
		$value = self::$plugin_options['margin'];
		?>
			<input class="hide-if-js" name="<?php echo $this->plugin_vars['option_key']; ?>[margin]" type="text" id="<?php echo $this->plugin_vars['option_key']; ?>-margin" value="<?php echo $value; ?>" />
			<div id="frontmenu-margin-slider" class="hide-if-no-js"><span><?php echo $value; ?></span></div>
		<?php
	}


	/**
	 * Renders the setting field.
	 */
	function settings_field_layout() {
		?>
		<div id="frontmenu-rows">
			<?php
			$count = 0;
			foreach ( explode( ',', self::$plugin_options['layout'] ) as $value) {
				echo '<div class="row row-' . $value . '">';
				for ($i = 1; $i <= $value; $i++) {
					$count++;
					echo '<span class="item">' . $count . '</span>';
				}
				echo '<a href="javascript:void(0)" class="dashicons dashicons-no remove-row hide-if-no-js" onclick="tb_frontmenu_options.remove_row(this)"></a></div>';
			}
			?>
		</div>
		<input class="hide-if-js" type="text" name="<?php echo $this->plugin_vars['option_key']; ?>[layout]" id="menu-layout" value="<?php echo self::$plugin_options['layout']; ?>" />
		<span class="label hide-if-js"><?php _e( 'comma-separated value of "2" and "3"', 'tb_frontmenu' ); ?></span>
		<p id="frontmenu-add" class="hide-if-no-js">
			<span class="button-secondary" id="button-add-row2"><?php _e( '2-blocks row', 'tb_frontmenu' ) ?></span>
			<span class="button-secondary" id="button-add-row3"><?php _e( '3-blocks row', 'tb_frontmenu' ) ?></span>
		</p>
		<?php
	}


	/**
	 * Renders the setting field.
	 */
	function settings_field_threshold() {
		$value = self::$plugin_options['threshold'];
		?>
		<input name="<?php echo $this->plugin_vars['option_key']; ?>[threshold]" type="text" id="<?php echo $this->plugin_vars['option_key']; ?>-threshold" value="<?php echo $value; ?>" />
		<label for="test-threshold">
			<input type="checkbox" value="1" name="<?php echo $this->plugin_vars['option_key']; ?>[test_mode]" id="test-threshold" <?php checked( self::$plugin_options['test_mode'] ); ?> />
			<?php _e( 'test_mode', 'tb_frontmenu' );  ?>
		</label>
		<?php
	}


	/**
	 * Renders the setting field.
	 */
	function settings_field_items() {

		if ( $location = $this->is_location_set() ) {

			$menu = wp_get_nav_menu_object( $location );

			$menu_items = wp_get_nav_menu_items( $menu->term_id );

			$menu_list = '<div id="frontmenu-items">';

			$counter = 0;
			foreach ( (array) $menu_items as $key => $menu_item ) {
				if( $menu_item->menu_item_parent ) continue;
				$counter++;
				$title = $menu_item->title;
				$url = $menu_item->url;
				$tools = $this->get_item_tools( $menu_item );
				$style = ' style="background:' . self::$plugin_options['items'][$menu_item->ID]['bg_color'] . '; color:' . self::$plugin_options['items'][$menu_item->ID]['txt_color'] . ';"';
				$menu_list .= '<h3' . $style . '><span class="item-number">' . $counter . '</span>' . $title . '</h3>';
				$menu_list .= '<div class="frontmenu-item" id="frontmenu-item-' . $menu_item->ID . '">' . $tools . '</div>';
			}
			$menu_list .= '</div>';

		} else {

			$menu_list = '<div><p class="hide-if-no-js">[' . __( 'No items', 'tb_frontmenu' ) . ']</p><p class="hide-if-js"><a href="' . get_admin_url( '', 'nav-menus.php?action=locations' ) . '">' . __( 'Menu not assigned', 'tb_frontmenu' ) . '</a></p></div>';

		}
		// $menu_list now ready to output

		echo $menu_list;

	}


	/**
	 * Renders the setting field.
	 */
	function get_item_tools( $menu_item ) {

		$item_id = $menu_item->ID;
		$obj_id = ( $menu_item->type === 'post_type' ) ? $menu_item->object_id : false;

		$bg_color		= self::$plugin_options['items'][$item_id]['bg_color'];
		$txt_color		= self::$plugin_options['items'][$item_id]['txt_color'];
		$img_id			= self::$plugin_options['items'][$item_id]['img_id'];
		if ( $obj_id && ( $img_id === '' ) ) {
			$_img_id = get_post_thumbnail_id( $obj_id );
			$_img_meta = wp_get_attachment_metadata( $_img_id );
			if ( ( $_img_meta['width'] >= $this->plugin_vars['image_size']['width'] ) && ( $_img_meta['height'] >= $this->plugin_vars['image_size']['height'] ) )
				$img_id = $_img_id;
		}
		$image			= $img_id ? '<a href="' . get_edit_post_link( $img_id ) . '" target="_blank">' . wp_get_attachment_image( $img_id ) . '</a>' : '';
		$has_featured	= $img_id ? ' featured' : '';

		$output = '';

		
		$output .= '<span class="label">' . __( 'background', 'tb_frontmenu' ) . '</span>';
		$output .= '<div class="setting">';
		$output .= '<input name="' . $this->plugin_vars['option_key'] . '[items][' . $item_id . '][bg_color]" class="plugin_option_colorpicker to-background" type="text" id="' . $this->plugin_vars['option_key'] . '-items-' . $item_id . '-bgcolor" value="' . $bg_color . '" data-default-color="' . self::$plugin_options['items_default']['bg_color'] . '" />';
		$output .= '<span class="description hide-if-js">' . __( 'Default' , 'tb_frontmenu' ) . ': ' . self::$plugin_options['items_default']['bg_color'] . '</span>';
		$output .= '</div>';

		$output .= '<span class="label">' . __( 'text', 'tb_frontmenu' ) . '</span>';
		$output .= '<div class="setting">';
		$output .= '<input name="' . $this->plugin_vars['option_key'] . '[items][' . $item_id . '][txt_color]" class="plugin_option_colorpicker to-text" type="text" id="' . $this->plugin_vars['option_key'] . '-items-' . $item_id . '-txtcolor" value="' . $txt_color . '" data-default-color="' . self::$plugin_options['items_default']['txt_color'] . '" />';
		$output .= '<span class="description hide-if-js">' . __( 'Default' , 'tb_frontmenu' ) . ': ' . self::$plugin_options['items_default']['txt_color'] . '</span>';
		$output .= '</div>';

		$output .= '<span class="label">' . __( 'image', 'tb_frontmenu' ) . '</span>';
		$output .= '<div class="setting">';
		$output .= '<input placeholder="ID" name="' . $this->plugin_vars['option_key'] . '[items][' . $item_id . '][img_id]" class="hide-if-js plugin_option_imageid" type="text" id="' . $this->plugin_vars['option_key'] . '-items-' . $item_id . '-txtcolor" value="' . $img_id . '" />';
		$output .= '<div class="thumbnail-container">' . $image . '</div>';
		$output .= '<a id="' . $this->plugin_vars['option_key'] . '-items-' . $item_id . '-imgid" class="button hide-if-no-js choose-featured-from-library" data-choose="' . esc_attr__( 'Choose Image' , 'tb_frontmenu' ) . '" data-update="' . esc_attr__( 'Set Image' , 'tb_frontmenu' ) . '">' . __( 'Choose Image' , 'tb_frontmenu' ) . '</a>';
		$output .= '<a href="javascript:void(0)" id="' . $this->plugin_vars['option_key'] . '-items-' . $item_id . '-remove" class="hide-if-no-js remove-img-id" >' . __( 'Remove' , 'tb_frontmenu' ) . '</a>';
		$output .= '</div>';

		$output = '<div class="frontmenu-tools' . $has_featured . '">' . $output . '</div>';

		return $output;
	}


	/**
	 * Returns the options array.
	 */
	function options_page() {
		?>

		<div class="wrap" id="<?php echo $this->plugin_vars['handle']; ?>-options-page">

			<h1><?php echo $this->plugin_vars['name']; ?></h1>

			<h2 id="tab-selector" class="nav-tab-wrapper">
				<a class="nav-tab nav-tab-active" href="#plugin-settings"><?php _e( 'Settings' , 'tb_frontmenu' ); ?></a>
				<a class="nav-tab" href="#plugin-info"><?php _e( 'Info' , 'tb_frontmenu' ); ?></a>
			</h2>

			<div id="tabs">

				<div id="plugin-settings" class="tab-content">

					<?php
						// settings have been updated
						if ( isset( $_GET['settings-updated'] ) ) {
							echo '<div class="updated inline"><p><strong>' . __( 'Settings updated', 'tb_frontmenu' ) . '</strong></p></div>';
						}
						// settings have been deleted
						if ( isset( $_GET[$this->plugin_vars['slug'] . '_erase'] ) ) {
							echo '<div class="updated inline"><p><strong>' . __( 'Defaults values loaded', 'tb_frontmenu' ) . '</strong></p></div>';
						}
					?>

					<form method="post" action="options.php">

						<?php
							settings_fields( $this->plugin_vars['option_key'] );
							do_settings_sections( 'plugin_options' );
						?>
						<div class="note"><p><?php _e( 'insert this code in your theme', 'tb_frontmenu' ); ?> <code>&lt;?php do_action('tb_frontmenu_display'); ?&gt;</code></p></div>
						<hr>
						<p>
							<?php
								if ( $this->is_location_set() ) {

									submit_button( '', 'primary', '', false);

									$arr_params = array(
										'page'	=> $this->plugin_vars['handle'] . '-options',
										$this->plugin_vars['slug'] . '_erase'	=> '1',
									);
									?>
										<a class="button" id="to-defaults" href="<?php echo add_query_arg( $arr_params, get_admin_url( '', 'themes.php' ) ); ?>" target="_self"><?php _e( 'Back to defaults' , 'tb_frontmenu' ); ?></a>
									<?php
								}
							?>
						</p>

					</form>

				</div>

				<div id="plugin-info" class="tab-content hide-if-js">

					<?php $this->the_readme();?>

				</div>

			</div>

		</div>

		<?php
	}

	/**
	 * Display an html-formatted readme.txt
	 */
	function the_readme() {

		$readme = file_get_contents( WP_PLUGIN_DIR . '/tb-frontmenu/readme.txt' );
		$readme = make_clickable( nl2br( esc_html( $readme ) ) );
		$readme = preg_replace( '/`(.*?)`/', '<code>\\1</code>', $readme );
		$readme = preg_replace( '/[\040]\*\*(.*?)\*\*/', ' <strong>\\1</strong>', $readme );
		$readme = preg_replace( '/[\040]\*(.*?)\*/', ' <em>\\1</em>', $readme );
		$readme = preg_replace( '/=== (.*?) ===/', '', $readme );
		$readme = preg_replace( '/== (.*?) ==/', '<hr><h3>\\1</h3>', $readme );
		$readme = preg_replace( '/= (.*?) =/', '<h4>\\1</h4>', $readme );
		$readme = preg_replace( '/(Contributors:|Tags:|Requires at least:|Tested up to:|License:|License URI:)/', '<strong class="label">\\1</strong>', $readme );

		echo balanceTags( $readme );

	}

	/**
	 * Sanitize and validate form input. Accepts an array, return a sanitized array.
	 */
	function validate( $input ) {

		// items settings ( bg_color, txt_color, img_id )
		foreach( $input['items'] as $key => $menu_item ) {

			if( ! preg_match( '/^#[a-f0-9]{6}$/i', $menu_item['bg_color'] ) ) {
				$input['items'][$key]['bg_color'] = self::$plugin_options['items_default']['bg_color'];
			}

			if( ! preg_match( '/^#[a-f0-9]{6}$/i', $menu_item['txt_color'] ) ) {
				$input['items'][$key]['txt_color'] = self::$plugin_options['items_default']['txt_color'];
			}

			$input['items'][$key]['img_id'] = absint( $input['items'][$key]['img_id'] );

		}

		//margin
		$input['margin'] = absint( $input['margin'] );

		//responsive threshold
		$input['threshold'] = absint( $input['threshold'] );

		//layout ( only '2' or '3' allowed)
		$layout = explode( ',', $input['layout'] );
		foreach( $layout as $key => $row ) {
			if( ! in_array( $row, array ( '2', '3' ) ) )
				unset( $layout[$key] );
		}
		$input['layout'] = implode( ',', $layout );

		//various checkboxes
		foreach( array( 'show_on_front', 'show_on_blog', 'show_on_page', 'show_on_post', 'test_mode' ) as $opt ) {

			if ( ! isset( $input[$opt] ) )
				$input[$opt] = 0;

			$input[$opt] = $input[$opt] ? 1 : 0;

		}

		return $input;

	}


	/**
	 * fill the values for each item in menu
	 */
	function get_items_settings() {

		if ( $location = $this->is_location_set() ) {

			$menu = wp_get_nav_menu_object( $location );

			$menu_items = wp_get_nav_menu_items( $menu->term_id );

			foreach ( (array) $menu_items as $key => $menu_item ) {

				if( $menu_item->menu_item_parent ) continue;

				if ( isset( self::$plugin_options['items'][$menu_item->ID] ) ) //if the current item is in db options
					//merge the options with an array of default values
					$item_settings = wp_parse_args( self::$plugin_options['items'][$menu_item->ID], self::$plugin_options['items_default'] );
				else
					//else use the default values
					$item_settings = self::$plugin_options['items_default'];

				self::$plugin_options['items'][$menu_item->ID] = $item_settings;

			}

		}

	}


	/**
	 * Create the custom css.
	 */
	function custom_css() {//round(1.95583, 2); 
		?>

	<style type="text/css">
		#tb-frontmenu > li {
			max-width: <?php echo absint( $this->plugin_vars['image_size']['width'] ); ?>px;
		}
		#tb-frontmenu > li.layout-2-1 {
			padding: 0 <?php echo absint( self::$plugin_options['margin'] )/2; ?>px <?php echo absint( self::$plugin_options['margin'] ); ?>px 0;
		}
		#tb-frontmenu > li.layout-2-2 {
			padding: 0 0 <?php echo absint( self::$plugin_options['margin'] ); ?>px <?php echo absint( self::$plugin_options['margin'] )/2; ?>px;
		}
		#tb-frontmenu > li.layout-3-1 {
			padding: 0 <?php echo round( absint( self::$plugin_options['margin'] )/3*2 , 3 ); ?>px <?php echo absint( self::$plugin_options['margin'] ); ?>px 0;
		}
		#tb-frontmenu > li.layout-3-2 {
			padding: 0 <?php echo round( absint( self::$plugin_options['margin'] )/3, 3 ); ?>px <?php echo absint( self::$plugin_options['margin'] ); ?>px;
		}
		#tb-frontmenu > li.layout-3-3 {
			padding: 0 0 <?php echo absint( self::$plugin_options['margin'] ); ?>px <?php echo round( absint( self::$plugin_options['margin'] )/3*2, 3 ); ?>px;
		}
		#tb-frontmenu {
			max-width: <?php echo absint( $this->plugin_vars['image_size']['width'] )*2; ?>px;
			margin: 0 auto;
		}
		@media screen and (max-width: <?php echo absint( self::$plugin_options['threshold'] ); ?>px) {
			#tb-frontmenu > li.row-last {
				padding: 0 0 <?php echo absint( self::$plugin_options['margin'] ); ?>px;
			}
		}
	</style>

		<?php
	}

}

new TBFrontmenu;


class TBFrontmenu_Walker extends Walker_Nav_Menu {

	/**
	 * Start the element output.
	 *
	 * @see Walker::start_el()
	 *
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item   Menu item data object.
	 * @param int    $depth  Depth of menu item. Used for padding.
	 * @param array  $args   An array of arguments. @see wp_nav_menu()
	 * @param int    $id     Current item ID.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		static $counter = 0;
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
		$style = ( $depth == 0 ) ? ' style="background:' . TBFrontmenu::$plugin_options['items'][$item->ID]['bg_color'] . '; color:' . TBFrontmenu::$plugin_options['items'][$item->ID]['txt_color'] . ';"' : '';
		$top_item_container = ( $depth ) ? '' : '<div class="top-item-container"' . $style . '>';

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;
		if ( $depth == 0 ) {
			$classes[] = $this->get_el_layout( $counter );
			$classes[] = $this->get_el_row( $counter );
			$classes[] = 'top-item-' . $counter;
			$classes[] = $this->contrast_color( TBFrontmenu::$plugin_options['items'][$item->ID]['bg_color'] );
			$counter++;
		}

		/**
		 * Filter the CSS class(es) applied to a menu item's <li>.
		 *
		 * @since 3.0.0
		 *
		 * @see wp_nav_menu()
		 *
		 * @param array  $classes The CSS classes that are applied to the menu item's <li>.
		 * @param object $item    The current menu item.
		 * @param array  $args    An array of wp_nav_menu() arguments.
		 */
		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		/**
		 * Filter the ID applied to a menu item's <li>.
		 *
		 * @since 3.0.1
		 *
		 * @see wp_nav_menu()
		 *
		 * @param string $menu_id The ID that is applied to the menu item's <li>.
		 * @param object $item    The current menu item.
		 * @param array  $args    An array of wp_nav_menu() arguments.
		 */
		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

		$output .= $indent . '<li' . $id . $class_names . '>' . $top_item_container;

		$atts = array();
		$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
		$atts['target'] = ! empty( $item->target )     ? $item->target     : '';
		$atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
		$atts['href']   = ! empty( $item->url )        ? $item->url        : '';

		/**
		 * Filter the HTML attributes applied to a menu item's <a>.
		 *
		 * @since 3.6.0
		 *
		 * @see wp_nav_menu()
		 *
		 * @param array $atts {
		 *     The HTML attributes applied to the menu item's <a>, empty strings are ignored.
		 *
		 *     @type string $title  Title attribute.
		 *     @type string $target Target attribute.
		 *     @type string $rel    The rel attribute.
		 *     @type string $href   The href attribute.
		 * }
		 * @param object $item The current menu item.
		 * @param array  $args An array of wp_nav_menu() arguments.
		 */
		$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args );

		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( ! empty( $value ) ) {
				$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		$item_output = $args->before;
		$item_output .= '<a'. $attributes .'>';
		/** This filter is documented in wp-includes/post-template.php */
		$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;

		/**
		 * Filter a menu item's starting output.
		 *
		 * The menu item's starting output only includes $args->before, the opening <a>,
		 * the menu item's title, the closing </a>, and $args->after. Currently, there is
		 * no filter for modifying the opening and closing <li> for a menu item.
		 *
		 * @since 3.0.0
		 *
		 * @see wp_nav_menu()
		 *
		 * @param string $item_output The menu item's starting HTML output.
		 * @param object $item        Menu item data object.
		 * @param int    $depth       Depth of menu item. Used for padding.
		 * @param array  $args        An array of wp_nav_menu() arguments.
		 */
		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}

	/**
	 * Ends the element output, if needed.
	 *
	 * @see Walker::end_el()
	 *
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item   Page data object. Not used.
	 * @param int    $depth  Depth of page. Not Used.
	 * @param array  $args   An array of arguments. @see wp_nav_menu()
	 */
	public function end_el( &$output, $item, $depth = 0, $args = array() ) {

		$top_item_container = ( $depth ) ? '' : '<div class="featured-container">' . $this->get_el_thumb( $item ) . '</div></div>';
		$output .= "$top_item_container</li>\n";

	}

	/**
	 * Get the image for the current menu item
	 * 
	 * @since 1.0
	 * 
	 * @param	object	$item	Page data object.
	 * 
	 * @return	string	an HTML image element representing an attachment file
	 */
	public function get_el_thumb( $item ) {

		return $image = ( isset( TBFrontmenu::$plugin_options['items'][$item->ID]['img_id'] ) && ( $id = TBFrontmenu::$plugin_options['items'][$item->ID]['img_id'] ) ) ? wp_get_attachment_image( $id, 'tb-frontpage-thumb' ) : '';

	}

	/**
	 * Return the layout-related class of the current item
	 * 
	 * @since 1.0
	 * 
	 * @param	integer	$index	index of current item
	 * 
	 * @return	string	class string of the current item, related to its layout
	 */
	public function get_el_layout( $index = 0 ) {
		static $el_list = array();

		if ( ! $el_list ) {

			//create the layout structure
			foreach ( explode( ',', TBFrontmenu::$plugin_options['layout'] ) as $value) {

				for ($i = 1; $i <= $value; $i++) {

					$el_list[] = $value . '-' . $i;

				}

			}

		}

		return 'layout-' . $el_list[$index];

	}

	/**
	 * Return the row-related class of the current item
	 * 
	 * @since 1.0
	 * 
	 * @param	integer	$index	index of current item
	 * 
	 * @return	string	class string of the current item, related to its row
	 */
	public function get_el_row( $index = 0 ) {
		static $el_list = array();

		if ( ! $el_list ) {

			//create the layout structure
			foreach ( $layout = explode( ',', TBFrontmenu::$plugin_options['layout'] ) as $row => $value) {

				for ($i = 1; $i <= $value; $i++) {

					$row_class = 'row-' . $row;

					if ( $row === 0 )
						$row_class .= ' row-first';

					if ( $row === ( count( $layout ) -1 ) )
						$row_class .= ' row-last';

					if ( ( $row === 0 ) && ( $i === 1 ) )
						$row_class .= ' menu-item-first';

					if ( ( $row === ( count( $layout ) -1 ) ) && ( $i == $value ) )
						$row_class .= ' menu-item-last';

					$el_list[] = $row_class;

				}

			}

		}

		return isset( $el_list[$index] ) ? $el_list[$index] : 'row-unknown';

	}

	/**
	 * Evaluate the lightness of the color
	 * 
	 * @since 1.0
	 * 
	 * @param	string	$hex	hex color value
	 * 
	 * @return	string	lightness
	 */
	function contrast_color( $hex = '#000000' ) {

		$color = str_replace( '#', '', $hex );
		$rgba['r'] = hexdec( substr( $color, 0, 2 ) );
		$rgba['g'] = hexdec( substr( $color, 2, 2 ) );
		$rgba['b'] = hexdec( substr( $color, 4, 2 ) );

		$lightness = ( 0.299 * $rgba['r'] + 0.587 * $rgba['g'] + 0.114 * $rgba['b'] );

		return ( $lightness > 125 ) ? 'light' : 'dark';

	}

}







