<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://xyz.com
 * @since      1.0.0
 *
 * @package    Soft_Media_Delete
 * @subpackage Soft_Media_Delete/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Soft_Media_Delete
 * @subpackage Soft_Media_Delete/includes
 * @author     Faheem <faheemahmed10293@gmail.com>
 */
class Soft_Media_Delete_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'soft-media-delete',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
