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
        foreach ( $context as $tag => $value ) {
            $message = str_replace( '{' . $tag . '}', $value, $message );
        }

        if ( isset( $context['gf_entry'] ) && is_array( $context['gf_entry'] ) ) {
            foreach ( $context['gf_entry'] as $field_id => $field_value ) {
                $message = str_replace( '{gf_' . $field_id . '}', $field_value, $message );
            }
        }

        return $message;
    }
}
