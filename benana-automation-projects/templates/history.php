<div class="benana-history">
    <h3>تاریخچه پروژه‌های من</h3>
    <?php if ( empty( $projects ) ) : ?>
        <p>پروژه‌ای ثبت نشده است.</p>
    <?php else : ?>
        <ul>
            <?php foreach ( $projects as $project ) : ?>
                <li><?php echo esc_html( get_the_title( $project ) ); ?> - <?php echo esc_html( get_post_meta( $project->ID, 'project_status', true ) ); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
