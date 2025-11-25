<?php
class Benana_Automation_Project_Handler {
    public static function find_assignees( $province_id, $city_id ) {
        $city_id     = is_string( $city_id ) ? trim( $city_id ) : $city_id;
        $province_id = is_string( $province_id ) ? trim( $province_id ) : $province_id;

        if ( '' === (string) $city_id ) {
            return array();
        }

        $meta_query = array(
            'relation' => 'AND',
            array(
                'relation' => 'OR',
                array(
                    'key'     => 'user_is_active',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => 'user_is_active',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
        );

        if ( ! empty( $province_id ) ) {
            $meta_query[] = array(
                'key'     => 'user_province_id',
                'value'   => $province_id,
                'compare' => '=',
            );
        }

        $users = get_users(
            array(
                'meta_query' => $meta_query,
            )
        );

        $filtered = array();
        foreach ( $users as $user ) {
            $user_cities    = array_map( 'strval', (array) get_user_meta( $user->ID, 'user_city_ids', true ) );
            $inactive_until = get_user_meta( $user->ID, 'user_inactive_until', true );

            if ( ! in_array( $city_id, $user_cities, true ) ) {
                continue;
            }

            if ( '' === $inactive_until || empty( $inactive_until ) ) {
                $filtered[] = $user->ID;
                continue;
            }

            if ( intval( $inactive_until ) === -1 ) {
                continue;
            }

            if ( intval( $inactive_until ) < time() ) {
                $filtered[] = $user->ID;
            }
        }

        return $filtered;
    }

    public static function send_assignment_sms( $project_id, $user_id, $entry ) {
        $settings    = Benana_Automation_Settings::get_settings();
        $sms_helper  = new Benana_Automation_SMS();
        $template    = $settings['sms_templates']['assign'];
        $mobile      = get_user_meta( $user_id, 'mobile', true );
        $context     = self::build_context( $project_id, $user_id, $entry );
        $message     = $sms_helper->parse_tags( $template, $context );
        $sms_helper->send_sms( $mobile, $message );
    }

    public static function accept_project( $project_id, $user_id ) {
        $accepted = get_post_meta( $project_id, 'accepted_by', true );
        if ( ! empty( $accepted ) ) {
            return false;
        }
        update_post_meta( $project_id, 'accepted_by', $user_id );
        update_post_meta( $project_id, 'project_status', 'accepted' );
        $entry = self::get_entry_for_project( $project_id );
        self::send_acceptance_sms( $project_id, $user_id, $entry );
        return true;
    }

    public static function reject_project( $project_id, $user_id ) {
        $assigned = json_decode( get_post_meta( $project_id, 'assigned_users', true ), true );
        if ( is_array( $assigned ) ) {
            $assigned = array_diff( $assigned, array( $user_id ) );
            update_post_meta( $project_id, 'assigned_users', wp_json_encode( $assigned ) );
        }
    }

    public static function upload_file( $project_id, $user_id, $file_url ) {
        if ( get_post_meta( $project_id, 'accepted_by', true ) != $user_id ) {
            return false;
        }
        update_post_meta( $project_id, 'file_url', esc_url_raw( $file_url ) );
        update_post_meta( $project_id, 'project_status', 'file_uploaded' );
        $entry = self::get_entry_for_project( $project_id );
        self::send_file_uploaded_sms( $project_id, $user_id, $entry );
        return true;
    }

    public static function complete_project( $project_id ) {
        update_post_meta( $project_id, 'project_status', 'completed' );
        $entry      = self::get_entry_for_project( $project_id );
        $accepted   = get_post_meta( $project_id, 'accepted_by', true );
        self::send_complete_sms( $project_id, $accepted, $entry );
    }

    public static function build_context( $project_id, $user_id, $entry ) {
        $province_id = get_post_meta( $project_id, 'project_province_id', true );
        $city_id     = get_post_meta( $project_id, 'project_city_id', true );
        $city_name   = Benana_Automation_Address::get_city_name( $province_id, $city_id );
        $provinces   = Benana_Automation_Address::get_provinces();
        $project     = get_post( $project_id );
        return array(
            'project_id'       => $project_id,
            'project_title'    => $project ? $project->post_title : '',
            'project_status'   => get_post_meta( $project_id, 'project_status', true ),
            'project_city'     => $city_name,
            'project_province' => isset( $provinces[ $province_id ] ) ? $provinces[ $province_id ] : '',
            'project_url'      => get_permalink( $project_id ),
            'file_url'         => get_post_meta( $project_id, 'file_url', true ),
            'assignee_name'    => get_user_by( 'id', $user_id )->display_name,
            'assignee_mobile'  => get_user_meta( $user_id, 'mobile', true ),
            'client_name'      => get_post_meta( $project_id, 'client_user_id', true ),
            'client_mobile'    => get_post_meta( $project_id, 'client_mobile', true ),
            'gf_entry'         => $entry,
        );
    }

    public static function get_entry_for_project( $project_id ) {
        $entry_id = get_post_meta( $project_id, 'gf_entry_id', true );
        if ( function_exists( 'GFAPI' ) && ! empty( $entry_id ) ) {
            return GFAPI::get_entry( $entry_id );
        }
        return array();
    }

    private static function send_acceptance_sms( $project_id, $user_id, $entry ) {
        $settings   = Benana_Automation_Settings::get_settings();
        $sms_helper = new Benana_Automation_SMS();
        $context    = self::build_context( $project_id, $user_id, $entry );

        $assignee_message = $sms_helper->parse_tags( $settings['sms_templates']['accepted_assignee'], $context );
        $client_message   = $sms_helper->parse_tags( $settings['sms_templates']['accepted_client'], $context );

        $sms_helper->send_sms( $context['assignee_mobile'], $assignee_message );
        $sms_helper->send_sms( $context['client_mobile'], $client_message );
    }

    private static function send_file_uploaded_sms( $project_id, $user_id, $entry ) {
        $settings   = Benana_Automation_Settings::get_settings();
        $sms_helper = new Benana_Automation_SMS();
        $context    = self::build_context( $project_id, $user_id, $entry );
        $message    = $sms_helper->parse_tags( $settings['sms_templates']['file_uploaded'], $context );
        $sms_helper->send_sms( $context['client_mobile'], $message );
    }

    private static function send_complete_sms( $project_id, $user_id, $entry ) {
        $settings   = Benana_Automation_Settings::get_settings();
        $sms_helper = new Benana_Automation_SMS();
        $context    = self::build_context( $project_id, $user_id, $entry );
        $message    = $sms_helper->parse_tags( $settings['sms_templates']['completed'], $context );
        $sms_helper->send_sms( $context['assignee_mobile'], $message );
        $sms_helper->send_sms( $context['client_mobile'], $message );
    }
}
