<div class="benana-project-detail">
    <h3><?php echo esc_html( $view['project']->post_title ); ?></h3>
    <div class="benana-meta">
        <span class="benana-status-tag"><span class="dot"></span><?php echo esc_html( $view['status_label'] ); ?></span>
        <span>موقعیت: <?php echo esc_html( Benana_Automation_Address::get_city_name( $view['province'], $view['city'] ) ); ?></span>
        <?php if ( ! empty( $view['entry_date'] ) ) : ?>
            <span>تاریخ ورودی: <?php echo esc_html( $view['entry_date'] ); ?></span>
        <?php endif; ?>
    </div>

    <?php if ( ! empty( $view['action_msg'] ) ) : ?>
        <div class="benana-alert <?php echo ( 'uploaded' === $view['action_msg'] || 'accepted' === $view['action_msg'] ) ? 'success' : 'warning'; ?>">
            <?php
            if ( 'accepted' === $view['action_msg'] ) {
                echo 'پروژه توسط شما پذیرفته شد.';
            } elseif ( 'rejected' === $view['action_msg'] ) {
                echo 'پروژه رد شد.';
            } elseif ( 'completed' === $view['action_msg'] ) {
                echo 'پروژه به اتمام رسید.';
            } elseif ( 'uploaded' === $view['action_msg'] ) {
                echo 'فایل با موفقیت ثبت و به ورودی پیوست شد.';
            } elseif ( 'upload_failed' === $view['action_msg'] ) {
                echo 'بارگذاری فایل انجام نشد.';
            }

            if ( ! empty( $view['action_text'] ) ) {
                echo ' ' . esc_html( $view['action_text'] );
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if ( 'new' === $view['status'] ) : ?>
        <div class="benana-actions-row">
            <form method="post" class="benana-action-card accept">
                <span class="icon">✔</span>
                <input type="hidden" name="project_id" value="<?php echo esc_attr( $view['project']->ID ); ?>" />
                <input type="hidden" name="benana_action" value="accept" />
                <input type="hidden" name="benana_action_nonce" value="<?php echo esc_attr( $view['nonce'] ); ?>" />
                <button type="submit" class="button button-primary">پذیرش</button>
            </form>
            <form method="post" class="benana-action-card reject">
                <span class="icon">✕</span>
                <input type="hidden" name="project_id" value="<?php echo esc_attr( $view['project']->ID ); ?>" />
                <input type="hidden" name="benana_action" value="reject" />
                <input type="hidden" name="benana_action_nonce" value="<?php echo esc_attr( $view['nonce'] ); ?>" />
                <button type="submit" class="button">رد</button>
            </form>
        </div>
    <?php elseif ( 'accepted' === $view['status'] ) : ?>
        <p>این پروژه توسط شما در حال انجام است.</p>
    <?php endif; ?>

    <?php if ( 'accepted' === $view['status'] ) : ?>
        <div class="benana-upload-box">
            <h4>بارگذاری فایل پروژه</h4>
            <p class="description">فایل پروژه را بارگذاری کنید تا به فیلد آپلود فرم (شناسه: <?php echo esc_html( $view['upload_field'] ?: '—' ); ?>) پیوست شود.</p>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="project_id" value="<?php echo esc_attr( $view['project']->ID ); ?>" />
                <input type="hidden" name="benana_action" value="upload_file" />
                <input type="hidden" name="benana_action_nonce" value="<?php echo esc_attr( $view['nonce'] ); ?>" />
                <input type="file" name="benana_project_files[]" multiple />
                <button type="submit" class="button">ارسال فایل</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $view['fields'] ) ) : ?>
        <div class="benana-entry-fields">
            <h4>جزئیات پروژه</h4>
            <div class="benana-field-grid">
                <?php
                foreach ( $view['fields'] as $field ) {
                    $value = $field['value'];
                    if ( is_array( $value ) ) {
                        $value = implode( '<br>', array_map( 'wp_kses_post', (array) $value ) );
                    }

                    ?>
                    <div class="benana-field">
                        <div class="benana-field__value"><?php echo wp_kses_post( $value ); ?></div>
                        <strong class="benana-field__label"><?php echo esc_html( $field['label'] ); ?></strong>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>
