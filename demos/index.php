<?php
namespace Atk4\Invoice;
require_once __DIR__ . '/init.php';

use Atk4\Schema\MigratorConsole;
use Atk4\Ui\Button;
use Atk4\Ui\Header;

Header::addTo($app, ['Migration Console']);

Button::addTo($app, ['Go to demo..', 'big primary'])->link(['invoice']);
$app->add([MigratorConsole::class])
    ->migrateModels([
        new Model\Client($app->db),
        new Model\Invoice($app->db),
        new Model\InvoiceItems($app->db),
        new Model\Payment($app->db),
    ]);
