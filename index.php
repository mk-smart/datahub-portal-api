<?php
error_reporting ( E_ALL );
ini_set ( 'display_errors', 1 );
define('WP_BASE_DIR', __DIR__ . '/..');
// Init wordpress environment
require_once (WP_BASE_DIR . '/wp-load.php');
// Register vendor sources
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/DataHub/Api.php';