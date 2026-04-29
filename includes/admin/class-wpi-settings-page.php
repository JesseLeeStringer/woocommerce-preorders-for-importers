<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPI_Settings_Page extends WC_Settings_Page {

	public function __construct() {
		$this->id    = 'wpi_preorders';
		$this->label = __( 'Preorders', 'wpi' );
		parent::__construct();
	}

	public function get_settings(): array {
		// Build a categories option list for the multiselect.
		$category_options = [];
		$terms = get_terms( [
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		] );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$category_options[ $term->term_id ] = $term->name;
			}
		}

		return apply_filters( 'wpi_settings', [
			[
				'title' => __( 'Preorder Display', 'wpi' ),
				'type'  => 'title',
				'id'    => 'wpi_display_options',
			],
			[
				'title'   => __( 'Loop badge text', 'wpi' ),
				'type'    => 'text',
				'id'      => 'wpi_badge_text',
				'default' => __( 'Pre-Order', 'wpi' ),
				'desc'    => __( 'Small badge shown on shop archive listings.', 'wpi' ),
			],
			[
				'title'   => __( 'Archive button label', 'wpi' ),
				'type'    => 'text',
				'id'      => 'wpi_archive_button_text',
				'default' => __( 'Pre-Orders Available', 'wpi' ),
				'desc'    => __( 'Replaces the Add to Cart / Out of Stock button on shop and category pages. Customers click through to the product page to read shipment details before ordering.', 'wpi' ),
			],
			[
				'title'   => __( 'Date display format', 'wpi' ),
				'type'    => 'text',
				'id'      => 'wpi_date_format',
				'default' => 'd/m/Y',
				'desc'    => __( 'PHP date format string, e.g. d/m/Y', 'wpi' ),
			],
			[
				'type' => 'sectionend',
				'id'   => 'wpi_display_options',
			],
			[
				'title' => __( 'Eligibility', 'wpi' ),
				'type'  => 'title',
				'id'    => 'wpi_eligibility_options',
				'desc'  => __( 'Limit which products can be added to a shipment as a preorder line item.', 'wpi' ),
			],
			[
				'title'    => __( 'Excluded categories', 'wpi' ),
				'type'     => 'multiselect',
				'class'    => 'wc-enhanced-select',
				'id'       => 'wpi_excluded_categories',
				'options'  => $category_options,
				'default'  => [],
				'desc_tip' => true,
				'desc'     => __( 'Products in any of these categories cannot be preordered. Use this for service / labour / installation products that are not physical goods.', 'wpi' ),
			],
			[
				'type' => 'sectionend',
				'id'   => 'wpi_eligibility_options',
			],
			[
				'title' => __( 'Checkout', 'wpi' ),
				'type'  => 'title',
				'id'    => 'wpi_checkout_options',
			],
			[
				'title'   => __( 'Checkout notice', 'wpi' ),
				'type'    => 'textarea',
				'id'      => 'wpi_checkout_notice',
				'default' => __( 'Your order includes preorder items. You are paying a deposit only. The balance will be invoiced separately when your order is ready to dispatch.', 'wpi' ),
			],
			[
				'type' => 'sectionend',
				'id'   => 'wpi_checkout_options',
			],
			[
				'title' => __( 'Notifications', 'wpi' ),
				'type'  => 'title',
				'id'    => 'wpi_notification_options',
				'desc'  => __( 'The Preorder Placed admin email is configured under WooCommerce → Settings → Emails → "New Preorder (Admin)". Use the field below as the default recipient when the email is first installed.', 'wpi' ),
			],
			[
				'title'   => __( 'Default admin recipient', 'wpi' ),
				'type'    => 'email',
				'id'      => 'wpi_admin_email',
				'default' => get_option( 'admin_email' ),
				'desc'    => __( 'Used as the default recipient for the Preorder Placed admin email. The email-specific recipient field overrides this once configured.', 'wpi' ),
			],
			[
				'type' => 'sectionend',
				'id'   => 'wpi_notification_options',
			],
		] );
	}
}
