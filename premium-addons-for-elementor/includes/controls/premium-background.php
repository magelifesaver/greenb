<?php

/**
 * Class: Premium_Background
 * Name:  Premium Background
 * Slug:  premium-background
 */

namespace PremiumAddons\Includes\Controls;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Premium background control.
 *
 * A base control for creating background control. Displays input fields to define
 * the background color, background image, background gradient or background video.
 *
 * @since 4.11.34
 */
class Premium_Background extends Group_Control_Background {

	/**
	 * Fields.
	 *
	 * Holds all the background control fields.
	 *
	 * @since 1.2.2
	 * @access protected
	 * @static
	 *
	 * @var array Background control fields.
	 */
	protected static $fields;

	/**
	 * Get background control type.
	 *
	 * Retrieve the control type, in this case `background`.
	 *
	 * @return string Control type.
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 */
	public static function get_type() {
		return 'pa-background';
	}

	/**
	 * Init fields.
	 *
	 * Initialize background control fields.
	 *
	 * @return array Control fields.
	 * @since 1.2.2
	 * @access public
	 *
	 */
	public function init_fields() {
		$fields = [];

		$fields['background'] = [
			'label'       => esc_html_x( 'Background Type', 'Background Control', 'premium-addons-for-elementor' ),
			'type'        => Controls_Manager::CHOOSE,
			'render_type' => 'ui',
		];

		$fields['color'] = [
			'label'     => esc_html_x( 'Color', 'Background Control', 'premium-addons-for-elementor' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '',
			'title'     => esc_html_x( 'Background Color', 'Background Control', 'premium-addons-for-elementor' ),
			'selectors' => [
				'{{SELECTOR}}' => 'background-color: {{VALUE}};',
			],
			'condition' => [
				'background' => [ 'classic', 'gradient', 'video' ],
			],
		];

		$fields['color_stop'] = [
			'label'       => esc_html_x( 'Location', 'Background Control', 'premium-addons-for-elementor' ),
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => [ '%', 'custom' ],
			'default'     => [
				'unit' => '%',
				'size' => 0,
			],
			'render_type' => 'ui',
			'condition'   => [
				'background' => [ 'gradient' ],
			],
			'of_type'     => 'gradient',
		];

		$fields['color_b'] = [
			'label'       => esc_html_x( 'Second Color', 'Background Control', 'premium-addons-for-elementor' ),
			'type'        => Controls_Manager::COLOR,
			'default'     => '#f2295b',
			'render_type' => 'ui',
			'condition'   => [
				'background' => [ 'gradient' ],
			],
			'of_type'     => 'gradient',
		];

		$fields['color_b_stop'] = [
			'label'       => esc_html_x( 'Location', 'Background Control', 'premium-addons-for-elementor' ),
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => [ '%', 'custom' ],
			'default'     => [
				'unit' => '%',
				'size' => 100,
			],
			'render_type' => 'ui',
			'condition'   => [
				'background' => [ 'gradient' ],
			],
			'of_type'     => 'gradient',
		];

		$fields['gradient_type'] = [
			'label'       => esc_html_x( 'Type', 'Background Control', 'premium-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'options'     => [
				'linear' => esc_html_x( 'Linear', 'Background Control', 'premium-addons-for-elementor' ),
				'radial' => esc_html_x( 'Radial', 'Background Control', 'premium-addons-for-elementor' ),
			],
			'default'     => 'linear',
			'render_type' => 'ui',
			'condition'   => [
				'background' => [ 'gradient' ],
			],
			'of_type'     => 'gradient',
		];

		$fields['gradient_angle'] = [
			'label'      => esc_html_x( 'Angle', 'Background Control', 'premium-addons-for-elementor' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'deg', 'grad', 'rad', 'turn', 'custom' ],
			'default'    => [
				'unit' => 'deg',
				'size' => 180,
			],
			'selectors'  => [
				'{{SELECTOR}}' => 'background-color: transparent; background-image: linear-gradient({{SIZE}}{{UNIT}}, {{color.VALUE}} {{color_stop.SIZE}}{{color_stop.UNIT}}, {{color_b.VALUE}} {{color_b_stop.SIZE}}{{color_b_stop.UNIT}})',
			],
			'condition'  => [
				'background'    => [ 'gradient' ],
				'gradient_type' => 'linear',
			],
			'of_type'    => 'gradient',
		];

		$fields['gradient_position'] = [
			'label'     => esc_html_x( 'Position', 'Background Control', 'premium-addons-for-elementor' ),
			'type'      => Controls_Manager::SELECT,
			'options'   => [
				'center center' => esc_html_x( 'Center Center', 'Background Control', 'premium-addons-for-elementor' ),
				'center left'   => esc_html_x( 'Center Left', 'Background Control', 'premium-addons-for-elementor' ),
				'center right'  => esc_html_x( 'Center Right', 'Background Control', 'premium-addons-for-elementor' ),
				'top center'    => esc_html_x( 'Top Center', 'Background Control', 'premium-addons-for-elementor' ),
				'top left'      => esc_html_x( 'Top Left', 'Background Control', 'premium-addons-for-elementor' ),
				'top right'     => esc_html_x( 'Top Right', 'Background Control', 'premium-addons-for-elementor' ),
				'bottom center' => esc_html_x( 'Bottom Center', 'Background Control', 'premium-addons-for-elementor' ),
				'bottom left'   => esc_html_x( 'Bottom Left', 'Background Control', 'premium-addons-for-elementor' ),
				'bottom right'  => esc_html_x( 'Bottom Right', 'Background Control', 'premium-addons-for-elementor' ),
			],
			'default'   => 'center center',
			'selectors' => [
				'{{SELECTOR}}' => 'background-color: transparent; background-image: radial-gradient(at {{VALUE}}, {{color.VALUE}} {{color_stop.SIZE}}{{color_stop.UNIT}}, {{color_b.VALUE}} {{color_b_stop.SIZE}}{{color_b_stop.UNIT}})',
			],
			'condition' => [
				'background'    => [ 'gradient' ],
				'gradient_type' => 'radial',
			],
			'of_type'   => 'gradient',
		];

		$fields['image'] = [
			'label'       => esc_html_x( 'Image', 'Background Control', 'premium-addons-for-elementor' ),
			'type'        => Controls_Manager::MEDIA,
			'ai'          => [
				'category' => 'background',
			],
			'dynamic'     => [
				'active' => true,
			],
			'responsive'  => false,
			'title'       => esc_html_x( 'Background Image', 'Background Control', 'premium-addons-for-elementor' ),
			'selectors'   => [
				'{{SELECTOR}}' => 'background-image: url("{{URL}}");',
			],
			'has_sizes'   => true,
			'render_type' => 'template',
			'condition'   => [
				'background' => [ 'classic' ],
			],
		];

		$fields['position'] = [
			'label'      => esc_html_x( 'Position', 'Background Control', 'premium-addons-for-elementor' ),
			'type'       => Controls_Manager::SELECT,
			'default'    => '',
			'separator'  => 'before',
			'responsive' => false,
			'options'    => [
				''              => esc_html_x( 'Default', 'Background Control', 'premium-addons-for-elementor' ),
				'center center' => esc_html_x( 'Center Center', 'Background Control', 'premium-addons-for-elementor' ),
				'center left'   => esc_html_x( 'Center Left', 'Background Control', 'premium-addons-for-elementor' ),
				'center right'  => esc_html_x( 'Center Right', 'Background Control', 'premium-addons-for-elementor' ),
				'top center'    => esc_html_x( 'Top Center', 'Background Control', 'premium-addons-for-elementor' ),
				'top left'      => esc_html_x( 'Top Left', 'Background Control', 'premium-addons-for-elementor' ),
				'top right'     => esc_html_x( 'Top Right', 'Background Control', 'premium-addons-for-elementor' ),
				'bottom center' => esc_html_x( 'Bottom Center', 'Background Control', 'premium-addons-for-elementor' ),
				'bottom left'   => esc_html_x( 'Bottom Left', 'Background Control', 'premium-addons-for-elementor' ),
				'bottom right'  => esc_html_x( 'Bottom Right', 'Background Control', 'premium-addons-for-elementor' ),

			],
			'selectors'  => [
				'{{SELECTOR}}' => 'background-position: {{VALUE}};',
			],
			'condition'  => [
				'background'  => [ 'classic' ],
				'image[url]!' => '',
			],
		];

		$fields['attachment'] = [
			'label'     => esc_html_x( 'Attachment', 'Background Control', 'premium-addons-for-elementor' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '',
			'options'   => [
				''       => esc_html_x( 'Default', 'Background Control', 'premium-addons-for-elementor' ),
				'scroll' => esc_html_x( 'Scroll', 'Background Control', 'premium-addons-for-elementor' ),
				'fixed'  => esc_html_x( 'Fixed', 'Background Control', 'premium-addons-for-elementor' ),
			],
			'selectors' => [
				'(desktop+){{SELECTOR}}' => 'background-attachment: {{VALUE}};',
			],
			'condition' => [
				'background'  => [ 'classic' ],
				'image[url]!' => '',
			],
		];

		$fields['attachment_alert'] = [
			'type'            => Controls_Manager::RAW_HTML,
			'content_classes' => 'elementor-control-field-description',
			'raw'             => esc_html__( 'Note: Attachment Fixed works only on desktop.', 'premium-addons-for-elementor' ),
			'separator'       => 'none',
			'condition'       => [
				'background'  => [ 'classic' ],
				'image[url]!' => '',
				'attachment'  => 'fixed',
			],
		];

		$fields['repeat'] = [
			'label'      => esc_html_x( 'Repeat', 'Background Control', 'premium-addons-for-elementor' ),
			'type'       => Controls_Manager::SELECT,
			'default'    => '',
			'responsive' => false,
			'options'    => [
				''          => esc_html_x( 'Default', 'Background Control', 'premium-addons-for-elementor' ),
				'no-repeat' => esc_html_x( 'No-repeat', 'Background Control', 'premium-addons-for-elementor' ),
				'repeat'    => esc_html_x( 'Repeat', 'Background Control', 'premium-addons-for-elementor' ),
				'repeat-x'  => esc_html_x( 'Repeat-x', 'Background Control', 'premium-addons-for-elementor' ),
				'repeat-y'  => esc_html_x( 'Repeat-y', 'Background Control', 'premium-addons-for-elementor' ),
			],
			'selectors'  => [
				'{{SELECTOR}}' => 'background-repeat: {{VALUE}};',
			],
			'condition'  => [
				'background'  => [ 'classic' ],
				'image[url]!' => '',
			],
		];

		$fields['size'] = [
			'label'      => esc_html_x( 'Display Size', 'Background Control', 'premium-addons-for-elementor' ),
			'type'       => Controls_Manager::SELECT,
			'responsive' => false,
			'default'    => '',
			'options'    => [
				''        => esc_html_x( 'Default', 'Background Control', 'premium-addons-for-elementor' ),
				'auto'    => esc_html_x( 'Auto', 'Background Control', 'premium-addons-for-elementor' ),
				'cover'   => esc_html_x( 'Cover', 'Background Control', 'premium-addons-for-elementor' ),
				'contain' => esc_html_x( 'Contain', 'Background Control', 'premium-addons-for-elementor' ),
			],
			'selectors'  => [
				'{{SELECTOR}}' => 'background-size: {{VALUE}};',
			],
			'condition'  => [
				'background'  => [ 'classic' ],
				'image[url]!' => '',
			],
		];

		return $fields;
	}

}
