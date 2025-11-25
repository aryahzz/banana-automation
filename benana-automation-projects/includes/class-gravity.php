<?php
class Benana_Automation_Gravity {
    public function __construct() {
        add_action( 'gform_after_submission', array( $this, 'handle_submission' ), 10, 2 );
    }

    public function handle_submission( $entry, $form ) {
        if ( ! function_exists( 'rgar' ) ) {
            return;
        }
        $settings = Benana_Automation_Settings::get_settings();
        $form_id  = rgar( $form, 'id' );
        if ( empty( $settings['gravity_forms'][ $form_id ] ) ) {
            return;
        }

        $form_settings = $settings['gravity_forms'][ $form_id ];
        $city_field    = $form_settings['city_field'];
        $mobile_field  = $form_settings['mobile_field'];
        $file_field    = $form_settings['file_field'];

        $province_id = substr( rgar( $entry, $city_field ), 0, 2 );
        $city_id     = rgar( $entry, $city_field );
        $project_id  = wp_insert_post( array(
            'post_type'   => 'project',
            'post_title'  => 'پروژه جدید #' . $form_id . '-' . rgar( $entry, 'id' ),
            'post_status' => 'publish',
        ) );

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
}
