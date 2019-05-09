<?php

$invoice_model = new \atk4\invoice\Model\Invoice($db, ['taxRate'=> 0.1]);
$payment_model = new \atk4\invoice\Model\Payment($db);

$invoice = $app->add(new \atk4\invoice\Invoice([
                                                   'model'          => $invoice_model,
                                                   'tableFields'    => ['reference', 'client', 'date', 'due_date', 'g_total', 'balance'],
                                                   'hasPayment'     => true
                                               ]));

$invoice->setInvoicePage(function($page, $id) use ($app, $invoice, $invoice_model) {

    $crumb = $page->add(['BreadCrumb',null, 'big']);

    $page->add(['ui' =>'divider']);

    $crumb->addCrumb('Invoices', $app->url('invoice-addon.php'));

    $m = $page->add('Menu');
    $m->addItem(['Add Payment', 'icon' => 'plus'])->link($invoice->paymentPage->getURL());
    //$m->addItem(['Edit '.$invoice_model->getTitle(), 'icon' => 'edit'])->link($invoice->invoicePage->getURL());

    $form = $page->add(['Form']);

    if ($id) {
        $invoice_model->load($id);
        $crumb->addCrumb($invoice_model->getTitle());
    } else {
        $crumb->addCrumb('New Invoice');
    }
    $crumb->popTitle();

    $form->add(['Button', 'Cancel'])->link('invoice-addon.php');

    $m = $form->setModel($invoice_model, false);

    $headerLayout = $form->layout->addSubLayout('Generic');
    $headerGroup = $headerLayout->addGroup();
    $headerGroup->setModel($m, ['reference', 'date', 'due_date', 'bill_to_id', 'paid_total']);

    $itemLayout = $form->layout->addSubLayout('Generic');
    $itemLayout->add(['Header', 'Invoice Items', 'size' => 4]);

    $ml = $itemLayout->addField('ml', ['MultiLine', 'options' => ['size' => 'small']]);
    $ml->setModel($m, ['item', 'qty', 'rate', 'amount'], 'Items', 'invoice_id');

    $ml->onLineChange([$invoice_model, 'jsUpdateFields'], ['qty', 'rate']);


    $columnsLayout = $form->layout->addSubLayout('Columns');
    $columnsLayout->addColumn(12);
    $c = $columnsLayout->addColumn(4);
    $c->setModel($m, ['sub_total', 'tax', 'g_total']);

    $form->onSubmit(function($f) use ($ml, $app) {
        $f->model->save();
        $ml->saveRows();
        return $app->jsRedirect('invoice-addon.php');
    });
});

$invoice->setPaymentPage(function($page, $id) use ($app, $invoice, $payment_model, $invoice_model) {
    $invoice_model->load($id);
    $payment_model->addCondition('invoice_id', $id);

    $balance = 'Balance: '.$app->ui_persistence->typecastSaveField($invoice_model->getElement('balance'), $invoice_model->get('balance'));

    // setup payment editing page.
    $paymentEdit = $page->add(['VirtualPage', 'urlTrigger' => 'p-edit']);
    $editCrumb = $paymentEdit->add(['BreadCrumb', null, 'big']);
    $paymentEdit->add(['ui' =>'divider']);

    $paymentEdit->add(['Header', $balance]);
    $editCrumb->addCrumb('Invoices', 'invoice-addon.php');
    $editCrumb->addCrumb($invoice_model->getTitle(), $invoice->paymentPage->getURL());

    $pId = $page->stickyGet('pId');
    if ($pId) {
        $payment_model->load($pId);
        $editCrumb->addCrumb('Edit');
    } else {
        $editCrumb->addCrumb('New');
    }
    $editCrumb->popTitle();

    $formPayment = $paymentEdit->add('Form');
    $formPayment->setModel($payment_model, ['method', 'paid_on', 'amount', 'details']);
    $formPayment->onSubmit(function($f) use ($app, $invoice, $invoice_model) {
        $f->model['invoice_id'] =  $invoice_model->get('id');
        $f->model['client_id']  =  $invoice_model->get('bill_to_id');
        $f->model->save();
        return $app->jsRedirect($invoice->paymentPage->getURL());
    });

    // setup payment grid display
    $crumb = $page->add(['BreadCrumb',null, 'big']);
    $page->add(['ui' =>'divider']);

    $crumb->addCrumb('Invoices', 'invoice-addon.php');
    $crumb->addCrumb($invoice_model->getTitle());
    $crumb->popTitle();

    $m = $page->add('Menu');
    $m->addItem(['Add Payment', 'icon' => 'plus'])->link($paymentEdit->getURL());
    $m->addItem(['Edit '.$invoice_model->getTitle(), 'icon' => 'edit'])->link($invoice->invoicePage->getURL());

    $gl = $page->add(['GridLayout', ['columns'=>3, 'rows'=>1]]);
    $seg = $gl->add(['View', 'ui' => 'basic segment'], 'r1c1');
    $card = $seg->add(['Card', 'header' => false]);
    $card->setModel($invoice_model, ['client','balance', 'paid_total','g_total']);

    $page->add(['ui' =>'hidden divider']);

    $g = $page->add('Table');
    $g->setModel($payment_model);
    $actions = $g->addColumn(null, 'Actions');
    $actions->addAction(['icon' => 'edit'], $invoice->jsIIF($paymentEdit->getURL(), 'pId'));
    $actions->addAction(['icon' => 'trash'], function ($js, $id) use ($g, $seg){
        $g->model->load($id)->delete();
        return [$js->closest('tr')->transition('fade left'), $seg->jsReload()];
    }, $invoice->confirmMsg);

});

