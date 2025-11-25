<?php
class Benana_Automation_Merge_Tags {
    public function __construct() {
        add_filter( 'benana_merge_tags', array( $this, 'default_tags' ) );
    }

    public function default_tags( $tags ) {
        $defaults = array(
            'project_id',
            'project_title',
            'project_status',
            'project_city',
            'project_province',
            'project_url',
            'file_url',
            'assignee_name',
            'assignee_mobile',
            'client_name',
            'client_mobile',
        );
        return array_unique( array_merge( $tags, $defaults ) );
    }
}
