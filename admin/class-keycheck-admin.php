<?php

/**
 * The admin-specific functionality of the plugin.
 *
 */

class Keycheck_Admin {

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
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */

	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/keycheck-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */

	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/keycheck-admin.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Add dinamic cron interval retrieved from the database.
	 *
	 * @since    1.0.0
	 */

	public function keycheck_cron_schedules( $schedules ) {

		/*
		 * Get the value of the setting we've registered with register_setting().
		 */

		$options = get_option( 'keycheck_options' );

		/*
		 * Add the dynamic time interval to the WordPress schedule.
		 */

		$schedules['keycheck_dynamic'] = array(
			'interval' => $options['keycheck_interval']*60,
			'display' => __('Keycheck Custom ')
		);
		return $schedules;
	}

	/**
	 * Run the check logic.
	 * 
	 * @since    1.0.0
	 */

	public function run_keycheck_interval_cb() {
		/*
		 * Mark the run of this function.
		 */
		define( "RUN_KEYCHECK", true );

		/*
		 * Get the value of the setting we've registered with register_setting().
		 */

		$options = get_option( 'keycheck_options' );

		/*
		 * Get array of keys to check from the $options variable.
		 */
		$keys = explode( ",", $options['keycheck_list_of_keys'] );

		/*
		 * Get all draft posts.
		 */
		$args = array(
      'post_type' => 'post',
      'orderby'   => 'ID',
      'order'     => 'ASC',
      'post_status' => 'draft',
      'posts_per_page' => 500,
    );

    $drafts = new WP_Query( $args );

    /*
     * If we have drafts check them against the keyward list.
     */

    if ( $drafts->have_posts() ) {
    	$match = false;
    	while ( $drafts->have_posts() ) {
    		$drafts->the_post();
    		$post_strings = array(
    			'title' => get_the_title(),
    			'contet' => get_the_content(),
    			'categories' => get_the_category_list( ' ', 'multiple' ),
    			'tags' => get_the_tag_list( '', ' ', '' ),
    			'metadata' => get_post_meta( get_the_ID() )
    		);

    		$post_string = json_encode( $post_strings );

    		foreach( $keys as $key  ) {
    			$match = stripos( $post_string, trim( $key ) );
    			if ( $match !== false ) {
	    			wp_trash_post( get_the_ID() );
	    			break;
			    }
    		}
    		if ( $match === false ) {
    			wp_update_post( array(
		        'ID'    =>  get_the_ID(),
		        'post_status'   =>  'publish'
		      ) );
    		}

    	}
    	wp_reset_postdata();
    }

    /*
     * Get last checked tag.
     */

    $last_tag_checked_id = intval( $options['last_tag'] );

    /*
     * If we didn't checked any tags start from the first one.
     */
    if ( !$last_tag_checked_id ) {
    	$last_tag_checked_id = 0;
    }

    /*
     * Get unchecked tags.
     * Custom filter for 'exclude' argument to match all id's bigger then the one excldued!
     */

    $terms = get_terms( array(
		    'hide_empty' => false,
		    'exclude' => intval( $last_tag_checked_id ),
		    'number' => 500

		) );

    /*
     * Reset $match
     */
    $match = 0;

    /*
     * Check each tag against the keys.
     */

    foreach( $terms as $term ) {
    	$term_strings = json_encode( $term );
    	foreach( $keys as $key ) {
    		$match = stripos( $term_strings, trim( $key ) );
    		if ( $match !== false ) {
    			wp_delete_term( $term->term_id, $term->taxonomy );
    			break;
    		}
    	}
    	$last_tag_checked_id = intval( $term->term_id );
    }

    $options['last_tag'] = intval( $last_tag_checked_id );
    update_option( 'keycheck_options', $options );


	}

	/**
	 * Edit WP Term Query where clause to allow id higher then the provided one. 
	 *
	 * @since 1.0.0
	 */
	public function custom_get_terms_where( $exclusions, $args, $taxonomies ) {
		if ( defined('RUN_KEYCHECK') ) {
			$exclusion_max_id = intval( $args['exclude'] );
			$exclusions = 't.term_id > ' . $exclusion_max_id;
			return $exclusions;
		}
	}

	/**
	 * Register plugin settings.
	 *
	 * @since    1.0.0
	 */
	public function register_keycheck_settings(){

		/*
		 * Register a new setting for the Keycheck settings page.
		 */

		register_setting( 'keycheck', 'keycheck_options' );
		 
		/*
		 * Register a new section in the Keycheck settings page.
		 */

		add_settings_section(
			'keycheck_settings_general_section',
			__( 'General section', 'keycheck' ),
			array( $this, 'keycheck_settings_general_section_cb' ),
			'keycheck'
		);
		 
		/*
		 * Register a new field in the keycheck_settings_general_section section, inside the Keycheck settings page.
		 */

		add_settings_field(
			'keycheck_list_of_keys',
			__( 'Keys list', 'keycheck' ),
			array( $this, 'keycheck_field_keys_list_cb' ),
			'keycheck',
			'keycheck_settings_general_section',
			[
				'label_for' => 'keycheck_list_of_keys',
				'class' => 'keycheck_row',
			]
		);

		add_settings_field(
			'keycheck_interval',
			__( 'Interval', 'keycheck' ),
			array( $this, 'keycheck_field_interval_cb' ),
			'keycheck',
			'keycheck_settings_general_section',
			[
				'label_for' => 'keycheck_interval',
				'class' => 'keycheck_row',
			]
		);
	}

	/**
	 * The callback registered at keycheck_settings_general_section.
   *
	 * @since    1.0.0
   */

	function keycheck_settings_general_section_cb( $args ) {
		return;
	}

	/**
	 * The callback for the key list field.
	 *
	 * @since    1.0.0
	 */

	function keycheck_field_keys_list_cb( $args ) {

		/*
		 * Get the value of the setting we've registered with register_setting().
		 */

		$options = get_option( 'keycheck_options' );

		/*
		 * Output the field
		 */
		?>
		<textarea id="<?php echo esc_attr( $args['label_for'] ); ?>"
		data-custom="<?php echo esc_attr( $args['keycheck_custom_data'] ); ?>"
		name="keycheck_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
		rows="8" cols="50"><?php echo isset( $options[ $args['label_for'] ] ) ? ( $options[$args['label_for']] ) : ( '' ); ?></textarea>
		<p class="description">
		<?php esc_html_e( 'Please add the desired keys separated by comma.', 'keycheck' ); ?>
		</p>
		<?php

	}

	/**
	 * The callback for the time interval field.
	 *
	 * @since    1.0.0
	 */

	public function keycheck_field_interval_cb( $args ) {
		/*
		 * Get the value of the setting we've registered with register_setting().
		 */

		$options = get_option( 'keycheck_options' );

		/*
		 * Output the field
		 */
		?>
		<input type="number" id="<?php echo esc_attr( $args['label_for'] ); ?>"
			data-custom="<?php echo esc_attr( $args['wporg_custom_data'] ); ?>"
			name="keycheck_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
			<?php echo isset( $options[ $args['label_for'] ] ) ? ( 'value=' . $options[$args['label_for']] ) : ( '' ); ?>
		/><?php _e( 'min', 'keycheck' );
	}

	/**
	 * Add the options admin menu that links the plugin settings.
	 *
	 * @since    1.0.0
	 */

	public function keycheck_menu() {
		add_options_page( 'Keycheck options', 'Keycheck', 'manage_options', 'keycheck', array( $this, 'keycheck_options' ) );
	}

	/**
	 * Options page for the plugin. 
	 *
	 */

	public function keycheck_options() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}


		if ( isset( $_GET['settings-updated'] ) ) {
			// add settings saved message with the class of "updated"
			// add_settings_error( 'keycheck_options', '	keycheck_option', __( 'Settings Saved', 'keycheck' ), 'updated' );
		}
		// show error/update messages
		settings_errors( 'keycheck_options' );
		?>
		<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
		<?php
		// output security fields for the registered setting "wporg"
		settings_fields( 'keycheck' );
		// output setting sections and their fields
		// (sections are registered for "wporg", each field is registered to a specific section)
		do_settings_sections( 'keycheck' );
		// output save settings button
		submit_button( 'Save Settings' );
		?>
		</form>
		</div>
		<?php
		 
	}

}