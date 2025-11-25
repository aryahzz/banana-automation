<?php

defined( 'ABSPATH' ) || exit;

class GFPersian_Adress extends GFPersian_Core {

	public function __construct() {

		if ( $this->option( 'address', '1' ) != '1' ) {
			return;
		}

		add_action( 'gform_editor_js', [ $this, 'custom_options_editor_js' ] );
		add_action( 'gform_field_standard_settings', [ $this, 'custom_options' ], 10, 2 );
		add_filter( 'gform_address_types', [ $this, 'iran_address_type' ] );
		add_filter( 'gform_predefined_choices', [ $this, 'iran_provinces_choices' ], 1 );
		add_filter( 'gform_field_content', [ $this, 'iran_cities_field_type' ], 10, 5 );
		add_action( 'gform_register_init_scripts', [ $this, 'init_script' ], 10, 1 );
		add_action( 'gform_enqueue_scripts', [ $this, 'external_js' ], 10, 2 );
	}

	/**
	 * Iran provinces list
	 *
	 * @return array
	 */
	public function iran_provinces(): array {
		return [
			'آذربایجان شرقی',
			'آذربایجان غربی',
			'اردبیل',
			'اصفهان',
			'البرز',
			'ایلام',
			'بوشهر',
			'تهران',
			'چهارمحال و بختیاری',
			'خراسان جنوبی',
			'خراسان رضوی',
			'خراسان شمالی',
			'خوزستان',
			'زنجان',
			'سمنان',
			'سیستان و بلوچستان',
			'فارس',
			'قزوین',
			'قم',
			'کردستان',
			'کرمان',
			'کرمانشاه',
			'کهگیلویه و بویراحمد',
			'گلستان',
			'گیلان',
			'لرستان',
			'مازندران',
			'مرکزی',
			'هرمزگان',
			'همدان',
			'یزد'
		];
	}

	/**
	 * Add Iran address type to GForms
	 *
	 * @filter gform_address_types
	 *
	 * @param array $addressTypes Contains the details for existing address types.
	 *
	 * @return array
	 */
	public function iran_address_type( array $address_types ): array {

		$address_types['iran'] = [
			'label'       => 'ایران',
			'country'     => 'ایران',
			'zip_label'   => 'کدپستی',
			'state_label' => 'استان',
			'states'      => array_merge( [ '' ], $this->iran_provinces() )
		];

		return $address_types;
	}

	/**
	 * Set values for predefined province in field
	 *
	 * @filter gform_predefined_choices
	 *
	 * @param array $choices
	 *
	 * @return array
	 */
	public function iran_provinces_choices( array $choices ): array {

		$states['استان های ایران'] = $this->iran_provinces();

		return array_merge( $states, $choices );
	}

	/**
	 * Add custom options to the address fields
	 *
	 * @action  gform_field_standard_settings
	 *
	 * @param int $position
	 * @param string $form_id
	 *
	 * @return void
	 */
	public function custom_options( int $position, string $form_id ): void {
		if ( $position == 25 ) { ?>
			<li class="iran_cities field_setting">
				<input type="checkbox" id="iran_cities"
				       onclick="SetFieldProperty('iran_cities', jQuery(this).is(':checked') ? 1 : 0);"/>
				<label class="inline gfield_value_label" for="iran_cities">فعالسازی شهرهای ایران</label>
			</li>

			<li class="switch_state_city_position field_setting">
				<input type="checkbox" id="switch_state_city_position"
				       onclick="SetFieldProperty('switch_state_city_position', jQuery(this).is(':checked') ? 1 : 0);"/>
				<label class="inline gfield_value_label" for="switch_state_city_position">جابجایی فیلد شهر و استان</label>
			</li>
			<?php
		}
	}

	/**
	 * Add iran cities to form edit field logic
	 *
	 * @action gform_editor_js
	 *
	 * @return void
	 */
	public function custom_options_editor_js(): void { ?>

		<script type='text/javascript'>
            jQuery(document).ready(function ($) {

                fieldSettings["address"] += ", .iran_cities, .switch_state_city_position";
                const field_address_type_el = $('#field_address_type');

                $(document).bind("gform_load_field_settings", function (event, field, form) {

                    // Iran Cities option
                    const iran_cities_el = $("#iran_cities");
                    const iran_cities_container_el = $('#iran_cities_div');
                    iran_cities_el.attr("checked", field["iran_cities"] == true);

                    if (!iran_cities_container_el.length) {

                        let iran_cities = $(".iran_cities");
                        let iran_cities_input = iran_cities.html();
                        iran_cities.remove();
                        field_address_type_el.after('<div id="iran_cities_div"><br>' + iran_cities_input + '</div>');

                    }

                    // Switch state and city field option
                    const switch_state_city_position_el = $("#switch_state_city_position");
                    const switch_state_city_position_container_el = $('#switch_state_city_position_div');
                    switch_state_city_position_el.attr("checked", field["switch_state_city_position"] == true);

                    if (!switch_state_city_position_container_el.length) {
                        let switch_state_city_position = $(".switch_state_city_position");
                        let switch_state_city_position_input = switch_state_city_position.html();
                        switch_state_city_position.remove();
                        field_address_type_el.after('<div id="switch_state_city_position_div"><br>' + switch_state_city_position_input + '</div>');

                    }

                    // Conditional logic control of custom options
                    if (field_address_type_el.val() === 'iran') {

                        iran_cities_container_el.show();
                        switch_state_city_position_container_el.show();

                    } else {

                        iran_cities_container_el.hide();
                        switch_state_city_position_container_el.hide();

                    }

                    field_address_type_el.change(function () {

                        if ($(this).val() === 'iran') {

                            iran_cities_container_el.slideDown();
                            switch_state_city_position_container_el.slideDown();

                        } else {

                            iran_cities_container_el.slideUp();
                            switch_state_city_position_container_el.slideUp();

                        }

                    });

                });

            });
		</script>

		<?php
	}

	/**
	 * Add Iran cities list to address field
	 *
	 * @filter gform_field_content
	 *
	 * @param string $content The field content
	 * @param GF_Field|array $field The Field Object
	 * @param array|string $value The field value
	 * @param int $lead_id The entry ID
	 * @param string $form_id The form ID
	 *
	 * @return string
	 *
	 */
	public function iran_cities_field_type( string $content, $field, $value, int $lead_id, string $form_id ): string {

		if ( ! $this->is_iran_cities( $field ) ) {
			return $content;
		}

		$id = rgar( $field, 'id', 0 );
		$id = absint( $id );

		preg_match( '/<input.*?(name=["\']input_' . $id . '.3["\'].*?)\/??>/i', $content, $match );

		if ( ! empty( $match[0] ) && ! empty( $match[1] ) ) {

			$city_input = trim( $match[1] );
			$city_input = str_ireplace( 'value=', 'data-selected=', $city_input );
			$content    = str_replace( $match[0], "<select {$city_input}><option value='' selected='selected'>&nbsp;&nbsp;</option></select>", $content );

		}

		return $content;
	}

	/**
	 * Add Iran city assets to the GForms address field presentation
	 *
	 * @action gform_enqueue_scripts
	 *
	 * @param array $form An array representing the current Form object.
	 * @param bool $ajax Whether this is being requested via AJAX.
	 *
	 * @return void
	 */
	public function external_js( array $form, bool $ajax ): void {

		$fields = GFCommon::get_fields_by_type( $form, [ 'address' ] );

		foreach ( $fields as $field ) {

			if ( ! $this->is_iran_cities( $field ) ) {
				continue;
			}

			wp_dequeue_script( 'gform_iran_cities' );
			wp_deregister_script( 'gform_iran_cities' );

			wp_register_script( 'gform_iran_cities', GF_PERSIAN_URL . 'assets/js/iran-cities-full' . GFPersian_Core::minified() . '.js', [], GF_PERSIAN_VERSION, false );
			wp_enqueue_script( 'gform_iran_cities' );

		}
	}


	/**
	 * Register Iran city input frontend logic when it presents
	 *
	 * @action gform_register_init_scripts
	 *
	 * @param array $form The Form object
	 *
	 * @return void
	 */
	public function init_script( array $form ): void {

		foreach ( $form['fields'] as $field ) {

			if ( ! $this->is_iran_cities( $field ) ) {
				continue;
			}

			$id = $form['id'] . '_' . $field['id'];

			$script = 'jQuery(document).ready(function($){' . '$(".has_city #input_' . $id . '_3").html(persian_gravity_form_iran_cities(""+$(".has_city #input_' . $id . '_4").val()));' . 'if ($(".has_city #input_' . $id . '_3").attr("data-selected")) {' . '$(".has_city #input_' . $id . '_3").val($(".has_city #input_' . $id . '_3").attr("data-selected"));' . '}' . '$(document.body).on("change autocomplete", ".has_city #input_' . $id . '_4" ,function(){' . '$(".has_city #input_' . $id . '_3").html(persian_gravity_form_iran_cities(""+$(".has_city #input_' . $id . '_4").val()));' . '}).on("change", ".has_city #input_' . $id . '_3" ,function(){' . '$(this).attr("data-selected", $(this).val());' . '})' . '});';

			if ( $this->switch_state_city_position( $field ) ) {
				$script .= 'jQuery(document).ready(function($){var $parent=$("#input_' . $id . '"),$city=$("#input_' . $id . '_3_container"),$state=$("#input_' . $id . '_4_container");if($parent.length&&$city.length&&$state.length){$state.insertBefore($city);}});';
			}

			GFFormDisplay::add_init_script( $form['id'], 'iran_address_city_' . $id, GFFormDisplay::ON_PAGE_RENDER, $script );

		}
	}

	/**
	 * Check Iran cities option enabled
	 *
	 * @param GF_Field|array $field
	 *
	 * @return bool
	 */
	private function is_iran_cities( $field ): bool {
		return $field['type'] == 'address' && $field['addressType'] == 'iran' && rgar( $field, 'iran_cities', false ) && ! is_admin();
	}

	/**
	 * Check state and city position switch enabled
	 *
	 * @param GF_Field|array $field
	 *
	 * @return bool
	 */
	private function switch_state_city_position( $field ): bool {
		return $field['type'] == 'address' && rgar( $field, 'switch_state_city_position', false ) && ! is_admin();
	}

}


new GFPersian_Adress();