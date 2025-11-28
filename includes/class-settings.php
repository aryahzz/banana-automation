<?php
class Benana_Automation_Settings {
    const OPTION_KEY = 'benana_automation_settings';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_post_benana_delete_gf_entry', array( $this, 'delete_gf_entry' ) );
    }

    public static function get_settings() {
        $defaults = array(
            'gravity_forms'    => array(),
            'sms_templates'    => array(
                'assign'             => 'کارگزار گرامی، پروژه جدیدی برای شما ثبت شد. شناسه: {project_id}',
                'accepted_assignee'  => 'پروژه {project_title} را به‌عنوان مهندس پذیرفتید.',
                'accepted_client'    => 'مشتری گرامی، پروژه شما با شناسه {project_id} پذیرفته شد.',
                'file_uploaded'      => 'فایل پروژه {project_title} ثبت شد: {file_url}',
                'completed'          => 'پروژه {project_title} به پایان رسید. سپاسگزاریم.',
            ),
            'update_source'    => self::get_update_source_url(),
        );
        $settings = get_option( self::OPTION_KEY, array() );
        $settings = wp_parse_args( $settings, $defaults );

        if ( empty( $settings['update_source'] ) ) {
            $settings['update_source'] = self::get_update_source_url();
        }

        return $settings;
    }

    public function register_menu() {
        add_menu_page( 'اتوماسیون پروژه‌ها', 'اتوماسیون پروژه‌ها', 'manage_options', 'benana-automation-projects', array( $this, 'settings_page' ), 'dashicons-admin-generic', 26 );
        add_submenu_page( 'benana-automation-projects', 'ورودی‌ها', 'ورودی‌ها', 'manage_options', 'benana-automation-entries', array( $this, 'entries_page' ) );
        add_submenu_page( 'benana-automation-projects', 'گزارشات', 'گزارشات', 'manage_options', 'benana-automation-reports', array( $this, 'reports_page' ) );
        add_submenu_page( 'benana-automation-projects', 'راهنمای برچسب‌ها', 'برچسب‌های راهنما', 'manage_options', 'benana-automation-merge-tags', array( $this, 'merge_tags_page' ) );
    }

    public function register_settings() {
        register_setting( 'benana_automation_settings', self::OPTION_KEY, array( $this, 'sanitize_settings' ) );
        add_action( 'admin_post_benana_manual_update_check', array( $this, 'manual_update_check' ) );
    }

    public function delete_gf_entry() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'دسترسی غیرمجاز' );
        }

        check_admin_referer( 'benana_delete_entry', 'benana_delete_entry_nonce' );

        $entry_id = absint( $_POST['gf_entry_id'] ?? 0 );
        $redirect = admin_url( 'admin.php?page=benana-automation-entries' );

        if ( ! $entry_id || ! class_exists( 'GFAPI' ) ) {
            wp_safe_redirect( add_query_arg( 'benana_entry_delete', rawurlencode( 'شناسه ورودی نامعتبر است.' ), $redirect ) );
            exit;
        }

        $result = GFAPI::delete_entry( $entry_id );
        if ( is_wp_error( $result ) ) {
            $redirect = add_query_arg( 'benana_entry_delete', rawurlencode( $result->get_error_message() ), $redirect );
        } else {
            $redirect = add_query_arg( 'benana_entry_delete', rawurlencode( 'ورودی با موفقیت حذف شد.' ), $redirect );
        }

        wp_safe_redirect( $redirect );
        exit;
    }

    public function settings_page() {
        $settings = self::get_settings();
        ?>
        <div class="wrap benana-admin">
            <h1>تنظیمات اتوماسیون پروژه‌ها</h1>
            <?php if ( isset( $_GET['benana_update_check'] ) ) : ?>
                <?php $notice = rawurldecode( wp_unslash( $_GET['benana_update_check'] ) ); ?>
                <div class="notice notice-info"><p><?php echo esc_html( $notice ); ?></p></div>
            <?php endif; ?>
            <form method="post" action="options.php" id="benana-settings-form">
                <?php settings_fields( 'benana_automation_settings' ); ?>
        <p>
            <button type="submit" class="button button-primary">ذخیره تنظیمات</button>
        </p>
                <h2>تنظیمات Gravity Forms</h2>
                <p>برای هر فرم، شناسه عددی فیلدها یا مرج‌تگ کامل آن‌ها را وارد کنید.</p>
                <p class="description">شناسه فیلدهایی که می‌خواهید پیش از پذیرش مخفی شوند را با ویرگول وارد کنید؛ پس از پذیرش همه فیلدها نمایش داده می‌شوند. شناسه فیلد آپلود فایل را نیز مشخص نمایید.</p>
                <table class="form-table" id="benana-gf-table">
                    <thead>
                        <tr>
                            <th>Form ID</th>
                            <th>فیلد شهر</th>
            <th>فیلد استان</th>
            <th>فیلد موبایل</th>
                            <th>فیلد آپلود</th>
                            <th>فیلدهای پیش از پذیرش</th>
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
                        $this->render_gf_row( 'row_template', '', array(), true );
                        ?>
                    </tbody>
                </table>
                <button type="button" class="button" id="benana-add-gf">افزودن فرم</button>

                <h2>تنظیمات پیامک</h2>
                <?php
                $templates = array(
                    'assign'            => 'پیامک کارگزار هنگام ثبت',
                    'accepted_assignee' => 'پیامک کارگزار پس از پذیرش',
                    'accepted_client'   => 'پیامک مشتری پس از پذیرش',
                    'file_uploaded'     => 'پیامک مشتری پس از بارگذاری فایل',
                    'completed'         => 'پیامک‌های پایان پروژه',
                );
                foreach ( $templates as $key => $label ) {
                    echo '<p><label for="sms_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
                    echo '<textarea id="sms_' . esc_attr( $key ) . '" name="' . self::OPTION_KEY . '[sms_templates][' . esc_attr( $key ) . ']" class="large-text" rows="3">' . esc_textarea( $settings['sms_templates'][ $key ] ) . '</textarea></p>';
                }
                ?>

            </form>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="benana-inline-form benana-update-check">
                <?php wp_nonce_field( 'benana_manual_update', 'benana_manual_update_nonce' ); ?>
                <input type="hidden" name="action" value="benana_manual_update_check" />
                <button type="submit" class="button">بررسی دستی بروزرسانی</button>
            </form>
        </div>
        <?php
    }

    public function entries_page() {
        $status_filter = sanitize_text_field( wp_unslash( $_GET['project_status'] ?? '' ) );
        $paged         = max( 1, absint( $_GET['paged'] ?? 1 ) );

        $query_args = array(
            'post_type'      => 'project',
            'posts_per_page' => 20,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if ( $status_filter ) {
            $query_args['meta_query'] = array(
                array(
                    'key'   => 'project_status',
                    'value' => $status_filter,
                ),
            );
        }

        $projects   = new WP_Query( $query_args );
        $provinces  = Benana_Automation_Address::get_provinces();
        $cities     = Benana_Automation_Address::get_cities();
        $status_map = $this->get_status_labels();

        ?>
        <div class="wrap benana-admin">
            <h1>ورودی‌ها</h1>
            <?php if ( isset( $_GET['benana_entry_delete'] ) ) : ?>
                <div class="notice notice-info"><p><?php echo esc_html( rawurldecode( wp_unslash( $_GET['benana_entry_delete'] ) ) ); ?></p></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="benana-inline-form">
                <?php wp_nonce_field( 'benana_delete_entry', 'benana_delete_entry_nonce' ); ?>
                <input type="hidden" name="action" value="benana_delete_gf_entry" />
                <label for="gf_entry_id">شناسه ورودی Gravity Forms:</label>
                <input type="number" name="gf_entry_id" id="gf_entry_id" min="1" required />
                <button type="submit" class="button button-secondary">حذف ورودی</button>
            </form>
            <form method="get" class="benana-filters">
                <input type="hidden" name="page" value="benana-automation-entries" />
                <label for="project_status">فیلتر وضعیت:</label>
                <select name="project_status" id="project_status">
                    <option value="">همه وضعیت‌ها</option>
                    <?php foreach ( $status_map as $status_key => $status_label ) : ?>
                        <option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $status_filter, $status_key ); ?>><?php echo esc_html( $status_label ); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button">اعمال فیلتر</button>
            </form>

            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>عنوان</th>
                        <th>ورودی GF</th>
                        <th>وضعیت</th>
                        <th>استان/شهر</th>
                        <th>کارگزاران</th>
                        <th>پذیرفته‌شده توسط</th>
                        <th>فایل</th>
                        <th>تاریخ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ( $projects->have_posts() ) :
                        while ( $projects->have_posts() ) :
                            $projects->the_post();
                            $project_id      = get_the_ID();
                            $province_id     = get_post_meta( $project_id, 'project_province_id', true );
                            $city_id         = get_post_meta( $project_id, 'project_city_id', true );
                            $province_label  = isset( $provinces[ $province_id ] ) ? $provinces[ $province_id ] : '—';
                            $city_label      = Benana_Automation_Address::get_city_name( $province_id, $city_id );
                            if ( '' === $city_label && ! empty( $city_id ) ) {
                                $city_label = $city_id;
                            }
                            $status          = get_post_meta( $project_id, 'project_status', true );
                            $status_label    = $status_map[ $status ] ?? 'نامشخص';
                            $assigned_raw    = get_post_meta( $project_id, 'assigned_users', true );
                            $assigned_users  = $assigned_raw ? json_decode( $assigned_raw, true ) : array();
                            $accepted_by     = get_post_meta( $project_id, 'accepted_by', true );
                            $accepted_user   = $accepted_by ? get_user_by( 'id', $accepted_by ) : false;
                            $file_url        = get_post_meta( $project_id, 'file_url', true );
                            $gf_entry_id     = get_post_meta( $project_id, 'gf_entry_id', true );
                            $gf_form_id      = get_post_meta( $project_id, 'gf_form_id', true );
                            $gf_link         = ( $gf_entry_id && $gf_form_id ) ? add_query_arg(
                                array(
                                    'page' => 'gf_entries',
                                    'view' => 'entry',
                                    'id'   => $gf_form_id,
                                    'lid'  => $gf_entry_id,
                                ),
                                admin_url( 'admin.php' )
                            ) : '';
                            ?>
                            <tr>
                                <td>#<?php echo esc_html( $project_id ); ?></td>
                                <td><a href="<?php echo esc_url( get_edit_post_link( $project_id ) ); ?>"><?php the_title(); ?></a></td>
                                <td><?php echo $gf_link ? '<a href="' . esc_url( $gf_link ) . '">مشاهده ورودی</a>' : '—'; ?></td>
                                <td><span class="status-tag status-<?php echo esc_attr( $status ?: 'none' ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
                                <td><?php echo esc_html( $province_label . ' / ' . $city_label ); ?></td>
                                <td><?php echo esc_html( is_array( $assigned_users ) ? count( $assigned_users ) : 0 ); ?></td>
                                <td><?php echo $accepted_user ? esc_html( $accepted_user->display_name ) : '—'; ?></td>
                                <td><?php echo $file_url ? '<a href="' . esc_url( $file_url ) . '" target="_blank">مشاهده</a>' : '—'; ?></td>
                                <td><?php echo esc_html( get_the_date( 'Y/m/d H:i' ) ); ?></td>
                            </tr>
                            <?php
                        endwhile;
                        wp_reset_postdata();
                    else :
                        ?>
                        <tr><td colspan="8">موردی یافت نشد.</td></tr>
                        <?php
                    endif;
                    ?>
                </tbody>
            </table>

            <?php
            $pagination = paginate_links(
                array(
                    'total'   => $projects->max_num_pages,
                    'current' => $paged,
                    'type'    => 'array',
                    'add_args' => array(
                        'page'           => 'benana-automation-entries',
                        'project_status' => $status_filter,
                    ),
                )
            );

            if ( $pagination ) {
                echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( implode( ' ', $pagination ) ) . '</div></div>';
            }
            ?>
        </div>
        <?php
    }

    public function reports_page() {
        $status_map  = $this->get_status_labels();
        $counts      = array();
        $total_query = new WP_Query( array( 'post_type' => 'project', 'posts_per_page' => -1, 'fields' => 'ids' ) );
        $total       = $total_query->found_posts;

        foreach ( $status_map as $status_key => $status_label ) {
            $query = new WP_Query(
                array(
                    'post_type'      => 'project',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                    'meta_query'     => array(
                        array(
                            'key'   => 'project_status',
                            'value' => $status_key,
                        ),
                    ),
                )
            );
            $counts[ $status_key ] = $query->found_posts;
        }

        $recent = new WP_Query(
            array(
                'post_type'      => 'project',
                'posts_per_page' => 10,
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );

        ?>
        <div class="wrap benana-admin">
            <h1>گزارشات پروژه</h1>
            <div class="benana-report-grid">
                <div class="report-card">
                    <h3>مجموع پروژه‌ها</h3>
                    <strong><?php echo esc_html( $total ); ?></strong>
                </div>
                <?php foreach ( $status_map as $status_key => $status_label ) : ?>
                    <div class="report-card">
                        <h3><?php echo esc_html( $status_label ); ?></h3>
                        <strong><?php echo esc_html( $counts[ $status_key ] ?? 0 ); ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>

            <h2>آخرین ورودی‌ها</h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>عنوان</th>
                        <th>وضعیت</th>
                        <th>استان/شهر</th>
                        <th>آخرین بروزرسانی</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $provinces = Benana_Automation_Address::get_provinces();
                    if ( $recent->have_posts() ) :
                        while ( $recent->have_posts() ) :
                            $recent->the_post();
                            $project_id   = get_the_ID();
                            $province_id  = get_post_meta( $project_id, 'project_province_id', true );
                            $city_id      = get_post_meta( $project_id, 'project_city_id', true );
                            $province     = isset( $provinces[ $province_id ] ) ? $provinces[ $province_id ] : '—';
                            $city         = Benana_Automation_Address::get_city_name( $province_id, $city_id );
                            $status       = get_post_meta( $project_id, 'project_status', true );
                            $status_label = $status_map[ $status ] ?? 'نامشخص';
                            ?>
                            <tr>
                                <td>#<?php echo esc_html( $project_id ); ?></td>
                                <td><a href="<?php echo esc_url( get_edit_post_link( $project_id ) ); ?>"><?php the_title(); ?></a></td>
                                <td><?php echo esc_html( $status_label ); ?></td>
                                <td><?php echo esc_html( $province . ' / ' . $city ); ?></td>
                                <td><?php echo esc_html( get_the_modified_date( 'Y/m/d H:i' ) ); ?></td>
                            </tr>
                            <?php
                        endwhile;
                        wp_reset_postdata();
                    else :
                        ?>
                        <tr><td colspan="5">موردی یافت نشد.</td></tr>
                        <?php
                    endif;
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function get_status_labels() {
        return array(
            'new'           => 'جدید',
            'accepted'      => 'پذیرفته‌شده',
            'file_uploaded' => 'فایل بارگذاری‌شده',
            'completed'     => 'تکمیل‌شده',
        );
    }

    private function render_gf_row( $row_key, $form_id, $form_settings, $is_template = false ) {
        $defaults = array(
            'city_field'     => '',
            'province_field' => '',
            'mobile_field'   => '',
            'file_field'     => '',
            'upload_field'   => '',
            'before_accept'  => '',
        );
        // همسان‌سازی کلید قدیمی/جدید برای فیلد آپلود
        if ( empty( $form_settings['file_field'] ) && ! empty( $form_settings['upload_field'] ) ) {
            $form_settings['file_field'] = $form_settings['upload_field'];
        }
        $form_settings = wp_parse_args( $form_settings, $defaults );
        $row_classes   = $is_template ? 'benana-gf-row benana-gf-template' : 'benana-gf-row';
        $disabled_attr = $is_template ? 'disabled="disabled"' : '';
        $style         = $is_template ? 'style="display:none;"' : '';
        ?>
        <tr class="<?php echo esc_attr( $row_classes ); ?>" data-row-key="<?php echo esc_attr( $row_key ); ?>" <?php echo $style; ?>>
            <td><input <?php echo $disabled_attr; ?> type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $row_key ); ?>][form_id]" value="<?php echo esc_attr( $form_id ); ?>" /></td>
            <td><input <?php echo $disabled_attr; ?> type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $row_key ); ?>][city_field]" value="<?php echo esc_attr( $form_settings['city_field'] ); ?>" /></td>
            <td><input <?php echo $disabled_attr; ?> type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $row_key ); ?>][province_field]" value="<?php echo esc_attr( $form_settings['province_field'] ); ?>" placeholder="اختیاری: مانند 28.4" /></td>
            <td><input <?php echo $disabled_attr; ?> type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $row_key ); ?>][mobile_field]" value="<?php echo esc_attr( $form_settings['mobile_field'] ); ?>" /></td>
            <td><input <?php echo $disabled_attr; ?> type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $row_key ); ?>][file_field]" value="<?php echo esc_attr( $form_settings['file_field'] ); ?>" /></td>
            <td><input <?php echo $disabled_attr; ?> type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gravity_forms][<?php echo esc_attr( $row_key ); ?>][before_accept]" value="<?php echo esc_attr( $form_settings['before_accept'] ); ?>" placeholder="مثال: 3,4 یا {input_5}" /></td>
            <td><button type="button" class="button benana-remove-gf" <?php echo $is_template ? 'disabled="disabled"' : ''; ?>>حذف</button></td>
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

                $file_field = isset( $row['file_field'] ) ? sanitize_text_field( $row['file_field'] ) : '';
                $upload_fld = isset( $row['upload_field'] ) ? sanitize_text_field( $row['upload_field'] ) : $file_field;

                $clean['gravity_forms'][ $form_id ] = array(
                    'city_field'     => isset( $row['city_field'] ) ? sanitize_text_field( $row['city_field'] ) : '',
                    'province_field' => isset( $row['province_field'] ) ? sanitize_text_field( $row['province_field'] ) : '',
                    'mobile_field'   => isset( $row['mobile_field'] ) ? sanitize_text_field( $row['mobile_field'] ) : '',
                    'file_field'     => $file_field,
                    'upload_field'   => $upload_fld,
                    'before_accept'  => isset( $row['before_accept'] ) ? $this->sanitize_comma_separated( $row['before_accept'] ) : '',
                );
            }
        }

        $clean['update_source']    = isset( $input['update_source'] ) ? esc_url_raw( $input['update_source'] ) : self::get_update_source_url();
        $clean['debug_assignment'] = 0;

        return $clean;
    }

    public static function get_update_source_url() {
        return 'https://raw.githubusercontent.com/aryahzz/banana-automation/refs/heads/main/update.json';
    }

    public function manual_update_check() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'دسترسی غیرمجاز', 'benana-automation-projects' ) );
        }

        check_admin_referer( 'benana_manual_update', 'benana_manual_update_nonce' );

        $updater = new Benana_Automation_Updater();
        $remote  = $updater->get_remote_data();
        $message = __( 'پاسخی دریافت نشد.', 'benana-automation-projects' );

        if ( $remote && isset( $remote['version'] ) ) {
            if ( version_compare( BENANA_AUTOMATION_VERSION, $remote['version'], '>=' ) ) {
                $message = sprintf( __( 'نسخه شما (%1$s) به‌روز است.', 'benana-automation-projects' ), BENANA_AUTOMATION_VERSION );
            } else {
                $message = sprintf( __( 'نسخه جدید %1$s در دسترس است. لینک دانلود: %2$s', 'benana-automation-projects' ), $remote['version'], isset( $remote['download_url'] ) ? esc_url_raw( $remote['download_url'] ) : '' );
            }
        }

        $redirect = add_query_arg(
            array(
                'page'                => 'benana-automation-projects',
                'benana_update_check' => rawurlencode( $message ),
                'settings-updated'    => 'true',
            ),
            admin_url( 'admin.php' )
        );

        wp_safe_redirect( $redirect );
        exit;
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
