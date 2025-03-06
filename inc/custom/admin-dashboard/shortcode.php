<?php
function admin_dashboard_shortcode() {
    wp_enqueue_script('socasatop-admin-dashboard', plugin_dir_url(__FILE__) . 'assets/js/admin-dashboard.js', array('jquery'), '1.0.0', true);
    return '<div id="admin-dashboard-root" class="admin-dashboard"></div>';
}
add_shortcode('admin_dashboard', 'admin_dashboard_shortcode');