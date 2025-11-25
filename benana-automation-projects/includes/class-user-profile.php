<?php
class Benana_Automation_User_Profile {
    public function __construct() {
        add_action( 'show_user_profile', array( $this, 'render_fields' ) );
        add_action( 'edit_user_profile', array( $this, 'render_fields' ) );
        add_action( 'personal_options_update', array( $this, 'save_fields' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_fields' ) );
    }

    public function render_fields( $user ) {
        $province_id = get_user_meta( $user->ID, 'user_province_id', true );
        $city_ids    = (array) get_user_meta( $user->ID, 'user_city_ids', true );
        $is_active   = get_user_meta( $user->ID, 'user_is_active', true );
        $inactive_until = get_user_meta( $user->ID, 'user_inactive_until', true );
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
                    <select name="user_city_ids[]" id="user_city_ids" multiple>
                        <?php
                        if ( $province_id && isset( $cities[ $province_id ] ) ) {
                            foreach ( $cities[ $province_id ] as $cid => $cname ) {
                                echo '<option value="' . esc_attr( $cid ) . '"' . selected( in_array( $cid, $city_ids, true ), true, false ) . '>' . esc_html( $cname ) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <p class="description">فهرست شهرها بر اساس استان انتخابی بارگذاری می‌شود.</p>
                </td>
            </tr>
            <tr>
                <th><label for="user_is_active">فعال</label></th>
                <td><input type="checkbox" id="user_is_active" name="user_is_active" value="1" <?php checked( $is_active, '1' ); ?> /></td>
            </tr>
            <tr>
                <th><label for="user_inactive_until">غیرفعال تا</label></th>
                <td><input type="date" id="user_inactive_until" name="user_inactive_until" value="<?php echo esc_attr( $inactive_until ); ?>" /></td>
            </tr>
        </table>
        <?php
    }

    public function save_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }
        update_user_meta( $user_id, 'user_province_id', sanitize_text_field( wp_unslash( $_POST['user_province_id'] ?? '' ) ) );
        $city_ids = array_map( 'sanitize_text_field', wp_unslash( $_POST['user_city_ids'] ?? array() ) );
        update_user_meta( $user_id, 'user_city_ids', $city_ids );
        update_user_meta( $user_id, 'user_is_active', isset( $_POST['user_is_active'] ) ? '1' : '0' );
        update_user_meta( $user_id, 'user_inactive_until', sanitize_text_field( wp_unslash( $_POST['user_inactive_until'] ?? '' ) ) );
    }
}
