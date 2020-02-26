<?php
namespace atk4\invoice;
include'../vendor/autoload.php';

$app = new \atk4\ui\App();
$app->initLayout('Centered');
$app->dbConnect('mysql://root:root@127.0.0.1/invoice');

return $app;
