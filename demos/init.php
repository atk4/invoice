<?php
namespace atk4\invoice;
require_once '../vendor/autoload.php';
require_once __DIR__ . '/config.php'; 

$app = new \atk4\ui\App();
$app->initLayout('Centered');
$app->dbConnect($config['db']);
