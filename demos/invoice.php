<?php
namespace Atk4\Invoice;
require_once __DIR__ . '/init.php';

use Atk4\Invoice\Model\Client;
use Atk4\Invoice\Model\Invoice;
use Atk4\Invoice\Model\Payment;
use Atk4\Ui\Crud;

Crud::addTo($app)->setModel(new Client($app->db));

$app->add(new InvoiceMgr([
   'invoiceModel' => new Invoice($app->db),
   'itemRef' => 'Items',
   'itemLink' => 'invoice_id',
   'clientRef' => 'client_id',
   'paymentModel' =>  new Payment($app->db),
   'tableFields' => ['ref_no', 'client', 'date', 'due_date', 'total_gross', 'balance'],
   'itemFields'   => ['item', 'price', 'qty', 'amount'],
   'paymentRelations' => ['invoice_id' => 'id', 'client_id' => 'client_id'],
   'paymentEditFields' => ['method', 'paid_on', 'amount', 'details'],
   'paymentDisplayFields' => ['client', 'balance', 'total_paid', 'total_gross'],
]));
