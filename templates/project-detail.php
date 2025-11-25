<div class="benana-project-detail">
    <h3><?php echo esc_html( $view['project']->post_title ); ?></h3>
    <div class="benana-meta">
        <span>وضعیت: <?php echo esc_html( $view['status'] ); ?></span>
        <span>موقعیت: <?php echo esc_html( Benana_Automation_Address::get_city_name( $view['province'], $view['city'] ) ); ?></span>
    </div>

    <?php if ( ! empty( $view['action_msg'] ) ) : ?>
        <div class="benana-alert <?php echo ( 'uploaded' === $view['action_msg'] || 'accepted' === $view['action_msg'] ) ? 'success' : 'warning'; ?>">
            <?php
            if ( 'accepted' === $view['action_msg'] ) {
                echo 'پروژه با موفقیت پذیرفته شد.';
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

    <div class="benana-actions">
        <?php if ( 'accepted' !== $view['status'] ) : ?>
            <form method="post" class="benana-inline-form">
                <input type="hidden" name="project_id" value="<?php echo esc_attr( $view['project']->ID ); ?>" />
                <input type="hidden" name="benana_action" value="accept" />
                <input type="hidden" name="benana_action_nonce" value="<?php echo esc_attr( $view['nonce'] ); ?>" />
                <button type="submit" class="button button-primary">قبول</button>
            </form>
            <form method="post" class="benana-inline-form">
                <input type="hidden" name="project_id" value="<?php echo esc_attr( $view['project']->ID ); ?>" />
                <input type="hidden" name="benana_action" value="reject" />
                <input type="hidden" name="benana_action_nonce" value="<?php echo esc_attr( $view['nonce'] ); ?>" />
                <button type="submit" class="button">رد</button>
            </form>
        <?php else : ?>
            <p>پروژه توسط شما پذیرفته شده است.</p>
            <form method="post" class="benana-inline-form">
                <input type="hidden" name="project_id" value="<?php echo esc_attr( $view['project']->ID ); ?>" />
                <input type="hidden" name="benana_action" value="complete" />
                <input type="hidden" name="benana_action_nonce" value="<?php echo esc_attr( $view['nonce'] ); ?>" />
                <button type="submit" class="button button-primary">اتمام پروژه</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ( in_array( $view['status'], array( 'accepted', 'file_uploaded' ), true ) ) : ?>
        <div class="benana-upload-box">
            <h4>بارگذاری فایل پروژه</h4>
            <p class="description">فایل یا فایل‌های پروژه را بارگذاری کنید تا به فیلد آپلود فرم (شناسه: <?php echo esc_html( $view['upload_field'] ); ?>) پیوست شود و در ورود Gravity Forms ذخیره گردد.</p>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="project_id" value="<?php echo esc_attr( $view['project']->ID ); ?>" />
                <input type="hidden" name="benana_action" value="upload_file" />
                <input type="hidden" name="benana_action_nonce" value="<?php echo esc_attr( $view['nonce'] ); ?>" />
                <input type="file" name="benana_project_files[]" multiple />
                <p class="description">پس از انتخاب فایل، روی «ارسال فایل» کلیک کنید تا به ورودی شماره <?php echo esc_html( $view['entry_id'] ); ?> افزوده شود.</p>
                <button type="submit" class="button">ارسال فایل</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $view['fields'] ) ) : ?>
        <div class="benana-entry-fields">
            <h4>جزئیات فرم</h4>
            <?php
            foreach ( $view['fields'] as $field ) {
                $value = is_array( $field['value'] ) ? implode( '، ', (array) $field['value'] ) : $field['value'];
                ?>
                <div class="benana-field">
                    <strong><?php echo esc_html( $field['label'] ); ?></strong>
                    <div><?php echo esc_html( $value ); ?></div>
                </div>
                <?php
            }
            ?>
        </div>
    <?php endif; ?>
</div>
