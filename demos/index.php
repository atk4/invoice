<?php
namespace atk4\invoice;
use atk4\ui\Button;

$app = include 'setup.php';
$app->add(['Header','Migration Console']);

$app->add(['Button', 'Go to demo..', 'big primary'])->link(['invoice']);
$app->add(\atk4\schema\MigratorConsole::class)
    ->migrateModels([
        new Model\Client($app->db),
        new Model\Invoice($app->db),
        new Model\InvoiceItems($app->db),
        new Model\Payment($app->db),
    ]);
