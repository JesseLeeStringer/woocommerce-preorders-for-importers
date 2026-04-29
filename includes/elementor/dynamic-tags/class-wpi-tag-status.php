<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Tag_Status extends \Elementor\Core\DynamicTags\Tag {

	public function get_name(): string {
		return 'wpi-preorder-status';
	}

	public function get_title(): string {
		return __( 'POI Status', 'wpi' );
	}

	public function get_group(): string {
		return 'wpi-preorder';
	}

	public function get_categories(): array {
		return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
	}

	public function render(): void {
		echo do_shortcode( '[preorder_status wrap="no"]' );
	}
}
