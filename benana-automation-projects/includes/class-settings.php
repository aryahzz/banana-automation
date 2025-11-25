<?php
class Benana_Automation_Settings {
    const OPTION_KEY = 'benana_automation_settings';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public static function get_settings() {
        $defaults = array(
            'gravity_forms' => array(),
            'sms_templates' => array(
                'assign'             => 'پروژه جدید برای شما ثبت شد. شناسه: {project_id}',
                'accepted_assignee'  => 'پروژه {project_title} را شما پذیرفتید.',
                'accepted_client'    => 'پروژه شما با شناسه {project_id} پذیرفته شد.',
                'file_uploaded'      => 'فایل پروژه {project_title} بارگذاری شد: {file_url}',
                'completed'          => 'پروژه {project_title} به پایان رسید. سپاسگزاریم.',
            ),
        );
        $settings = get_option( self::OPTION_KEY, array() );
        return wp_parse_args( $settings, $defaults );
    }

    public function register_menu() {
        add_menu_page( 'اتوماسیون پروژه‌ها', 'اتوماسیون پروژه‌ها', 'manage_options', 'benana-automation-projects', array( $this, 'settings_page' ), 'dashicons-admin-generic', 26 );
        add_submenu_page( 'benana-automation-projects', 'راهنمای برچسب‌ها', 'برچسب‌های راهنما', 'manage_options', 'benana-automation-merge-tags', array( $this, 'merge_tags_page' ) );
    }

    public function register_settings() {
        register_setting( 'benana_automation_settings', self::OPTION_KEY, array( $this, 'sanitize_settings' ) );
    }

    public function settings_page() {
        $settings = self::get_settings();
        ?>
        <div class="wrap benana-admin">
            <h1>تنظیمات اتوماسیون پروژه‌ها</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'benana_automation_settings' ); ?>
                <h2>تنظیمات Gravity Forms</h2>
                <p>برای هر فرم، شناسه عددی فیلدها یا مرج‌تگ کامل آن‌ها را وارد کنید.</p>
                <p class="description">فیلدهای قبل/بعد از قبول را با ویرگول جدا کنید؛ مثال: <code>3,4,5</code> یا <code>{Field:3},{input_4}</code>.</p>
                <table class="form-table" id="benana-gf-table">
                    <thead>
                        <tr>
                            <th>Form ID</th>
                            <th>فیلد شهر</th>
                            <th>فیلد موبایل</th>
                            <th>فیلد آپلود</th>
                            <th>فیلدهای قبل از قبول</th>
                            <th>فیلدهای بعد از قبول</th>
                            <th>حذف</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $row_index = 0;
                        foreach ( $settings['gravity_forms'] as $form_id => $form_settings ) {
                            $this->render_gf_row( 'row_' . $row_index, $form_id, $form_settings );
                            $row_index++;
                        }
                        $this->render_gf_row( 'row_new', '', array() );
                        ?>
                    </tbody>
                </table>
                <button type="button" class="button" id="benana-add-gf">افزودن فرم</button>

                <h2>تنظیمات پیامک</h2>
                <?php
                $templates = array(
                    'assign'            => 'پیامک اساینی هنگام ثبت',
                    'accepted_assignee' => 'پیامک اساینی بعد از قبول',
                    'accepted_client'   => 'پیامک درخواست‌کننده بعد از قبول',
                    'file_uploaded'     => 'پیامک کاربر بعد از آپلود',
                    'completed'         => 'پیامک‌های پایان پروژه',
                );
                foreach ( $templates as $key => $label ) {
                    echo '<p><label for="sms_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
                    echo '<textarea id="sms_' . esc_attr( $key ) . '" name="' . self::OPTION_KEY . '[sms_templates][' . esc_attr( $key ) . ']" class="large-text" rows="3">' . esc_textarea( $settings['sms_templates'][ $key ] ) . '</textarea></p>';
                }
                ?>
                <?php submit_button( 'ذخیره تنظیمات' ); ?>
            </form>
        </div>
        <?php
    }

    private function render_gf_row( $row_key, $form_id, $form_settings ) {
        $defaults = array(
            'city_field'    => '',
            'mobile_field'  => '',
            'file_field'    => '',
            'before_accept' => '',
            'after_accept'  => '',
        );
        $form_settings = wp_parse_args( $form_settings, $defaults );
        ?>
        <tr class="benana-gf-row" data-row-key="<?php echo esc_attr( $row_key ); ?>">
            <td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $row_key ); ?>][form_id]" value="<?php echo esc_attr( $form_id ); ?>" /></td>
            <td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $row_key ); ?>][city_field]" value="<?php echo esc_attr( $form_settings['city_field'] ); ?>" /></td>
            <td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $row_key ); ?>][mobile_field]" value="<?php echo esc_attr( $form_settings['mobile_field'] ); ?>" /></td>
            <td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $row_key ); ?>][file_field]" value="<?php echo esc_attr( $form_settings['file_field'] ); ?>" /></td>
            <td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $row_key ); ?>][before_accept]" value="<?php echo esc_attr( $form_settings['before_accept'] ); ?>" placeholder="مثال: 3,4 یا {input_5}" /></td>
            <td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $row_key ); ?>][after_accept]" value="<?php echo esc_attr( $form_settings['after_accept'] ); ?>" placeholder="مثال: 6,7 یا {Field:8}" /></td>
            <td><button type="button" class="button benana-remove-gf">حذف</button></td>
        </tr>
        <?php
    }

    public function sanitize_settings( $input ) {
        $clean = self::get_settings();

        $clean['sms_templates'] = isset( $input['sms_templates'] ) && is_array( $input['sms_templates'] ) ? array_map( 'wp_kses_post', $input['sms_templates'] ) : $clean['sms_templates'];

        $clean['gravity_forms'] = array();
        if ( isset( $input['gravity_forms'] ) && is_array( $input['gravity_forms'] ) ) {
            foreach ( $input['gravity_forms'] as $row ) {
                $form_id = isset( $row['form_id'] ) ? sanitize_text_field( $row['form_id'] ) : '';
                if ( '' === $form_id ) {
                    continue;
                }

                $before = isset( $row['before_accept'] ) ? $this->sanitize_comma_separated( $row['before_accept'] ) : '';
                $after  = isset( $row['after_accept'] ) ? $this->sanitize_comma_separated( $row['after_accept'] ) : '';

                $clean['gravity_forms'][ $form_id ] = array(
                    'city_field'    => isset( $row['city_field'] ) ? sanitize_text_field( $row['city_field'] ) : '',
                    'mobile_field'  => isset( $row['mobile_field'] ) ? sanitize_text_field( $row['mobile_field'] ) : '',
                    'file_field'    => isset( $row['file_field'] ) ? sanitize_text_field( $row['file_field'] ) : '',
                    'before_accept' => $before,
                    'after_accept'  => $after,
                );
            }
        }

        return $clean;
    }

    private function sanitize_comma_separated( $value ) {
        $parts = array_filter( array_map( 'trim', explode( ',', (string) $value ) ) );
        return implode( ',', $parts );
    }

    public function merge_tags_page() {
        ?>
        <div class="wrap benana-admin">
            <h1>برچسب‌های قابل استفاده در پیامک</h1>
            <p>این برچسب‌ها در تنظیمات پیامک استفاده می‌شوند:</p>
            <ul>
                <li>{project_id}</li>
                <li>{project_title}</li>
                <li>{project_status}</li>
                <li>{project_city}</li>
                <li>{project_province}</li>
                <li>{project_url}</li>
                <li>{file_url}</li>
                <li>{assignee_name}</li>
                <li>{assignee_mobile}</li>
                <li>{client_name}</li>
                <li>{client_mobile}</li>
                <li>{gf_FIELDID}</li>
            </ul>
        </div>
        <?php
    }
}
