<?php
class AdminDashboardLogger {
    private $log_file;
    private $metrics_log_file;

    public function __construct() {
        $this->log_file = WP_CONTENT_DIR . '/admin-dashboard-debug.log';
        $this->metrics_log_file = WP_CONTENT_DIR . '/admin-dashboard-metrics.log';
    }

    public function log($message, $type = 'info') {
        $timestamp = current_time('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [{$type}] {$message}\n";
        error_log($log_entry, 3, $this->log_file);
    }

    public function log_metrics($data, $type = 'metrics') {
        $timestamp = current_time('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [{$type}] " . print_r($data, true) . "\n";
        error_log($log_entry, 3, $this->metrics_log_file);
    }
}

$admin_logger = new AdminDashboardLogger();