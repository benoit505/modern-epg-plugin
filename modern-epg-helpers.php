<?php
// Helper functions for Modern EPG Plugin

if (!function_exists('modern_epg_log')) {
    function modern_epg_log($message, $level = 'INFO') {
        error_log("Attempting to log: $level - $message");
        if (class_exists('Modern_EPG_Logger')) {
            Modern_EPG_Logger::log($message, $level);
        } else {
            error_log("Modern EPG [$level]: $message");
        }
    }
}

// Add any other helper functions here
