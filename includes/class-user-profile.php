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
        $inactive_output = '';
        if ( is_numeric( $inactive_until ) && intval( $inactive_until ) > 0 ) {
            $inactive_output = gmdate( 'Y-m-d\TH:i', intval( $inactive_until ) );
        }
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
                        <input type="datetime-local" id="user_inactive_until" name="user_inactive_until" value="<?php echo esc_attr( $inactive_output ); ?>" placeholder="yyyy-mm-ddThh:mm" />
                        <p class="description">بازه غیرفعالی را انتخاب کنید یا یک تاریخ/ساعت دستی وارد نمایید.</p>
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
        update_user_meta( $user_id, 'user_province_id', sanitize_text_field( wp_unslash( $_POST['user_province_id'] ?? '' ) ) );
        $city_ids = array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['user_city_ids'] ?? array() ) ) );
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
                    $time   = strtotime( $custom );
                    if ( $time ) {
                        $inactive_ts = $time;
                    }
                    break;
            }
        }

        update_user_meta( $user_id, 'user_is_active', $is_active === '0' ? '0' : '1' );
        update_user_meta( $user_id, 'user_inactive_until', $inactive_ts );
    }
}
