<?php
class SmartSearchLogger {
    private static $log_file;

    public static function init() {
        $upload_dir = wp_upload_dir();
        self::$log_file = $upload_dir['basedir'] . '/smart-search-debug.log';
    }

    public static function log($message, $type = 'info') {
        if (!self::$log_file) {
            self::init();
        }

        $timestamp = current_time('mysql');
        $log_entry = "[{$timestamp}] [{$type}] {$message}\n";
        
        error_log($log_entry, 3, self::$log_file);
    }

    public static function clear_log() {
        if (file_exists(self::$log_file)) {
            unlink(self::$log_file);
        }
    }

    public static function get_log_contents() {
        if (file_exists(self::$log_file)) {
            return file_get_contents(self::$log_file);
        }
        return 'Log file not found.';
    }
}

// Inicialização do logger
add_action('init', ['SmartSearchLogger', 'init']);