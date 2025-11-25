<?php
class Benana_Automation_Project_Handler {
    public static function find_assignees( $province_id, $city_id ) {
        $city_id     = is_string( $city_id ) ? trim( $city_id ) : $city_id;
        $province_id = is_string( $province_id ) ? trim( $province_id ) : $province_id;
        $city_id     = (string) $city_id;
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
            $user_province  = get_user_meta( $user->ID, 'user_province_id', true );

            $record = array(
                'user'      => $user->ID,
                'cities'    => $user_cities,
                'province'  => $user_province,
                'active'    => get_user_meta( $user->ID, 'user_is_active', true ),
                'inactive'  => $inactive_until,
                'matched'   => false,
                'reason'    => '',
            );

            if ( ! in_array( $city_id, $user_cities, true ) ) {
                continue;
            }

            if ( '' === $inactive_until || empty( $inactive_until ) ) {
                $filtered[] = $user->ID;
                $record['matched'] = true;
                continue;
            }

            if ( intval( $inactive_until ) === -1 ) {
                continue;
            }

            if ( intval( $inactive_until ) < time() ) {
                $filtered[] = $user->ID;
                $record['matched'] = true;
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

        if ( ! self::user_is_assignee( $project_id, $user_id ) ) {
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

    public static function user_is_assignee( $project_id, $user_id ) {
        $raw_assigned = get_post_meta( $project_id, 'assigned_users', true );
        $assigned     = json_decode( $raw_assigned, true );

        if ( ! is_array( $assigned ) ) {
            $maybe = maybe_unserialize( $raw_assigned );
            if ( is_array( $maybe ) ) {
                $assigned = $maybe;
            }
        }

        if ( ! is_array( $assigned ) && ! empty( $raw_assigned ) ) {
            $assigned = array_map( 'intval', array_filter( array_map( 'trim', explode( ',', (string) $raw_assigned ) ) ) );
        }

        if ( ! is_array( $assigned ) ) {
            $assigned = array();
        }

        $assigned   = array_map( 'intval', $assigned );
        $accepted_by = get_post_meta( $project_id, 'accepted_by', true );
        return ( in_array( intval( $user_id ), $assigned, true ) || intval( $accepted_by ) === intval( $user_id ) );
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
            $entry = GFAPI::get_entry( $entry_id );
            if ( ! is_wp_error( $entry ) && ! empty( $entry ) ) {
                return $entry;
            }
        }
        $snapshot = self::get_entry_snapshot( $project_id );
        return $snapshot['entry'];
    }

    public static function get_entry_snapshot( $project_id ) {
        $raw = get_post_meta( $project_id, 'gf_entry_snapshot', true );
        $decoded = json_decode( $raw, true );
        if ( ! is_array( $decoded ) ) {
            $decoded = array();
        }
        return array(
            'entry'   => isset( $decoded['entry'] ) && is_array( $decoded['entry'] ) ? $decoded['entry'] : array(),
            'display' => isset( $decoded['display'] ) && is_array( $decoded['display'] ) ? $decoded['display'] : array(),
            'labels'  => isset( $decoded['labels'] ) && is_array( $decoded['labels'] ) ? $decoded['labels'] : array(),
        );
    }

    public static function store_entry_snapshot( $project_id, $form, $entry ) {
        if ( empty( $project_id ) || empty( $entry ) ) {
            return;
        }

        $clean_entry = array();
        foreach ( $entry as $key => $value ) {
            if ( is_array( $value ) ) {
                $clean_entry[ $key ] = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $value ) );
            } else {
                $clean_entry[ $key ] = sanitize_text_field( wp_unslash( $value ) );
            }
        }

        $display = array();
        $labels  = array();

        if ( ! empty( $form['fields'] ) && class_exists( 'GFCommon' ) ) {
            foreach ( $form['fields'] as $field ) {
                $fid = is_object( $field ) ? $field->id : ( $field['id'] ?? '' );
                if ( '' === $fid ) {
                    continue;
                }

                $raw        = rgar( $entry, $fid );
                $displayed  = GFCommon::get_lead_field_display( $field, $raw, $entry['currency'] ?? '', true, 'html' );
                $labels[ $fid ] = is_object( $field ) ? $field->label : ( $field['label'] ?? $fid );

                if ( '' === trim( wp_strip_all_tags( (string) $displayed ) ) ) {
                    $displayed = is_array( $raw ) ? implode( ', ', array_filter( array_map( 'trim', (array) $raw ) ) ) : $raw;
                }

                $display[ $fid ] = $displayed;
            }
        }

        update_post_meta(
            $project_id,
            'gf_entry_snapshot',
            wp_json_encode(
                array(
                    'entry'   => $clean_entry,
                    'display' => $display,
                    'labels'  => $labels,
                )
            )
        );
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
