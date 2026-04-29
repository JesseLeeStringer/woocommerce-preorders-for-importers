<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Tag_Date extends \Elementor\Core\DynamicTags\Tag {

	public function get_name(): string {
		return 'wpi-preorder-date';
	}

	public function get_title(): string {
		return __( 'POI Arrival Date', 'wpi' );
	}

	public function get_group(): string {
		return 'wpi-preorder';
	}

	public function get_categories(): array {
		return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
	}

	protected function register_controls(): void {
		$this->add_control(
			'date_format',
			[
				'label'       => __( 'Date format', 'wpi' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'd/m/Y',
				'description' => __( 'PHP date format. Leave empty to use the global plugin setting.', 'wpi' ),
			]
		);
		$this->add_control(
			'prefix',
			[
				'label'       => __( 'Prefix text', 'wpi' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( 'e.g. Arrives:', 'wpi' ),
			]
		);
	}

	public function render(): void {
		$format = (string) $this->get_settings( 'date_format' );
		$prefix = (string) $this->get_settings( 'prefix' );
		echo do_shortcode( sprintf(
			'[preorder_date format="%s" prefix="%s"]',
			esc_attr( $format ),
			esc_attr( $prefix )
		) );
	}
}
