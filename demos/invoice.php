<?php

$invoice_model = new \atk4\invoice\Model\Invoice($db, [
    'taxRate'       => 0.1,
]);


$invoice = $app->add(new \atk4\invoice\Invoice([
           'model'          => $invoice_model,
           'itemsRef'       => 'Items',
           'itemsRefFields' => ['item', 'qty', 'rate', 'amount'],
           'itemsRefId'     => 'invoice_id',
           'tableFields'    => ['reference', 'client', 'date', 'due_date', 'g_total', 'balance'],
           'headerFields'   => ['reference', 'date', 'due_date', 'bill_to_id', 'paid_total'],
           'footerFields'   => ['sub_total', 'tax', 'g_total'],
           'eventFields'    => ['qty', 'rate'],
       ]));

$invoice->setJsAction(function() use ($invoice, $app) {
    return $app->jsRedirect('invoice-addon.php');
});

$invoice->setFormLayout('Invoice', 'Invoice Items', [$invoice_model, 'jsUpdateFields']);
