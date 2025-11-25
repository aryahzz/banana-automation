<?php
class Benana_Automation_Gravity {
    public function __construct() {
        add_action( 'gform_after_submission', array( $this, 'handle_submission' ), 10, 2 );
        add_action( 'gform_post_payment_completed', array( $this, 'handle_payment_complete' ), 10, 2 );
    }

    public function handle_submission( $entry, $form ) {
        $payment_status = function_exists( 'rgar' ) ? rgar( $entry, 'payment_status' ) : '';

        // برای فرم‌های پرداختی، فقط بعد از تکمیل پرداخت پروژه ساخته می‌شود.
        if ( ! empty( $payment_status ) ) {
            return;
        }

        $this->maybe_create_project( $entry, $form, false );
    }

    public function handle_payment_complete( $entry, $action ) {
        if ( isset( $action['is_success'] ) && ! $action['is_success'] ) {
            return;
        }

        $form = function_exists( 'GFAPI' ) ? GFAPI::get_form( rgar( $entry, 'form_id' ) ) : array();
        $this->maybe_create_project( $entry, $form, true );
    }

    private function maybe_create_project( $entry, $form, $from_payment_hook = false ) {
        if ( ! function_exists( 'rgar' ) ) {
            return;
        }

        $settings = Benana_Automation_Settings::get_settings();
        $form_id  = rgar( $form, 'id' );
        if ( empty( $form_id ) ) {
            $form_id = rgar( $entry, 'form_id' );
        }

        if ( empty( $settings['gravity_forms'][ $form_id ] ) ) {
            return;
        }

        if ( $this->project_exists_for_entry( rgar( $entry, 'id' ) ) ) {
            return;
        }

        $form_settings  = $settings['gravity_forms'][ $form_id ];
        $city_field     = $form_settings['city_field'];
        $province_field = isset( $form_settings['province_field'] ) ? $form_settings['province_field'] : '';
        $mobile_field   = $form_settings['mobile_field'];

        $payment_status = rgar( $entry, 'payment_status' );
        if ( ! $from_payment_hook && ! empty( $payment_status ) ) {
            return;
        }

        $city_raw     = rgar( $entry, $city_field );
        $province_raw = $province_field ? rgar( $entry, $province_field ) : '';

        $location    = Benana_Automation_Address::normalize_location( $province_raw, $city_raw );
        $city_id     = $location['city_id'];
        $province_id = $location['province_id'];
        $project_id  = wp_insert_post( array(
            'post_type'   => 'project',
            'post_title'  => 'پروژه جدید #' . $form_id . '-' . rgar( $entry, 'id' ),
            'post_status' => 'publish',
        ) );

        if ( is_wp_error( $project_id ) ) {
            return;
        }

        $assigned_users = Benana_Automation_Project_Handler::find_assignees( $province_id, $city_id );

        update_post_meta( $project_id, 'project_status', 'new' );
        update_post_meta( $project_id, 'gf_entry_id', rgar( $entry, 'id' ) );
        update_post_meta( $project_id, 'gf_form_id', $form_id );
        update_post_meta( $project_id, 'project_city_id', $city_id );
        update_post_meta( $project_id, 'project_province_id', $province_id );
        update_post_meta( $project_id, 'assigned_users', wp_json_encode( $assigned_users ) );
        update_post_meta( $project_id, 'client_mobile', rgar( $entry, $mobile_field ) );
        update_post_meta( $project_id, 'timestamps', wp_json_encode( array( 'created' => current_time( 'mysql' ) ) ) );

        foreach ( $assigned_users as $user_id ) {
            Benana_Automation_Project_Handler::send_assignment_sms( $project_id, $user_id, $entry );
        }
    }

    private function project_exists_for_entry( $entry_id ) {
        $query = new WP_Query( array(
            'post_type'  => 'project',
            'meta_query' => array(
                array(
                    'key'   => 'gf_entry_id',
                    'value' => $entry_id,
                ),
            ),
            'fields'     => 'ids',
            'post_status'=> 'any',
        ) );

        return ( $query->found_posts > 0 );
    }
}
