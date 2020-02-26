<?php
namespace atk4\invoice;

use atk4\invoice\Model\Client;

$app = include 'setup.php';

$app->add('CRUD')->setModel(new Client($app->db));

$app->add(new \atk4\invoice\InvoiceMgr([
   'invoiceModel' => new \atk4\invoice\Model\Invoice($app->db ),
   'itemRef' => 'Items',
   'itemLink' => 'invoice_id',
   'clientRef' => 'client_id',
   'paymentModel' =>  new \atk4\invoice\Model\Payment($app->db),
   'tableFields' => ['reference', 'client', 'date', 'due_date', 'total', 'balance'],
   'headerFields' => ['reference', 'date', 'due_date', 'client_id', 'paid_total'],
   'footerFields' => ['subtotal', 'tax', 'total'],
   'itemFields'   => ['item', 'qty', 'rate', 'amount'],
   'paymentRelations' => ['invoice_id' => 'id', 'client_id' => 'client_id'],
   'paymentEditFields' => ['method', 'paid_on', 'amount', 'details'],
   'paymentDisplayFields' => ['client','balance', 'paid_total','total'],
]));
