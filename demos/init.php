<?php
namespace Atk4\Invoice;
require_once '../vendor/autoload.php';
require_once __DIR__ . '/config.php'; 

$app = new \Atk4\Ui\App();
$app->initLayout([\Atk4\Ui\Layout\Centered::class]);

$db = new \Atk4\Data\Persistence\Sql($config['db']);
$app->db = $db;
