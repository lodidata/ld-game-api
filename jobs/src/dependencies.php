<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Utils\Admin\Controller;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

$container = $app->getContainer();

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get( 'settings' )['logger'];
    $logger   = new Monolog\Logger( $settings['name'] );
    $logger->pushProcessor( new Monolog\Processor\UidProcessor() );
    if (isset( $settings['type'] ) && $settings['type'] == 'file') {
        $logger->pushHandler( new Monolog\Handler\RotatingFileHandler( $settings['path'], 0, $settings['level'] ) ); // 每天生成一个日志
    }
    return $logger;
};

$container['db'] = function ($c) {
    $capsule   = new \Illuminate\Database\Capsule\Manager;
    $db_config = $c['settings']['db'];
    foreach ($db_config as $key => $v) {
        $capsule->addConnection( $v, $key );
    }
    $capsule->setEventDispatcher( new Dispatcher( new Container ) );
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    return $capsule;
};

$container['redis'] = function ($c) {
    $settings = $c->get( 'settings' )['cache'];
    $config   = [
        'scheme'   => $settings['scheme'],
        'host'     => $settings['host'],
        'port'     => $settings['port'],
        'database' => $settings['database'],
    ];

    if (!empty( $settings['password'] )) {
        $config['password'] = $settings['password'];
    }
    if ($config['scheme'] == 'tls') {
        $config['ssl'] = $settings['ssl'];
    }
    return new Predis\Client( $config );
};

$container['Controller']  = function ($c) {
    return new Controller( __DIR__, $c );
};

$container['lang'] = function ($c) {
    $langConfig =
        (require __DIR__ . '/../../config/lang/api.admin.php');
    return new \Logic\Define\ErrMsg( $c, $langConfig );
};

$container['notFoundHandler'] = function ($c) {
    return function () use ($c) {
        $controller = new Controller( __DIR__, $c );
        return $controller->run();
    };
};

$container['phpErrorHandler'] = $container['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        $debug = [
            'type'    => get_class( $exception ),
            'code'    => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
            'trace'   => explode( "\n", $exception->getTraceAsString() )
        ];
        $c->logger->error( '程序异常', $debug );
        $data = [
            'data'       => null,
            'attributes' => null,
            'state'      => -9999,
            'message'    => '程序运行异常'
        ];
        if (RUNMODE == 'dev') {
            $data['debug'] = $debug;
        }
        return $c['response']->withStatus( 500 )->withHeader( 'Content-Type', 'application/json' )->withJson( $data );
    };
};