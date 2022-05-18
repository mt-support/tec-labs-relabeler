<?php
/**
 * Settings Object.
 *
 * @package Tribe\Extensions\Relabeler
 * @since   1.0.0
 *
 */

namespace Tribe\Extensions\Relabeler;

use Tribe__Settings_Manager;
use Tribe__Settings_Tab;

/**
 * Do the Settings.
 *
 * TODO: Delete file if not using settings
 */
class Settings {

	/**
	 * The Settings Helper class.
	 *
	 * @var Settings_Helper
	 */
	protected $settings_helper;

	/**
	 * The prefix for our settings keys.
	 *
	 * @see get_options_prefix() Use this method to get this property's value.
	 *
	 * @var string
	 */
	private $options_prefix = '';

	/**
	 * Settings constructor.
	 *
	 * TODO: Update this entire class for your needs, or remove the entire `src` directory this file is in and do not load it in the main plugin file.
	 *
	 * @param string $options_prefix Recommended: the plugin text domain, with hyphens converted to underscores.
	 */
	public function __construct( $options_prefix ) {
		$this->settings_helper = new Settings_Helper();

		$this->set_options_prefix( $options_prefix );

		// Create the settings panel
		//add_action( 'tribe_settings_do_tabs', [ $this, 'add_settings_tabs' ] );

		// Add settings specific to OSM
		add_action( 'admin_init', [ $this, 'add_settings' ] );
	}

	/**
	 * Allow access to set the Settings Helper property.
	 *
	 * @see get_settings_helper()
	 *
	 * @param Settings_Helper $helper
	 *
	 * @return Settings_Helper
	 */
	public function set_settings_helper( Settings_Helper $helper ) {
		$this->settings_helper = $helper;

		return $this->get_settings_helper();
	}

	/**
	 * Allow access to get the Settings Helper property.
	 *
	 * @see set_settings_helper()
	 */
	public function get_settings_helper() {
		return $this->settings_helper;
	}

	/**
	 * Set the options prefix to be used for this extension's settings.
	 *
	 * Recommended: the plugin text domain, with hyphens converted to underscores.
	 * Is forced to end with a single underscore. All double-underscores are converted to single.
	 *
	 * @see get_options_prefix()
	 *
	 * @param string $options_prefix
	 */
	private function set_options_prefix( $options_prefix = '' ) {
		if ( empty( $opts_prefix ) ) {
			$opts_prefix = str_replace( '-', '_', 'tec-labs-relabeler' ); // The text domain.
		}

		$opts_prefix = $opts_prefix . '_';

		$this->options_prefix = str_replace( '__', '_', $opts_prefix );
	}

	/**
	 * Get this extension's options prefix.
	 *
	 * @see set_options_prefix()
	 *
	 * @return string
	 */
	public function get_options_prefix() {
		return $this->options_prefix;
	}

	/**
	 * Given an option key, get this extension's option value.
	 *
	 * This automatically prepends this extension's option prefix so you can just do `$this->get_option( 'a_setting' )`.
	 *
	 * @see tribe_get_option()
	 *
	 * @param string $key
	 * @param string $default
	 *
	 * @return mixed
	 */
	public function get_option( $key = '', $default = '' ) {
		$key = $this->sanitize_option_key( $key );

		return tribe_get_option( $key, $default );
	}

	/**
	 * Get an option key after ensuring it is appropriately prefixed.
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	private function sanitize_option_key( $key = '' ) {
		$prefix = $this->get_options_prefix();

		if ( 0 === strpos( $key, $prefix ) ) {
			$prefix = '';
		}

		return $prefix . $key;
	}

	/**
	 * Get an array of all of this extension's options without array keys having the redundant prefix.
	 *
	 * @return array
	 */
	public function get_all_options() {
		$raw_options = $this->get_all_raw_options();

		$result = [];

		$prefix = $this->get_options_prefix();

		foreach ( $raw_options as $key => $value ) {
			$abbr_key            = str_replace( $prefix, '', $key );
			$result[ $abbr_key ] = $value;
		}

		return $result;
	}

	/**
	 * Get an array of all of this extension's raw options (i.e. the ones starting with its prefix).
	 *
	 * @return array
	 */
	public function get_all_raw_options() {
		$tribe_options = Tribe__Settings_Manager::get_options();

		if ( ! is_array( $tribe_options ) ) {
			return [];
		}

		$result = [];

		foreach ( $tribe_options as $key => $value ) {
			if ( 0 === strpos( $key, $this->get_options_prefix() ) ) {
				$result[ $key ] = $value;
			}
		}

		return $result;
	}

	/**
	 * Given an option key, delete this extension's option value.
	 *
	 * This automatically prepends this extension's option prefix so you can just do `$this->delete_option( 'a_setting' )`.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function delete_option( $key = '' ) {
		$key = $this->sanitize_option_key( $key );

		$options = Tribe__Settings_Manager::get_options();

		unset( $options[ $key ] );

		return Tribe__Settings_Manager::set_options( $options );
	}

	/**
	 * Register the settings tab and fields.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_settings_tabs() {

		// Create the settings tab.
		//$settings_tab = new Tribe__Settings_Tab( 'relabeler', __( 'Relabeler', 'tec-labs-relabeler' ), $this->add_settings() );
	}

	/**
	 * Adds a new section of fields to Events > Settings > General tab, appearing after the "Map Settings" section
	 * and before the "Miscellaneous Settings" section.
	 *
	 * TODO: Move the setting to where you want and update this docblock. If you like it here, just delete this TODO.
	 */
	public function add_settings() {
		$fields = [
			'views_heading'      => [
				'type' => 'html',
				'html' => '<h3>' . esc_html__( 'Views', 'tec-labs-relabeler' ) . '</h3>',
			],
			'views_helper_text'  => [
				'type' => 'html',
				'html' => '<p>' . esc_html__( 'The following fields allow you to change the default names of the views. Inputting something other than the default will change that word everywhere it appears.', 'tec-labs-relabeler' ) . '</p>',
			],
			'label_list_view'  => [
				'type'            => 'text',
				'label'           => esc_html__( 'List view', 'the-events-calendar' ),
				'default'         => esc_attr__( 'List', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Label for list view.', 'tec-labs-relabeler' ),
				'validation_type' => 'html',
			],
			'label_month_view' => [
				'type'            => 'text',
				'label'           => esc_html__( 'Month view', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Month', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Label for month view.', 'tec-labs-relabeler' ),
				'validation_type' => 'html',
			],
			'label_day_view'   => [
				'type'            => 'text',
				'label'           => esc_html__( 'Day view', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Day', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Label for day view.', 'tec-labs-relabeler' ),
				'validation_type' => 'html',
			],
		];

		$fields_ecp_views = [
			'label_week_view'    => [
				'type'            => 'text',
				'label'           => esc_html__( 'Week view', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Week', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Label for week view.', 'tec-labs-relabeler' ),
				'validation_type' => 'html',
			],
			'label_map_view'     => [
				'type'            => 'text',
				'label'           => esc_html__( 'Map view', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Map', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Label for map view.', 'tec-labs-relabeler' ),
				'validation_type' => 'html',
			],
			'label_photo_view'   => [
				'type'            => 'text',
				'label'           => esc_html__( 'Photo view', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Photo', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Label for photo view.', 'tec-labs-relabeler' ),
				'validation_type' => 'html',
			],
			'label_summary_view' => [
				'type'            => 'text',
				'label'           => esc_html__( 'Summary view', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Summary', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Label for summary view.', 'tec-labs-relabeler' ),
				'validation_type' => 'html',
			],
		];

		$fields_rewrite = [
			'rewrite_view_slugs' => [
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Re-write view slugs', 'tec-labs-relabeler' ),
				'tooltip'         => esc_html__( 'When enabled the slugs of the views will be also changed (with the exception of Map view). You will need to flush permalinks after changing this setting.', 'tec-labs-relabeler' ),
				'validation_type' => 'boolean',
				'default'         => false,
			],
		];

		$fields_labels = [
			'labels_heading'                   => [
				'type' => 'html',
				'html' => '<h3>' . esc_html__( 'Labels', 'tec-labs-relabeler' ) . '</h3>',
			],
			'labels_helper_text'               => [
				'type' => 'html',
				'html' => '<p>' . esc_html__( 'The following fields allow you to change the default labels. Inputting something other than the default will change that word everywhere it appears.', 'tec-labs-relabeler' ) . '</p>',
			],
			'label_event_single'               => [
				'type'            => 'text',
				'label'           => esc_html__( 'Event', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Event', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Singular label for Events.', 'tec-labs-relabeler' ),
				'validation_type' => 'html',
			],
			'label_event_single_lowercase'     => [
				'type'            => 'text',
				'label'           => esc_html__( 'event', 'the-events-calendar' ),
				'default'         => esc_attr__( 'event', 'the-events-calendar' ),
				'tooltip'         => sprintf(
					esc_html__( 'Lowercase singular label for Events. You might wish to also modify the "Events URL Slug" found in the %s events settings tab.', 'tec-labs-relabeler' ),
					$this->general_settings_tab_link()
				),
				'validation_type' => 'html',
			],
			'label_event_plural'               => [
				'type'            => 'text',
				'label'           => esc_html__( 'Events', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Events', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Plural label for Events.', 'tec-labs-relabeler' ),
				'validation_type' => 'html',
			],
			'label_event_plural_lowercase'     => [
				'type'            => 'text',
				'label'           => esc_html__( 'events', 'the-events-calendar' ),
				'default'         => esc_attr__( 'events', 'the-events-calendar' ),
				'tooltip'         => sprintf(
					esc_html__( 'Lowercase plural label for Events. You might wish to also modify the "Single Event URL Slug" found in the %s events settings tab.', 'tec-labs-relabeler' ),
					$this->general_settings_tab_link()
				),
				'validation_type' => 'html',
			],
			'label_venue_single'               => [
				'type'            => 'text',
				'label'           => esc_html__( 'Venue', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Venue', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Singular label for Venues.', 'tec-labs-relabeler' ),
				'validation_type' => 'html',
			],
			'label_venue_single_lowercase'     => [
				'type'            => 'text',
				'label'           => esc_html__( 'venue', 'the-events-calendar' ),
				'default'         => esc_attr__( 'venue', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Lowercase singular label for Venues.', 'tec-labs-relabeler' ),
				'validation_type' => 'html',
			],
			'label_venue_plural'               => [
				'type'            => 'text',
				'label'           => esc_html__( 'Venues', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Venues', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Plural label for Venues.', 'tec-labs-relabeler' ),
				'validation_type' => 'html',
			],
			'label_venue_plural_lowercase'     => [
				'type'            => 'text',
				'label'           => esc_html__( 'venues', 'the-events-calendar' ),
				'default'         => esc_attr__( 'venues', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Lowercase plural label for Venues.', 'tec-labs-relabeler' ),
				'validation_type' => 'html',
			],
			'label_organizer_single'           => [
				'type'            => 'text',
				'label'           => esc_html__( 'Organizer', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Organizer', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Singular label for Organizers.', 'tec-labs-relabeler' ),
				'validation_type' => 'html',
			],
			'label_organizer_single_lowercase' => [
				'type'            => 'text',
				'label'           => esc_html__( 'organizer', 'the-events-calendar' ),
				'default'         => esc_attr__( 'organizer', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Lowercase singular label for Organizers.', 'tec-labs-relabeler' ),
				'validation_type' => 'html',
			],
			'label_organizer_plural'           => [
				'type'            => 'text',
				'label'           => esc_html__( 'Organizers', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Organizers', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Plural label for Organizers.', 'tec-labs-relabeler' ),
				'validation_type' => 'html',
			],
			'label_organizer_plural_lowercase' => [
				'type'            => 'text',
				'label'           => esc_html__( 'organizers', 'the-events-calendar' ),
				'default'         => esc_attr__( 'organizers', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Lowercase plural label for Organizers.', 'tec-labs-relabeler' ),
				'validation_type' => 'html',
			],
		];

		if ( class_exists( 'Tribe__Events__Pro__Main' ) ) {
			$fields = array_merge( $fields, $fields_ecp_views );
		}

		$fields = array_merge( $fields, $fields_rewrite, $fields_labels );

		$this->settings_helper->add_fields(
			$this->prefix_settings_field_keys( $fields ),
			'display',
			'tribeEventsDateFormatSettingsTitle',
			true
		);

		return $fields;
	}

	/**
	 * Add the options prefix to each of the array keys.
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	private function prefix_settings_field_keys( array $fields ) {
		$prefixed_fields = array_combine(
			array_map(
				function ( $key ) {
					return $this->get_options_prefix() . $key;
				}, array_keys( $fields )
			),
			$fields
		);

		return (array) $prefixed_fields;
	}

	/**
	 * Here is an example of getting some HTML for the Settings Header.
	 *
	 * TODO: Delete this method if you do not need a heading for your settings. Also remove the corresponding element in the the $fields array in the `add_settings()` method above.
	 *
	 * @return string
	 */
	private function get_example_intro_text() {
		$result = '<h3>' . esc_html_x( 'Example Extension Setup', 'Settings header', 'tec-labs-relabeler' ) . '</h3>';
		$result .= '<div style="margin-left: 20px;">';
		$result .= '<p>';
		$result .= esc_html_x( 'Some text here about this settings section.', 'Setting section description', 'tec-labs-relabeler' );
		$result .= '</p>';
		$result .= '</div>';

		return $result;
	}

	/**
	 * Get an HTML link to the General settings tab
	 *
	 * @return string HTML link element to the general settings tab
	 */
	protected function general_settings_tab_link() {
		$url = \Tribe__Settings::instance()->get_url( [ 'tab' => 'general' ] );

		return sprintf(
			'<a href="%2$s">%1$s</a>',
			esc_html__( 'General', 'tribe-common' ),
			esc_url( $url )
		);
	}

}
