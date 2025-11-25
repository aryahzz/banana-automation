<?php
class Benana_Automation_SMS {
    public function send_sms( $mobile, $message ) {
        $message = (string) ( $message ?? '' );
        $numbers = array();

        foreach ( (array) $mobile as $single ) {
            $single = trim( (string) $single );
            if ( '' !== $single ) {
                $numbers[] = $single;
            }
        }

        if ( empty( $numbers ) || '' === $message ) {
            return false;
        }

        if ( function_exists( 'wp_sms_send' ) ) {
            $result = wp_sms_send( $numbers, $message );
            if ( is_wp_error( $result ) ) {
                return false;
            }

            return (bool) $result;
        }

        if ( class_exists( 'WP_SMS' ) ) {
            $sms = new WP_SMS();
            if ( property_exists( $sms, 'to' ) ) {
                $sms->to = $numbers;
            }
            if ( property_exists( $sms, 'msg' ) ) {
                $sms->msg = $message;
            }

            if ( method_exists( $sms, 'send_sms' ) ) {
                return (bool) $sms->send_sms();
            }

            if ( method_exists( $sms, 'send' ) ) {
                return (bool) $sms->send();
            }

            if ( method_exists( $sms, 'SendSMS' ) ) {
                return (bool) $sms->SendSMS();
            }
        }

        return false;
    }

    public function parse_tags( $message, $context ) {
        $message = (string) ( $message ?? '' );
        $context = is_array( $context ) ? $context : array();

        $normalize_value = function ( $value ) use ( &$normalize_value ) {
            if ( is_null( $value ) ) {
                return '';
            }

            if ( is_scalar( $value ) ) {
                return (string) $value;
            }

            if ( is_array( $value ) ) {
                $flattened = array();
                foreach ( $value as $item ) {
                    $prepared = $normalize_value( $item );
                    if ( '' !== $prepared ) {
                        $flattened[] = $prepared;
                    }
                }

                return implode( '، ', $flattened );
            }

            if ( is_object( $value ) && method_exists( $value, '__toString' ) ) {
                return (string) $value;
            }

            return '';
        };

        foreach ( $context as $tag => $value ) {
            if ( 'gf_entry' === $tag ) {
                continue;
            }

            $message = str_replace( '{' . $tag . '}', $normalize_value( $value ), $message );
        }

        if ( isset( $context['gf_entry'] ) && is_array( $context['gf_entry'] ) ) {
            foreach ( $context['gf_entry'] as $field_id => $field_value ) {
                $message = str_replace( '{gf_' . $field_id . '}', $normalize_value( $field_value ), $message );
            }
        }

        return $message;
    }
}
