<?php
/**
 * Elementor widget.
 *
 * @package DealerInventory
 */

namespace DealerInventory\Integrations;

use DealerInventory\Assets;
use DealerInventory\Elementor;
use DealerInventory\Schema;
use DealerInventory\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Vehicle Inventory" widget. Controls are generated from the settings
 * schema; empty values inherit the site-wide settings.
 */
final class Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget name.
	 */
	public function get_name() {
		return Elementor::WIDGET;
	}

	/**
	 * Widget title.
	 */
	public function get_title() {
		return __( 'Vehicle Inventory', 'dealer-inventory-for-autoscout24' );
	}

	/**
	 * Widget icon.
	 */
	public function get_icon() {
		return 'eicon-posts-grid';
	}

	/**
	 * Widget categories.
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * Search keywords.
	 */
	public function get_keywords() {
		return array( 'autoscout24', 'cars', 'vehicles', 'inventory', 'dealer' );
	}

	/**
	 * Front-end styles.
	 */
	public function get_style_depends() {
		return array( Assets::HANDLE );
	}

	/**
	 * Output is dynamic (vehicles change with every sync).
	 */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/**
	 * Controls from the schema.
	 */
	protected function register_controls() {
		$schema = Schema::editor_schema();
		foreach ( $schema['groups'] as $group => $title ) {
			$this->start_controls_section(
				'dinv_' . $group,
				array(
					'label' => $title,
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			foreach ( $schema['fields'] as $field ) {
				if ( $group !== $field['group'] ) {
					continue;
				}
				$this->add_control( 'dinv_' . $field['attr'], $this->control( $field ) );
			}

			$this->end_controls_section();
		}
	}

	/**
	 * Elementor control arguments for a schema field.
	 *
	 * @param array $field Editor field.
	 * @return array<string, mixed>
	 */
	private function control( array $field ): array {
		$inherit = 'preset' === $field['group']
			? __( 'Any', 'dealer-inventory-for-autoscout24' )
			/* translators: %s: current site-wide value. */
			: sprintf( __( 'Default (%s)', 'dealer-inventory-for-autoscout24' ), '' !== $field['global'] ? $field['global'] : '—' );

		$args = array(
			'label'       => $field['label'],
			'description' => $field['help'],
			'label_block' => true,
		);

		$options = array();
		foreach ( (array) ( $field['options'] ?? array() ) as $option ) {
			$options[ $option['value'] ] = $option['label'];
		}

		switch ( $field['type'] ) {
			case 'bool':
				return $args + array(
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => '',
					'options' => array(
						''    => $inherit,
						'yes' => __( 'Yes', 'dealer-inventory-for-autoscout24' ),
						'no'  => __( 'No', 'dealer-inventory-for-autoscout24' ),
					),
				);

			case 'multi':
			case 'list':
				return $args + array(
					'type'        => \Elementor\Controls_Manager::SELECT2,
					'multiple'    => true,
					'default'     => array(),
					'options'     => $options,
					'description' => trim( $field['help'] . ' ' . __( 'Leave empty to use the site-wide setting.', 'dealer-inventory-for-autoscout24' ) . ( 'list' === $field['type'] ? ' ' . __( 'Shown in the order selected.', 'dealer-inventory-for-autoscout24' ) : '' ) ),
				);

			case 'int':
			case 'number':
				return $args + array(
					'type'        => \Elementor\Controls_Manager::NUMBER,
					'default'     => '',
					'min'         => $field['min'] ?? 0,
					'max'         => $field['max'] ?? null,
					'placeholder' => $field['global'],
				);
		}

		if ( $options ) {
			return $args + array(
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => array( '' => $inherit ) + $options,
			);
		}

		return $args + array(
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '',
			'placeholder' => $field['global'],
		);
	}

	/**
	 * Render.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$atts     = array();
		foreach ( Schema::instance_fields() as $attr => $field ) {
			$value = $settings[ 'dinv_' . $attr ] ?? '';
			if ( is_array( $value ) ) {
				$value = implode( ',', array_map( 'strval', $value ) );
			}
			if ( is_scalar( $value ) && '' !== (string) $value ) {
				$atts[ $attr ] = (string) $value;
			}
		}
		echo Shortcode::render( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered by the plugin templates.
	}
}
