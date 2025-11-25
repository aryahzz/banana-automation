<?php
class Benana_Automation_User_Profile {
    public function __construct() {
        add_action( 'show_user_profile', array( $this, 'render_fields' ) );
        add_action( 'edit_user_profile', array( $this, 'render_fields' ) );
        add_action( 'personal_options_update', array( $this, 'save_fields' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_fields' ) );
    }

    public function render_fields( $user ) {
        $province_id     = get_user_meta( $user->ID, 'user_province_id', true );
        $city_ids        = (array) get_user_meta( $user->ID, 'user_city_ids', true );
        $is_active       = get_user_meta( $user->ID, 'user_is_active', true );
        $inactive_until  = get_user_meta( $user->ID, 'user_inactive_until', true );
        $inactive_output = Benana_Automation_Date_Helper::format_for_picker( $inactive_until );
        if ( '' === $is_active ) {
            $is_active = '1';
        }
        $provinces   = Benana_Automation_Address::get_provinces();
        $cities      = Benana_Automation_Address::get_cities();
        ?>
        <h2>اطلاعات پروژه‌های بنانا</h2>
        <table class="form-table benana-user-profile">
            <tr>
                <th><label for="user_province_id">استان</label></th>
                <td>
                    <select name="user_province_id" id="user_province_id">
                        <option value="">انتخاب استان</option>
                        <?php foreach ( $provinces as $pid => $pname ) : ?>
                            <option value="<?php echo esc_attr( $pid ); ?>" <?php selected( $province_id, $pid ); ?>><?php echo esc_html( $pname ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="user_city_ids">شهرها</label></th>
                <td>
                    <div class="benana-city-select" data-field="user_city_ids" data-selected="<?php echo esc_attr( implode( ',', $city_ids ) ); ?>">
                        <div class="benana-city-grid">
                            <?php
                            if ( $province_id && isset( $cities[ $province_id ] ) ) {
                                foreach ( $cities[ $province_id ] as $cid => $cname ) {
                                    ?>
                                    <label class="benana-city-item">
                                        <input type="checkbox" name="user_city_ids[]" value="<?php echo esc_attr( $cid ); ?>" <?php checked( in_array( $cid, $city_ids, true ), true ); ?> />
                                        <span><?php echo esc_html( $cname ); ?></span>
                                    </label>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                        <p class="description">شهرهای مجاز را براساس استان انتخاب کنید. امکان انتخاب چندگانه با تیک فراهم است.</p>
                    </div>
                </td>
            </tr>
            <tr>
                <th>وضعیت فعالیت</th>
                <td class="benana-availability">
                    <label class="benana-radio">
                        <input type="radio" name="user_is_active" value="1" <?php checked( $is_active, '1' ); ?> /> فعال (پیش‌فرض)
                    </label>
                    <label class="benana-radio">
                        <input type="radio" name="user_is_active" value="0" <?php checked( $is_active, '0' ); ?> /> غیرفعال موقت
                    </label>
                    <div class="benana-inactive-options" <?php echo ( '0' === $is_active ) ? '' : 'style="display:none"'; ?>>
                        <select name="user_inactive_duration" id="user_inactive_duration">
                            <option value="">انتخاب بازه</option>
                            <option value="8h">۸ ساعته</option>
                            <option value="12h">۱۲ ساعته</option>
                            <option value="2d">دو روزه</option>
                            <option value="1w">یک هفته‌ای</option>
                            <option value="manual">تا اطلاع ثانوی</option>
                            <option value="custom">تاریخ و ساعت دلخواه</option>
                        </select>
                        <input type="text" id="user_inactive_until" class="benana-jdp-input" data-jdp data-jdp-only-date="false" name="user_inactive_until" value="<?php echo esc_attr( $inactive_output ); ?>" placeholder="مثال: 1402/07/15 14:30" autocomplete="off" />
                        <p class="description">با تقویم شمسی تاریخ و ساعت دلخواه را انتخاب کنید؛ مقدار ذخیره‌شده به‌صورت خودکار به تقویم میلادی تبدیل می‌شود.</p>
                    </div>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }
        $province_id = sanitize_text_field( wp_unslash( $_POST['user_province_id'] ?? '' ) );
        $city_inputs = array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['user_city_ids'] ?? array() ) ) );
        $city_ids    = array();

        foreach ( $city_inputs as $city_input ) {
            $normalized = Benana_Automation_Address::normalize_location( $province_id, $city_input );

            if ( empty( $province_id ) && ! empty( $normalized['province_id'] ) ) {
                $province_id = $normalized['province_id'];
            }

            if ( ! empty( $normalized['city_id'] ) ) {
                $city_ids[] = (string) $normalized['city_id'];
            }
        }

        $city_ids = array_values( array_unique( $city_ids ) );

        update_user_meta( $user_id, 'user_province_id', $province_id );
        update_user_meta( $user_id, 'user_city_ids', $city_ids );

        $is_active   = isset( $_POST['user_is_active'] ) ? sanitize_text_field( wp_unslash( $_POST['user_is_active'] ) ) : '1';
        $duration    = sanitize_text_field( wp_unslash( $_POST['user_inactive_duration'] ?? '' ) );
        $inactive_ts = '';

        if ( '0' === $is_active ) {
            switch ( $duration ) {
                case '8h':
                    $inactive_ts = time() + HOUR_IN_SECONDS * 8;
                    break;
                case '12h':
                    $inactive_ts = time() + HOUR_IN_SECONDS * 12;
                    break;
                case '2d':
                    $inactive_ts = time() + DAY_IN_SECONDS * 2;
                    break;
                case '1w':
                    $inactive_ts = time() + WEEK_IN_SECONDS;
                    break;
                case 'manual':
                    $inactive_ts = -1;
                    break;
                case 'custom':
                    $custom = sanitize_text_field( wp_unslash( $_POST['user_inactive_until'] ?? '' ) );
                    $parsed = Benana_Automation_Date_Helper::parse_inactive_input( $custom );
                    if ( '' !== $parsed ) {
                        $inactive_ts = $parsed;
                    }
                    break;
            }
        }

        update_user_meta( $user_id, 'user_is_active', $is_active === '0' ? '0' : '1' );
        update_user_meta( $user_id, 'user_inactive_until', $inactive_ts );
    }
}
