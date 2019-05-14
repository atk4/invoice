<?php
/**
 * Manage invoice pages.
 */

namespace atk4\invoice;

use atk4\ui\Exception;
use atk4\ui\View;

class InvoiceMgr extends View
{
    public $invoice = null;

    public $invoiceModel = null;
    public $tableFields = null;
    public $headerFields = [];
    public $footerFields = [];
    public $itemFields =  null;

    public $paymentModel = null;
    public $paymentRelations = null;
    public $paymentEditFields = null;
    public $paymentDisplayFields = null;

    public $itemRef = null;
    public $itemLink = null;

    public function init()
    {
        parent::init();

        if (!$this->invoiceModel) {
            throw new Exception('Invoice model not set.');
        }

        $this->invoice = $this->add(new Invoice([
                                'model'          => $this->invoiceModel,
                                'tableFields'    => $this->tableFields,
                                'hasPayment'     => $this->paymentModel ? true : false,
                            ]));


        // set page for editing invoice.
        $this->invoice->setInvoicePage(function($page, $id) {

            $crumb = $page->add(['BreadCrumb',null, 'big']);

            $page->add(['ui' =>'divider']);

            $crumb->addCrumb('Invoices', $this->invoice->getURL());

            $m = $page->add('Menu');
            $m->addItem(['Payments', 'icon' => 'dollar sign'])->link($this->invoice->paymentPage->getURL());

            $form = $page->add(['Form', 'canLeave' => false]);

            if ($id) {
                $this->invoiceModel->load($id);
                $crumb->addCrumb($this->invoiceModel->getTitle());
            } else {
                $crumb->addCrumb('New Invoice');
            }
            $crumb->popTitle();

            $form->add(['Button', 'Back'])->link($this->invoice->url());

            $m = $form->setModel($this->invoiceModel, false);

            $headerLayout = $form->layout->addSubLayout('Generic');
            $headerGroup = $headerLayout->addGroup();
            $headerGroup->setModel($m, $this->headerFields);

            $itemLayout = $form->layout->addSubLayout('Generic');
            $itemLayout->add(['Header', 'Invoice Items', 'size' => 4]);

            $ml = $itemLayout->addField('ml', ['MultiLine', 'options' => ['size' => 'small']]);
            $ml->setModel($m, $this->itemFields, $this->itemRef, $this->itemLink);

            $ml->onLineChange([$this->invoiceModel, 'jsUpdateFields'], ['qty', 'rate']);


            $columnsLayout = $form->layout->addSubLayout('Columns');
            $columnsLayout->addColumn(12);
            $c = $columnsLayout->addColumn(4);
            $c->setModel($m, $this->footerFields );

            $form->onSubmit(function($f) use ($ml) {
                $f->model->save();
                $ml->saveRows();

                return new \atk4\ui\jsToast('Saved!');
            });
        });

        $this->invoice->setPrintPage(function($page, $id) {
            $invoice_items = $this->invoiceModel->load($id)->ref($this->itemRef);
            $container = $page->add('View')->setStyle(['width' => '900px', 'margin-top' => '20px']);
            $gl_top = $container->add(['GridLayout', ['rows' => 5, 'columns' => 2]])->setStyle(['width' => '900px', 'margin-top' => '20px']);

            $comp_view = $gl_top->add(['View', 'defaultTemplate' => $this->invoice->getDir('template').'/company.html'], 'r1c1');
            $comp_view->template->set('name', 'My Company');
            $comp_view->template->set('image', $this->invoice->getDir('public').'/images/logo.png');

            $inv_info = $gl_top->add('View', 'r1c2');
            $inv_info->add(['Header', 'Invoice', 'subHeader' => '#'.$this->invoiceModel->getTitle()])->addClass('aligned right');
            $inv_info->add(['Header', 'Balance', 'size' => 3, 'subHeader' => $this->invoice->get('balance')])->addClass('aligned right');

            $bill_to = $container->add(['View', 'ui' => 'basic segment']);
            $bill_to->add(['Header', 'Bill to: '.$this->invoiceModel->get('client'), 'size'=> 4]);
            $table_view  = $container->add(['View']);
            $table = $table_view->add('Table')->setModel($invoice_items);

            $container->add(['ui' => 'hidden divider']);

            $gl_bottom = $container->add(['GridLayout', ['rows' => 1, 'columns' => 4]]);
            $card_container = $gl_bottom->add(['View', 'ui' => 'aligned right'], 'r1c4');
            $card = $card_container->add(['Card', 'header' => false]);
            $card->setModel($this->invoiceModel, $this->footerFields);
        });

        // set payment page.
        $this->invoice->setPaymentPage(function($page, $id) {
            $this->invoiceModel->load($id);
            $this->paymentModel->addCondition('invoice_id', $id);

            $balance = 'Balance: '.$this->invoice->get('balance');

            // setup payment editing page.
            $paymentEdit = $page->add(['VirtualPage', 'urlTrigger' => 'p-edit']);
            $editCrumb = $paymentEdit->add(['BreadCrumb', null, 'big']);
            $paymentEdit->add(['ui' =>'divider']);

            $paymentEdit->add(['Header', $balance]);
            $editCrumb->addCrumb('Invoices', 'invoice-addon.php');
            $editCrumb->addCrumb($this->invoiceModel->getTitle().' \'s payments', $this->invoice->paymentPage->getURL());

            $pId = $page->stickyGet('pId');
            if ($pId) {
                $this->paymentModel->load($pId);
                $editCrumb->addCrumb('Edit payment');
            } else {
                $editCrumb->addCrumb('New payment');
            }
            $editCrumb->popTitle();

            $formPayment = $paymentEdit->add('Form');
            $formPayment->setModel($this->paymentModel, $this->paymentEditFields);
            $formPayment->onSubmit(function($f) {
                foreach ($this->paymentRelations as $paiement => $relation) {
                    $f->model[$paiement] = $this->invoiceModel[$relation];
                }
                $f->model->save();

                return $this->app->jsRedirect($this->invoice->paymentPage->getURL());
            });

            // setup payment grid display
            $crumb = $page->add(['BreadCrumb',null, 'big']);
            $page->add(['ui' =>'divider']);

            $crumb->addCrumb('Invoices', $this->invoice->getUrl());
            $crumb->addCrumb($this->invoiceModel->getTitle().' \'s payments');
            $crumb->popTitle();

            $m = $page->add('Menu');
            $m->addItem(['Add Payment', 'icon' => 'plus'])->link($paymentEdit->getURL());
            $m->addItem(['Edit Invoice', 'icon' => 'edit'])->link($this->invoice->invoicePage->getURL());

            $gl = $page->add(['GridLayout', ['columns'=>3, 'rows'=>1]]);
            $seg = $gl->add(['View', 'ui' => 'basic segment'], 'r1c1');
            $card = $seg->add(['Card', 'header' => false]);
            $card->setModel($this->invoiceModel, $this->paymentDisplayFields);

            $page->add(['ui' =>'hidden divider']);

            // Add payment table.
            $g = $page->add('Table');
            $g->setModel($this->paymentModel);
            $actions = $g->addColumn(null, 'Actions');
            $actions->addAction(['icon' => 'edit'], $this->invoice->jsIIF($paymentEdit->getURL(), 'pId'));
            $actions->addAction(['icon' => 'trash'], function ($js, $id) use ($g, $seg){
                $g->model->load($id)->delete();

                return [$js->closest('tr')->transition('fade left'), $seg->jsReload()];
            }, $this->invoice->confirmMsg);
        });
    }
}
