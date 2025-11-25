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
        register_setting( 'benana_automation_settings', self::OPTION_KEY );
    }

    public function settings_page() {
        $settings = self::get_settings();
        ?>
        <div class="wrap benana-admin">
            <h1>تنظیمات اتوماسیون پروژه‌ها</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'benana_automation_settings' ); ?>
                <h2>تنظیمات Gravity Forms</h2>
                <p>برای هر فرم، شناسه فیلدهای شهر، موبایل، فایل و نمایش را وارد کنید.</p>
                <table class="form-table" id="benana-gf-table">
                    <thead>
                        <tr>
                            <th>Form ID</th>
                            <th>فیلد شهر</th>
                            <th>فیلد موبایل</th>
                            <th>فیلد آپلود</th>
                            <th>فیلدهای قبل از قبول</th>
                            <th>فیلدهای بعد از قبول</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ( $settings['gravity_forms'] as $form_id => $form_settings ) {
                            $this->render_gf_row( $form_id, $form_settings );
                        }
                        $this->render_gf_row( '', array() );
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

    private function render_gf_row( $form_id, $form_settings ) {
        $defaults = array(
            'city_field'    => '',
            'mobile_field'  => '',
            'file_field'    => '',
            'before_accept' => '',
            'after_accept'  => '',
        );
        $form_settings = wp_parse_args( $form_settings, $defaults );
        ?>
        <tr>
            <td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $form_id ); ?>][form_id]" value="<?php echo esc_attr( $form_id ); ?>" /></td>
            <td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $form_id ); ?>][city_field]" value="<?php echo esc_attr( $form_settings['city_field'] ); ?>" /></td>
            <td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $form_id ); ?>][mobile_field]" value="<?php echo esc_attr( $form_settings['mobile_field'] ); ?>" /></td>
            <td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $form_id ); ?>][file_field]" value="<?php echo esc_attr( $form_settings['file_field'] ); ?>" /></td>
            <td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $form_id ); ?>][before_accept]" value="<?php echo esc_attr( $form_settings['before_accept'] ); ?>" /></td>
            <td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $form_id ); ?>][after_accept]" value="<?php echo esc_attr( $form_settings['after_accept'] ); ?>" /></td>
        </tr>
        <?php
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
