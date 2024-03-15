<?php

use Logic\Admin\GameMenu;
use Model\Common\GameMenuModel;

require __DIR__ . '/../repo/vendor/autoload.php';
$settings = require __DIR__ . '/../config/settings.php';
$alias    = 'LDMessageServer';

\Workerman\Worker::$logFile = LOG_PATH . '/php/messageSever.log';
$worker                     = new \Workerman\Worker();
$worker->count              = 1;
$worker->name               = $alias;

// 防多开配置
// if ($app->getContainer()->redis->get(\Logic\Define\CacheKey::$perfix['prizeServer'])) {
//     echo 'prizeServer服务已启动，如果已关闭, 请等待5秒再启动', PHP_EOL;
//     exit;
// }

$worker->onWorkerStart = function ($worker) use ($settings) {
    global $app, $logger;
    /**********************config start*******************/
    // $settings = require __DIR__ . '/../config/settings.php';
    if (defined('ENCRYPTMODE') && ENCRYPTMODE) {
        $settings['settings'] = \Utils\Utils::settleCrypt($settings['settings'], false);
    }
    $app = new \Slim\App($settings);
    Utils\App::setApp($app);
    // Set up dependencies
    require __DIR__ . '/src/dependencies.php';

    // Register middleware
    require __DIR__ . '/src/middleware.php';

    require __DIR__ . '/src/common.php';

    $app->run();
    $app->getContainer()->db->getConnection('default');
    $logger = $app->getContainer()->logger;
    /**********************config end*******************/


    $proccId = 0;
    // 维护消息 1
    if ($worker->id === $proccId) {
        $interval = 60;
        \Workerman\Lib\Timer::add($interval, function () use (&$app) {
            //维护完成，维护时间已到，维护状态为工作，原维护状态为维护
            $ids = GameMenuModel::where('work_status', GameMenuModel::WORK_STATUS_OFF)
                ->where('end_uworked_at', '>', 0)
                ->where('end_uworked_at', '<', date('Y-m-d H:i:s', time()))
                ->pluck('id');
            foreach ($ids as $v) {
                $GameMenu = new GameMenu($app->getContainer());
                $GameMenu->setWorkStatus($v);
            }
        });
    }
};
\Workerman\Worker::runAll();
