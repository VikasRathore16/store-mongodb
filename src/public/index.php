<?php

use Phalcon\Di\FactoryDefault;
use Phalcon\Loader;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\Url;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Config;
use Phalcon\Config\ConfigFactory;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\Stream;
use Phalcon\Events\Event;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Session\Manager;
use Phalcon\Cache;
use Phalcon\Cache\Adapter\Memory;
use Phalcon\Storage\SerializerFactory;


// Define some absolute path constants to aid in locating resources
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

require APP_PATH . '/library/vendor/autoload.php';


// Config  ----------------------------------------------start --------------------------------------

$config = new Config([]);
$fileName = '../app/etc/config.php';
$factory  = new ConfigFactory();
$config = $factory->newInstance('php', $fileName);

// Config  ----------------------------------------------end -----------------------------------------



//Logger ------------------------------------------------start ---------------------------------------

$adapter = new Stream('../app/storage/main.log');
$logger  = new Logger(
    'messages',
    [
        'main' => $adapter,
    ]
);

//Logger ------------------------------------------------ends ----------------------------------------


// Loader-----------------------------------------------start ---------------------------------------- 

$loader = new Loader();

$loader->registerDirs(
    [
        APP_PATH . "/controllers/",
        APP_PATH . "/models/",
    ]
);

$loader->registerNamespaces(
    [
        'App\Components' => APP_PATH . '/components',
        'App\Listeners'  => APP_PATH . '/listeners',
    ]
);

$loader->register();

// Loader---------------------------------------------ends --------------------------------------------


#container--------------------------------------------------start -------------------------------------

$container = new FactoryDefault();

$container->set(
    'view',
    function () {
        $view = new View();
        $view->setViewsDir(APP_PATH . '/views/');
        return $view;
    }
);

$container->set(
    'url',
    function () {
        $url = new Url();
        $url->setBaseUri('/');
        return $url;
    }
);

$container->set(
    'mongo',
    function () {
        $mongo = new \MongoDB\Driver\Manager("mongodb://mongo", array("username" => 'root', "password" => "password123"));

        return $mongo;
    },
    true
);

$container->set(
    'config',
    $config,
    true
);

$container->set(
    'logger',
    $logger,
);


//Event Mangement -----------------------------------------start ------------------------------------------------------

$eventsManager = new EventsManager();

$container->set(
    'db',
    function () use ($eventsManager) {
        $config = $this->get('config');
        $connection = new Mysql(
            [
                'host'     => $config->db->host,
                'username' => $config->db->username,
                'password' => $config->db->password,
                'dbname'   => $config->db->dbname,
            ]
        );
        $connection->setEventsManager($eventsManager);
        return $connection;
    }
);

$eventsManager->attach(
    'notification',
    new \App\Listeners\NotificationListeners()
);


$eventsManager->attach(
    'db:afterQuery',
    function (Event $event, $connection) use ($logger) {
        // die('db');
        $logger->error($connection->getSQLStatement());
    }
);


$eventsManager->attach(
    'application:beforeHandleRequest',
    new \App\Listeners\NotificationListeners()
);

$container->set(
    'locale',
    (new \App\Components\Locale())->getTranslator()
);
$container->set(
    'EventsManager',
    $eventsManager,
);

//Event Mangement -----------------------------------------ends ------------------------------------------------------


//Cache ---------------------------------------------------starts ----------------------------------------------------



$serializerFactory = new SerializerFactory();
$options = [
    'lifetime' => 7200,
    'storageDir'        => APP_PATH . '/storage/cache',
];

$adapter = new Memory($serializerFactory, $options);
$adapter = new Phalcon\Cache\Adapter\Stream($serializerFactory, $options);
$cache = new Cache($adapter);
$container->set('cache', $cache);


//Cache ---------------------------------------------------ends ----------------------------------------------------


//session ----------------------------------------------start ----------------------------------------


$container->set(
    'session',
    function () {
        $session = new Manager();
        $files = new \Phalcon\Session\Adapter\Stream(
            [
                'savePath' => '/tmp',
            ]
        );

        $session
            ->setAdapter($files)
            ->setName('user')
            ->start();

        return $session;
    }
);


//session --------------------------------------------- end ----------------------------------------------------




$application = new Application($container);
$application->setEventsManager($eventsManager);

//container ------------------------------------------------ends ------------------------------------------------------





try {
    // Handle the request
    $response = $application->handle(
        $_SERVER["REQUEST_URI"]
    );

    $response->send();
} catch (\Exception $e) {
    echo 'Exception: ', $e->getMessage();
}
