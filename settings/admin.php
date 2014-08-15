<?php

defined( 'ABSPATH' ) or die();

class V23_Settings_Page {
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
		add_filter( 'mexp_services', array( $this, 'store_all_mexp_services' ), 20 );
		add_filter( 'mexp_services', array( $this, 'chosen_mexp_services' ), 99 );
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page() {
		// This page will be under "Settings"
		add_options_page(
			//            'Settings Admin',
			'23 Video',
			'23 Video',
			'manage_options',
			'v23-setting-admin',
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page() {
		// Set class property
		$this->options = get_option( 'v23_options' );
?>
        <div class="wrap">
            <h2>23 Video</h2>
            <form method="post" action="options.php">
            <?php
		// This prints out all hidden setting fields
		settings_fields( 'v23_option_group' );
		do_settings_sections( 'v23-setting-admin' );
		submit_button();
?>
            </form>
        </div>
        <?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init() {


		/**
		 * param: Option group, Option name, Sanitize fields callback
		 */
		register_setting( 'v23_option_group', 'v23_options', array( $this, 'input_validation' ) );

		/**
		 * param: ID, Title, Callback, Page
		 */
		add_settings_section( 'setting_section_id', 'Settings', array( $this, 'print_section_info' ), 'v23-setting-admin' );

		/**
		 *  param: ID, Title, Callback, Page, Section
		 */
		//         $apiurl = 'http://nettv.regjeringen.no';

		// $consumerKey = '';
		// $consumerSecret = '';
		// $accessToken = '';
		// $accessTokenSecret = '';
		add_settings_field( 'apiurl', __( 'URL to your 23 Video site', 'mexp-23' ), array( $this, 'form_field' ), 'v23-setting-admin', 'setting_section_id', array( 'type'=> 'text', 'label_for' => 'apiurl' ) );
		add_settings_field( 'consumerkey', __( 'Consumer Key', 'mexp-23' ), array( $this, 'form_field' ), 'v23-setting-admin', 'setting_section_id', array( 'type'=> 'text', 'label_for' => 'consumerkey' ) );
		add_settings_field( 'consumersecret', __( 'Consumer Secret', 'mexp-23' ), array( $this, 'form_field' ), 'v23-setting-admin', 'setting_section_id', array( 'type'=> 'text', 'label_for' => 'consumersecret' ) );
		add_settings_field( 'accesstoken', 'Access Token', array( $this, 'form_field' ), 'v23-setting-admin', 'setting_section_id', array( 'type'=> 'text', 'label_for' => 'accesstoken' ) );
		add_settings_field( 'accesstokensecret', __( 'AccessTokenSecret', 'mexp-23' ), array( $this, 'form_field' ), 'v23-setting-admin', 'setting_section_id', array( 'type'=> 'text', 'label_for' => 'accesstokensecret' ) );
		add_settings_field( 'chosenmexpservices', __( 'Other MEXP Services', 'mexp-23' ), array( $this, 'choose_mexp_services' ), 'v23-setting-admin', 'setting_section_id', array( 'label_for' => 'chosenmexpservices', 'description' => __( 'Select to enable MEXP Services. (CTRL-click/Option-click to choose multiple/unselect services)', 'mexp-23' ) ) );
	}


	public function store_all_mexp_services( $services ) {
		if ( false === ( $all_mexp_services = get_transient( 'all_mexp_services' ) ) ) {
			set_transient( 'all_mexp_services', $services, 60 );
		}
		return $services;
	}

	public function chosen_mexp_services( $services ) {
		$options = get_option( 'v23_options' );
		if ( ! isset( $options['chosenmexpservices'] ) ) {
			return $services;
		}

		//printf("<pre>%s</pre>", print_r($options,true));

		if ( false !== ( $all_mexp_services = get_transient( 'all_mexp_services' ) ) ) {
			foreach ( $all_mexp_services as $service_id => $service ) {
				if ( in_array( $service_id, $options['chosenmexpservices'] ) ) {
					$chosen_sevices[$service_id] = $service;
				}
			}
			return $chosen_sevices;
		} else {
			return $services;
		}
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array   $input Contains all settings fields as array keys
	 */
	public function input_validation( $input ) {
		$sanitized_input = $input;
		if ( isset( $input['apiurl'] ) ) {
			$sanitized_input['apiurl'] = untrailingslashit( esc_url( $input['apiurl'] ) );

		}

		require_once MEXP_23_DIR . 'lib/class-wp-23-video.php';
		extract( $sanitized_input, EXTR_SKIP );

		$client = new WP_23_Video( $apiurl, $consumerkey, $consumersecret, $accesstoken, $accesstokensecret );
		$ping_response = $client->get( '/api/echo', array( 'ping' => 'pong' ) );
		if ( is_wp_error( $ping_response ) ) {
			$error_string = $ping_response->get_error_message();
			add_settings_error(
				'v23_api',
				'v23_apierror',
				$error_string,
				'error'
			);
		}
		return $sanitized_input;
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info() {
		_e( 'See <a href="http://www.23video.com/api/oauth#setting-up-your-application" target="_blank">"Setting up your application"</a> to learn how to obtain Consumer Key etc.', 'mexp-23' );
	}


	public function form_field( $args ) {
		// global $wp_settings_sections,  $wp_settings_fields;
		//printf("<pre>%s</pre>", print_r($this->options,true));

		$defaults = array(
			'type' => 'text',

		);
		$args = wp_parse_args( $args, $defaults );
		extract( $args, EXTR_SKIP );
		if ( ! isset( $label_for ) ) {
			echo __( "\$label_for is missing", 'mexp-23' );
			return;
		}

		switch ( $type ) {
		case 'value':
			// code...
			break;
		case 'text':
		default:
			printf( '<input type="text" id="%s" name="v23_options[%s]" value="%s" class="regular-text" />', $label_for, $label_for,
				isset( $this->options[$label_for] ) ? esc_attr( $this->options[$label_for] ) : ''
			);
			break;
		}
		if ( isset( $description ) ) {
			printf( '<p class="description">%s</p>', $description );
		}
	}


	public function choose_mexp_services( $args ) {

		extract( $args, EXTR_SKIP );
		if ( ! isset( $label_for ) ) {
			echo __( "\$label_for is missing", 'mexp-23' );
			return;
		}

		if ( false !== ( $all_mexp_services = get_transient( 'all_mexp_services' ) ) ) {
			foreach ( $all_mexp_services as $service_id => $service ) {
				$all_mexp_services_ids[] = $service_id;
			}
			printf( '<select id="%s" name="v23_options[%s][]" multiple="multiple" size="%s" style="width:25em;">', $label_for, $label_for, count( $all_mexp_services_ids ) - 1 );
			foreach ( $all_mexp_services_ids as $service_id ) {
				if ( !isset( $this->options['chosenmexpservices'] ) /*|| '23_mexp_service' == $service_id*/ ) {
					$selected = ' selected="selected" ';
				} else if ( '23_mexp_service' == $service_id ) {
						continue;
					} else {
					$selected = ( in_array( $service_id, $this->options['chosenmexpservices'] ) ) ? ' selected="selected" ' : '';
				}
				printf( '<option value="%s"%s>%s</option>', $service_id, $selected, $service_id );
			}
			echo "</select>";
			echo '<input type="hidden" value="23_mexp_service" name="v23_options[chosenmexpservices][]" />';
		}
		if ( isset( $description ) ) {
			printf( '<p class="description">%s</p>', $description );
		}
		//printf("<pre>%s</pre>", print_r($this->options,true));

	}

}

if ( is_admin() )
	$v23_settings_page = new V23_Settings_Page();
