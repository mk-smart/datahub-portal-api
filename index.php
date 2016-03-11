<?php
error_reporting ( E_ALL );
ini_set ( 'display_errors', 1 );
define('WP_BASE_DIR', __DIR__ . '/..');
// Init wordpress environment
require_once (WP_BASE_DIR . '/wp-load.php');
// Force Wordpress to Believe we are in an Admin section, as our authentication and 
// policies management differs from the WP portal ones.
// Register vendor sources
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/DataHub/Api.php';