<?php
class Benana_Automation_Date_Helper {
    private static $persian_digits = array(
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    );

    public static function normalize_digits( $value ) {
        return strtr( $value, self::$persian_digits );
    }

    public static function format_for_picker( $timestamp ) {
        if ( ! is_numeric( $timestamp ) || intval( $timestamp ) <= 0 ) {
            return '';
        }

        return date_i18n( 'Y/m/d H:i', intval( $timestamp ) );
    }

    public static function parse_inactive_input( $input ) {
        if ( '' === $input ) {
            return '';
        }

        $clean = self::normalize_digits( trim( $input ) );

        if ( '' === $clean ) {
            return '';
        }

        $maybe = strtotime( $clean );
        if ( $maybe ) {
            return $maybe;
        }

        $parts = preg_split( '/[T\s]+/', $clean );
        $date  = $parts[0] ?? '';
        $time  = $parts[1] ?? '';

        if ( ! preg_match( '/^(\d{3,4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $date, $matches ) ) {
            return '';
        }

        $hour = 0;
        $min  = 0;
        $sec  = 0;

        if ( preg_match( '/(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?/', $time, $t ) ) {
            $hour = intval( $t[1] );
            $min  = intval( $t[2] );
            $sec  = isset( $t[3] ) ? intval( $t[3] ) : 0;
        }

        $year  = intval( $matches[1] );
        $month = intval( $matches[2] );
        $day   = intval( $matches[3] );

        if ( $year > 1600 ) {
            $timestamp = strtotime( sprintf( '%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $min, $sec ) );
            return $timestamp ? $timestamp : '';
        }

        list( $gy, $gm, $gd ) = self::jalali_to_gregorian( $year, $month, $day );
        $timestamp = strtotime( sprintf( '%04d-%02d-%02d %02d:%02d:%02d', $gy, $gm, $gd, $hour, $min, $sec ) );

        return $timestamp ? $timestamp : '';
    }

    private static function jalali_to_gregorian( $jy, $jm, $jd ) {
        $jy       = intval( $jy ) - 979;
        $jm       = intval( $jm ) - 1;
        $jd       = intval( $jd ) - 1;
        $j_day_no = 365 * $jy + intdiv( $jy, 33 ) * 8 + intdiv( ( $jy % 33 ) + 3, 4 );
        $j_months = array( 31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29 );

        for ( $i = 0; $i < $jm; $i++ ) {
            $j_day_no += $j_months[ $i ];
        }

        $j_day_no += $jd;
        $g_day_no  = $j_day_no + 79;

        $g_year = 1600 + 400 * intdiv( $g_day_no, 146097 );
        $g_day_no = $g_day_no % 146097;

        $leap = true;
        if ( $g_day_no >= 36525 ) {
            $g_day_no--;
            $g_year += 100 * intdiv( $g_day_no, 36524 );
            $g_day_no = $g_day_no % 36524;

            if ( $g_day_no >= 365 ) {
                $g_day_no++;
            } else {
                $leap = false;
            }
        }

        $g_year += 4 * intdiv( $g_day_no, 1461 );
        $g_day_no = $g_day_no % 1461;

        if ( $g_day_no >= 366 ) {
            $leap     = false;
            $g_day_no--;
            $g_year  += intdiv( $g_day_no, 365 );
            $g_day_no = $g_day_no % 365;
        }

        $g_months = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );

        for ( $i = 0; $g_day_no >= $g_months[ $i ] + ( ( 1 === $i ) && $leap ? 1 : 0 ); $i++ ) {
            $g_day_no -= $g_months[ $i ] + ( ( 1 === $i ) && $leap ? 1 : 0 );
        }

        $g_month = $i + 1;
        $g_day   = $g_day_no + 1;

        return array( $g_year, $g_month, $g_day );
    }
}
