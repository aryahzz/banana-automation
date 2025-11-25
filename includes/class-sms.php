<?php
class Benana_Automation_SMS {
    public function send_sms( $mobile, $message ) {
        if ( empty( $mobile ) || empty( $message ) ) {
            return false;
        }

        if ( class_exists( 'WP_SMS' ) ) {
            $sms = new WP_SMS();
            $sms->to = array( $mobile );
            $sms->msg = $message;
            $sms->SendSMS();
            return true;
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
