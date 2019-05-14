<?php

$app->add(new \atk4\invoice\InvoiceMgr([
   'invoiceModel'           => new \atk4\invoice\Model\Invoice($db, ['taxRate'=> 0.1]),
   'paymentModel'           =>  new \atk4\invoice\Model\Payment($db),
   'tableFields'            => ['reference', 'client', 'date', 'due_date', 'g_total', 'balance'],
   'headerFields'           => ['reference', 'date', 'due_date', 'client_id', 'paid_total'],
   'footerFields'           => ['sub_total', 'tax', 'g_total'],
   'itemFields'             => ['item', 'qty', 'rate', 'amount'],
   'paymentRelations'       => ['invoice_id' => 'id', 'client_id' => 'client_id'],
   'paymentEditFields'      => ['method', 'paid_on', 'amount', 'details'],
   'paymentDisplayFields'   => ['client','balance', 'paid_total','g_total'],
   'itemRef'                => 'Items',
   'itemLink'               => 'invoice_id'
]));
