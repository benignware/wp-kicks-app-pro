<?php
/**
 * Customize API: WP_Customize_Color_Control class
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Customize Color Control class.
 *
 * @since 3.4.0
 *
 * @see WP_Customize_Control
 */

 if (class_exists('WP_Customize_Control')) {
	class Theme_Customize_Control extends WP_Customize_Control {
		/**
		 * Type.
		 *
		 * @var string
		 */
		public $type = 'variable';

		/**
		 * Statuses.
		 *
		 * @var array
		 */
		public $statuses;

		/**
		 * Mode.
		 *
		 * @since 4.7.0
		 * @var string
		 */
		public $mode = 'full';



	  public $var = '';

		/**
		 * Constructor.
		 *
		 * @since 3.4.0
		 * @uses WP_Customize_Control::__construct()
		 *
		 * @param WP_Customize_Manager $manager Customizer bootstrap instance.
		 * @param string               $id      Control ID.
		 * @param array                $args    Optional. Arguments to override class property defaults.
		 */
		public function __construct( $manager, $id, $args = array() ) {
			$this->statuses = array( '' => __( 'Default' ) );
	    $this->var = $args['settings'];
			parent::__construct( $manager, $id, $args );

		}

		/**
		 * Enqueue scripts/styles for the color picker.
		 *
		 * @since 3.4.0
		 */
		public function enqueue() {
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );

	    wp_enqueue_script( 'themalize' );
		}

		/**
		 * Refresh the parameters passed to the JavaScript via JSON.
		 *
		 * @since 3.4.0
		 * @uses WP_Customize_Control::to_json()
		 */
		public function to_json() {
			parent::to_json();
			$this->json['statuses']     = $this->statuses;
			$this->json['defaultValue'] = $this->setting->default;
			$this->json['mode']         = $this->mode;
	    $this->json['var']         = $this->var;
      $this->json['choices']     = $this->choices;
		}

		/**
		 * Don't render the control content from PHP, as it's rendered via JS on load.
		 *
		 * @since 3.4.0
		 */
		public function render_content() {}

		/**
		 * Render a JS template for the content of the color picker control.
		 *
		 * @since 4.1.0
		 */
		public function content_template() {
			?>
			<# if ( data.label ) { #>
				<span class="customize-control-title">{{{ data.label }}}</span>
			<# } #>
			<# if ( data.description ) { #>
				<span class="description customize-control-description">{{{ data.description }}}</span>
			<# } #>
			<div class="customize-control-content">
				<label>
          <span class="screen-reader-text">{{{ data.label }}}</span>
          <# if (data.choices && Object.keys(data.choices).length) { #>
            <select
              data-theme-setting="{{{data.var}}}"
              data-theme-control="select"
              data-theme-default="{{{data.defaultValue}}}"
              placeholder="{{ data.defaultValue }}"
            >
              <# for (var key in data.choices) { #>
                <option value="{{{key}}}">{{{data.choices[key]}}}</option>
              <# } #>
            </select>
          <# } else { #>
            <input
              data-theme-setting="{{data.var}}"
              data-theme-default="{{data.defaultValue}}"
              placeholder="{{data.defaultValue}}" type="text" data-theme-control="text"
            />
          <# } #>
        </label>
			</div>
			<?php
		}
	}

}
