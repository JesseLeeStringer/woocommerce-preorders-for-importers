<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers WPI dynamic tags with Elementor's Dynamic Content system so
 * page builders can drop preorder status / stock / date as native fields
 * inside Elementor templates (Theme Builder, Pro single-product templates, etc.).
 */
class WPI_Elementor {

	public function __construct() {
		add_action( 'elementor/dynamic_tags/register', [ $this, 'register' ], 10, 1 );
	}

	/**
	 * Compatible with both new and legacy Elementor APIs.
	 *
	 * @param mixed $manager Elementor\Core\DynamicTags\Manager (new) or callable signature varies by version.
	 */
	public function register( $manager ): void {
		// Resolve the manager regardless of which signature Elementor passes.
		if ( ! is_object( $manager ) || ! method_exists( $manager, 'register' ) ) {
			return;
		}

		// Group container.
		if ( method_exists( $manager, 'register_group' ) ) {
			$manager->register_group( 'wpi-preorder', [
				'title' => __( 'Preorder', 'wpi' ),
			] );
		}

		require_once WPI_PLUGIN_DIR . 'includes/elementor/dynamic-tags/class-wpi-tag-status.php';
		require_once WPI_PLUGIN_DIR . 'includes/elementor/dynamic-tags/class-wpi-tag-stock.php';
		require_once WPI_PLUGIN_DIR . 'includes/elementor/dynamic-tags/class-wpi-tag-date.php';

		$manager->register( new WPI_Tag_Status() );
		$manager->register( new WPI_Tag_Stock() );
		$manager->register( new WPI_Tag_Date() );
	}
}
