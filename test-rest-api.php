<?php
// Ensure this file is being run within the WordPress context
if (!defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once('../../../../wp-load.php');
}

function test_epg_endpoint() {
    $request = new WP_REST_Request('GET', '/modern-epg/v1/epg');
    $response = rest_do_request($request);
    
    if (is_wp_error($response)) {
        return array(
            'error' => true,
            'message' => $response->get_error_message()
        );
    }
    
    $data = $response->get_data();
    $status = $response->get_status();
    
    return array(
        'status' => $status,
        'data' => $data
    );
}

// Only run the test if accessed directly
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    $result = test_epg_endpoint();
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}