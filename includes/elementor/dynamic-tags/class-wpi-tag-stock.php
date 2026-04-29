<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Tag_Stock extends \Elementor\Core\DynamicTags\Tag {

	public function get_name(): string {
		return 'wpi-preorder-stock';
	}

	public function get_title(): string {
		return __( 'POI Stock', 'wpi' );
	}

	public function get_group(): string {
		return 'wpi-preorder';
	}

	public function get_categories(): array {
		return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
	}

	protected function register_controls(): void {
		$this->add_control(
			'format',
			[
				'label'   => __( 'Format', 'wpi' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => [
					'text'    => __( 'Labelled rows (In stock: 5 / Available: 12 …)', 'wpi' ),
					'long'    => __( 'Long form (multi-sentence)', 'wpi' ),
					'numbers' => __( 'Numbers only (5 · 12)', 'wpi' ),
				],
				'default' => 'text',
			]
		);
	}

	public function render(): void {
		$format = $this->get_settings( 'format' ) ?: 'text';
		echo do_shortcode( '[preorder_stock format="' . esc_attr( $format ) . '"]' );
	}
}
