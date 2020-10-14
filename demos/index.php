<?php
namespace atk4\invoice;
require_once __DIR__ . '/init.php';

use atk4\schema\MigratorConsole;
use atk4\ui\Button;
use atk4\ui\Header;

Header::addTo($app, ['Migration Console']);

Button::addTo($app, ['Go to demo..', 'big primary'])->link(['invoice']);
$app->add([MigratorConsole::class])
    ->migrateModels([
        new Model\Client($app->db),
        new Model\Invoice($app->db),
        new Model\InvoiceItems($app->db),
        new Model\Payment($app->db),
    ]);
