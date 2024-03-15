<?php

use Respect\Validation\Exceptions\DateException;

header( 'Access-Control-Allow-Origin: *' );
header( 'Access-Control-Allow-Headers: X-Requested-With, X-Request-Uri, Content-Type, Accept, Origin, Authorization, pl, mm, av, sv, uuid' );
header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS' );
define( 'COST_START', microtime( true ) );
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url( $_SERVER['REQUEST_URI'] );
    $file = __DIR__ . $url['path'];
    if (is_file( $file )) {
        return false;
    }
}

require __DIR__ . '/../../repo/vendor/autoload.php';

// session_start();

// Instantiate the app
$settings = require __DIR__ . '/../../config/settings.php';

if (RUNMODE == 'dev') {
    error_reporting( E_ALL );
    ini_set( 'display_errors', 'On' );
}

$app = new \Slim\App( $settings );

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';
require __DIR__ . '/../src/router.php';
require __DIR__ . '/../src/common.php';

Utils\App::setApp( $app );

// Run app
try {
    $app->run();
} catch (Throwable $e) {
    throw new DateException( $e->getMessage() );
}