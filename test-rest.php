<?php
require_once('wp-load.php');

$request = new WP_REST_Request('GET', '/wp/v2/posts');
$response = rest_do_request($request);
$server = rest_get_server();
$data = $server->response_to_data($response, false);
print_r($data);