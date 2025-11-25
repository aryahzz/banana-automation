<div class="benana-history">
    <h3>تاریخچه پروژه‌های من</h3>
    <form class="benana-filter" method="get">
        <input type="text" name="benana_history_search" value="<?php echo isset( $_GET['benana_history_search'] ) ? esc_attr( wp_unslash( $_GET['benana_history_search'] ) ) : ''; ?>" placeholder="جستجو در عنوان یا توضیح پروژه" />
        <button type="submit" class="button">جستجو</button>
    </form>
    <?php if ( empty( $projects ) ) : ?>
        <p>موردی ثبت نشده است.</p>
    <?php else : ?>
        <?php foreach ( $projects as $project ) :
            $status = get_post_meta( $project->ID, 'project_status', true );
            $file   = get_post_meta( $project->ID, 'file_url', true );
            ?>
            <div class="benana-card">
                <strong><?php echo esc_html( get_the_title( $project ) ); ?></strong>
                <div class="meta">وضعیت: <?php echo esc_html( $status ); ?></div>
                <?php if ( $file ) : ?>
                    <div class="meta">فایل: <a href="<?php echo esc_url( $file ); ?>" target="_blank" rel="noopener">دانلود</a></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
