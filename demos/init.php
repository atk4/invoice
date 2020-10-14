<?php
namespace atk4\invoice;
require_once '../vendor/autoload.php';
require_once __DIR__ . '/config.php'; 

$app = new \atk4\ui\App();
$app->initLayout([\atk4\ui\Layout\Centered::class]);

$db = new \atk4\data\Persistence\Sql($config['db']);
$app->db = $db;
