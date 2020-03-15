<?php
if(file_exists(__DIR__ . '/config-local.php')) {
    include(__DIR__ . '/config-local.php');
    return;
}
$config['db'] = 'mysql://root:root@127.0.0.1/invoice;charset=utf8mb4';
