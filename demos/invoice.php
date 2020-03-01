<?php
namespace atk4\invoice;
require_once __DIR__ . '/init.php';

use atk4\invoice\Model\Client;
use atk4\invoice\Model\Invoice;
use atk4\invoice\Model\Payment;

$app->add('CRUD')->setModel(new Client($app->db));

$app->add(new InvoiceMgr([
   'invoiceModel' => new Invoice($app->db ),
   'itemRef' => 'Items',
   'itemLink' => 'invoice_id',
   'clientRef' => 'client_id',
   'paymentModel' =>  new Payment($app->db),
   'tableFields' => ['ref_no', 'client', 'date', 'due_date', 'total_gross', 'balance'],
   'headerFields' => ['ref_no', 'date', 'due_date', 'client_id', 'vat_rate', 'total_paid'],
   'footerFields' => ['total_net', 'total_vat', 'total_gross'],
   'itemFields'   => ['item', 'price', 'qty', 'amount'],
   'paymentRelations' => ['invoice_id' => 'id', 'client_id' => 'client_id'],
   'paymentEditFields' => ['method', 'paid_on', 'amount', 'details'],
   'paymentDisplayFields' => ['client', 'balance', 'total_paid', 'total_gross'],
]));
