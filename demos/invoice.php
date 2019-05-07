<?php

$invoice_model = new \atk4\invoice\Model\Invoice($db, [
    'taxRate'       => 0.1,
    'items'         => new \atk4\invoice\Model\InvoiceItems(),
    'itemsRef'      => 'Items',
    'itemsRefId'   => 'invoice_id',
]);

$invoice_model->tryLoadAny();

$invoice = $app->add(new \atk4\invoice\Invoice());
$invoice->setFormLayout($invoice_model);