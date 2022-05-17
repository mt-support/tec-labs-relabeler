<?php
/**
 * Plugin Class.
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Relabeler
 */

namespace Tribe\Extensions\Relabeler;

/**
 * Class Plugin
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Relabeler
 */
class Plugin extends \tad_DI52_ServiceProvider {
	/**
	 * Stores the version for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const VERSION = '2.0.0';

	/**
	 * Stores the base slug for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const SLUG = 'relabeler';

	/**
	 * Stores the base slug for the extension.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const FILE = TEC_LABS_RELABELER_FILE;

	/**
	 * @since 1.0.0
	 *
	 * @var string Plugin Directory.
	 */
	public $plugin_dir;

	/**
	 * @since 1.0.0
	 *
	 * @var string Plugin path.
	 */
	public $plugin_path;

	/**
	 * @since 1.0.0
	 *
	 * @var string Plugin URL.
	 */
	public $plugin_url;

	/**
	 * @since 1.0.0
	 *
	 * @var Settings
	 *
	 * TODO: Remove if not using settings
	 */
	private $settings;

	/**
	 * Caches labels that are retrieved from the database.
	 *
	 * @var array {
	 *      @type $option_name string Full text for the altered label
	 * }
	 */
	protected $label_cache = [];

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		// Set up the plugin provider properties.
		$this->plugin_path = trailingslashit( dirname( static::FILE ) );
		$this->plugin_dir  = trailingslashit( basename( $this->plugin_path ) );
		$this->plugin_url  = plugins_url( $this->plugin_dir, $this->plugin_path );

		// Register this provider as the main one and use a bunch of aliases.
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'extension.relabeler', $this );
		$this->container->singleton( 'extension.relabeler.plugin', $this );
		$this->container->register( PUE::class );

		if ( ! $this->check_plugin_dependencies() ) {
			// If the plugin dependency manifest is not met, then bail and stop here.
			return;
		}

		// Do the settings.
		// TODO: Remove if not using settings
		$this->get_settings();

		// Start binds.

		// Events.
		add_filter( 'tribe_event_label_singular', [ $this, 'get_event_single' ] );
		add_filter( 'tribe_event_label_singular_lowercase', [ $this, 'get_event_single_lowercase' ] );
		add_filter( 'tribe_event_label_plural', [ $this, 'get_event_plural' ] );
		add_filter( 'tribe_event_label_plural_lowercase', [ $this, 'get_event_plural_lowercase' ] );

		// Venues.
		add_filter( 'tribe_venue_label_singular', [ $this, 'get_venue_single' ] );
		add_filter( 'tribe_venue_label_singular_lowercase', [ $this, 'get_venue_single_lowercase' ] );
		add_filter( 'tribe_venue_label_plural', [ $this, 'get_venue_plural' ] );
		add_filter( 'tribe_venue_label_plural_lowercase', [ $this, 'get_venue_plural_lowercase' ] );

		// Organizers.
		add_filter( 'tribe_organizer_label_singular', [ $this, 'get_organizer_single' ] );
		add_filter( 'tribe_organizer_label_singular_lowercase', [ $this, 'get_organizer_single_lowercase' ] );
		add_filter( 'tribe_organizer_label_plural', [ $this, 'get_organizer_plural' ] );
		add_filter( 'tribe_organizer_label_plural_lowercase', [ $this, 'get_organizer_plural_lowercase' ] );

		// End binds.

		$this->container->register( Hooks::class );
		$this->container->register( Assets::class );
	}

	/**
	 * Checks whether the plugin dependency manifest is satisfied or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the plugin dependency manifest is satisfied or not.
	 */
	protected function check_plugin_dependencies() {
		$this->register_plugin_dependencies();

		return tribe_check_plugin( static::class );
	}

	/**
	 * Registers the plugin and dependency manifest among those managed by Tribe Common.
	 *
	 * @since 1.0.0
	 */
	protected function register_plugin_dependencies() {
		$plugin_register = new Plugin_Register();
		$plugin_register->register_plugin();

		$this->container->singleton( Plugin_Register::class, $plugin_register );
		$this->container->singleton( 'extension.relabeler', $plugin_register );
	}

	/**
	 * Get this plugin's options prefix.
	 *
	 * Settings_Helper will append a trailing underscore before each option.
	 *
	 * @return string
     *
	 * @see \Tribe\Extensions\Relabeler\Settings::set_options_prefix()
	 *
	 * TODO: Remove if not using settings
	 */
	private function get_options_prefix() {
		return (string) str_replace( '-', '_', 'tec-labs-relabeler' );
	}

	/**
	 * Get Settings instance.
	 *
	 * @return Settings
	 *
	 * TODO: Remove if not using settings
	 */
	private function get_settings() {
		if ( empty( $this->settings ) ) {
			$this->settings = new Settings( $this->get_options_prefix() );
		}

		return $this->settings;
	}

	/**
	 * Get all of this extension's options.
	 *
	 * @return array
	 *
	 * TODO: Remove if not using settings
	 */
	public function get_all_options() {
		$settings = $this->get_settings();

		return $settings->get_all_options();
	}

	/**
	 * Get a specific extension option.
	 *
	 * @param $option
	 * @param string $default
	 *
	 * @return array
	 *
	 * TODO: Remove if not using settings
	 */
	public function get_option( $option, $default = '' ) {
		$settings = $this->get_settings();

		return $settings->get_option( $option, $default );
	}

	/**
	 * Gets the label from the database and caches it
	 *
	 * @param $key     string Option key for the label.
	 * @param $default string Value to return if none set.
	 *
	 * @return string|null
	 */
	public function get_label( $key, $default = null ) {

		$key = $this->get_options_prefix() . "_" . $key;

		if ( ! isset( $this->label_cache[ $key ] ) ) {
			$this->label_cache[ $key ] = tribe_get_option( $key, $default );
		}

		return $this->label_cache[ $key ];
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_event_single( $label ) {
		return $this->get_label( 'label_event_single', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_event_single_lowercase( $label ) {
		return $this->get_label( 'label_event_single_lowercase', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_event_plural( $label ) {
		return $this->get_label( 'label_event_plural', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_event_plural_lowercase( $label ) {
		return $this->get_label( 'label_event_plural_lowercase', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_venue_single( $label ) {
		return $this->get_label( 'label_venue_single', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_venue_single_lowercase( $label ) {
		return $this->get_label( 'label_venue_single_lowercase', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_venue_plural( $label ) {
		return $this->get_label( 'label_venue_plural', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_venue_plural_lowercase( $label ) {
		return $this->get_label( 'label_venue_plural_lowercase', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_organizer_single( $label ) {
		return $this->get_label( 'label_organizer_single', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_organizer_single_lowercase( $label ) {
		return $this->get_label( 'label_organizer_single_lowercase', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_organizer_plural( $label ) {
		return $this->get_label( 'label_organizer_plural', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_organizer_plural_lowercase( $label ) {
		return $this->get_label( 'label_organizer_plural_lowercase',  $label );
	}
}
