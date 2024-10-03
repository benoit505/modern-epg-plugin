<?php

class Modern_EPG_Logger {
    private static $log_file;
    private static $log_level;
    private static $levels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3, 'CRITICAL' => 4];

    public static function init($log_file, $log_level = 'INFO') {
        self::$log_file = $log_file;
        self::$log_level = $log_level;
    }

    public static function log($message, $level = 'INFO') {
        error_log("Modern_EPG_Logger: Attempting to log - $level: $message");
        if (self::should_log($level)) {
            $timestamp = date('Y-m-d H:i:s');
            $formatted_message = "[$timestamp] Modern EPG [$level]: $message" . PHP_EOL;
            error_log($formatted_message, 3, self::$log_file);
        }
    }

    private static function should_log($level) {
        if (!isset(self::$levels[$level])) {
            $level = 'INFO'; // Default to INFO if an undefined level is used
        }
        return self::$levels[$level] >= self::$levels[self::$log_level];
    }
}
