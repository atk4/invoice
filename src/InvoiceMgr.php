<?php

declare(strict_types = 1);

namespace Atk4\Invoice;

use Atk4\Data\Model;
use Atk4\Invoice\Layout\InvoiceForm;
use Atk4\Invoice\Layout\InvoicePrint;
use Atk4\Ui\BreadCrumb;
use Atk4\Ui\Button;
use Atk4\Ui\Card;
use Atk4\Ui\Exception;
use Atk4\Ui\GridLayout;
use Atk4\Ui\Header;
use Atk4\Ui\Form;
use Atk4\Ui\JsExpression;
use Atk4\Ui\Menu;
use Atk4\Ui\JsToast;
use Atk4\Ui\Table;
use Atk4\Ui\UserAction\BasicExecutor;
use Atk4\Ui\UserAction\ConfirmationExecutor;
use Atk4\Ui\View;
use Atk4\Ui\VirtualPage;

/**
 * Manage invoice pages.
 * Act as a controller for VirtualPages created in invoice View.
 *
 * This is where content for virtual pages, like editing invoice, payments or display print page, are added.
 * Virtual page are created in $invoice View instance but are set using this View.
 *
 */
class InvoiceMgr extends View
{
    /** @var View A view for listing the invoices. */
    public $invoice;

    /** @var Model Invoice model  */
    public $invoiceModel;

    /** @var string The qualifier name for hasMany relation between the Invoice and InvoiceItems model that was set in Invoice model*/
    public $itemRef;

    /** @var string The qualifier name for hasOne relation between the InvoiceItems and Invoice model that was set in InvoiceItems model. */
    public $itemLink;

    /** @var string The qualifier name for hasOne relation between the Invoice and Client model that was set in Invoice model. */
    public $clientRef;

    /** @var array A list of field, from invoiceModel, to display in Invoices table. */
    public $tableFields;

    /** @var array A list of field from InvoiceItems model to use in MultiLine Input Field. */
    public $itemFields;

    /** @var Model The Model for payment. */
    public $paymentModel;

    /**
     * When payment is save, related field will be saved using this array content in regards
     * to the invoice model.
     *  ex when using: ['client_id' => 'client_id']
     *      This will save field payment.client_id using invoice.client_id.
     *
     * @var  array A list of array representing models relation using fields between Payment and Invoice model.
     */
    public $paymentRelations;

    /** @var array A list of field to display for adding/editing Payment model. */
    public $paymentEditFields;

    /** @var array A list of field from Invoice Model to be display in Payment page. */
    public $paymentDisplayFields;


    /** @var array @deprecated Use custom form instead. */
    public $headerFields = [];
    /** @var array @deprecated use custom form instead. */
    public $footerFields = [];

    protected function init(): void
    {
        parent::init();

        if (!$this->invoiceModel) {
            throw new Exception('Invoice model not set.');
        }

        // creating the default Invoice view.
        $this->invoice = $this->add(new Invoice([
                                'model'          => $this->invoiceModel,
                                'tableFields'    => $this->tableFields,
                                'hasPayment'     => $this->paymentModel ? true : false,
                            ]));


        // set page for editing invoice.
        $this->invoice->setInvoicePage(function($page, $id) {
            $invoiceRecord = $this->invoiceModel->tryLoad($id);
            $crumb = BreadCrumb::addTo($page, [null, 'big']);

            View::addTo($page, ['ui' =>'divider']);

            $crumb->addCrumb('Invoices', $this->invoice->getUrl());

            $menu = Menu::addTo($page);
            if ($this->paymentModel) {
                $menu->addItem(['Payments', 'icon' => 'dollar sign'])->link($this->invoice->paymentPage->getUrl());

            }
            $menu->addItem(['Print', 'icon' => 'print'])->link($this->invoice->printPage->getUrl('popup'));

            $form = Form::addTo($page, ['canLeave' => false, 'layout' => [InvoiceForm::class]]);

            if ($id) {
                $this->invoiceModel->load($id);
                $crumb->addCrumb($invoiceRecord->getTitle());
                $menu->addItem(['Delete', 'icon' => 'times'], $this->getDeleteInvoiceAction())->addClass('floated right');
            }

            $crumb->popTitle();

            Button::addTo($form,['Back'])->link($this->invoice->getUrl());

            $f_model = $form->setModel($invoiceRecord);

            $form->addControl('total_vat', [Form\Control\Line::class, 'readonly' => true]);
            $form->addControl('total_net', [Form\Control\Line::class, 'readonly' => true]);
            $form->addControl('total_gross', [Form\Control\Line::class, 'readonly' => true]);

            $ml = $form->addControl('ml', [Form\Control\Multiline::class, 'tableProps' => ['size' => 'small'], 'caption' => 'Items'], ['never_persist' => true]);
            $ml->setReferenceModel($this->itemRef, null, $this->itemFields);
            $ml->onLineChange(\Closure::fromCallable([$this->invoiceModel, 'jsUpdateFields']), ['qty', 'price']);

            $form->onSubmit(function($f) use ($ml) {
                $f->model->save();
                $ml->saveRows();

                return [
                    new JsToast('Saved!'),
                    new JsExpression('document.location = [url]', ['url' => $this->invoice->getUrl(true)])
                ];
            });
        });

        // set page for printing invoice.
        $this->invoice->setPrintPage(function($page, $id) {
            $invoice_items = $this->invoiceModel->load($id)->ref($this->itemRef);
            $print = InvoicePrint::addTo($page, ['uiPersistence' => $this->getApp()->ui_persistence]);
            $print->setModel($this->invoiceModel->load($id));

            $print->add([Table::class], 'InvoiceItems')->addClass('celled striped')->setModel($invoice_items);
        });

        // set payment page.
        if ($this->paymentModel) {
            // set payment page.
            $this->invoice->setPaymentPage(function($page, $id) {
                $invoiceRecord = $this->invoiceModel->load($id);
                $this->paymentModel->addCondition($this->findRelatedField($this->paymentModel, $this->invoiceModel), $id);

                // setup payment editing page.
                $paymentEdit = VirtualPage::addTo($page, ['urlTrigger' => 'p-edit']);

                $paymentEdit->set(function($paymentPage) use ($page, $invoiceRecord) {
                    $balance = 'Balance: '.$invoiceRecord->get('balance');

                    $editCrumb = BreadCrumb::addTo($paymentPage, [null, 'big']);
                    View::addTo($paymentPage, ['ui' =>'divider']);

                    Header::addTo($paymentPage, [$balance]);
                    $editCrumb->addCrumb('Invoices', $this->invoice->getURL(false));
                    $editCrumb->addCrumb($invoiceRecord->getTitle(), $this->invoice->invoicePage->getUrl());
                    $editCrumb->addCrumb($invoiceRecord->getTitle().' \'s payments', $page->getUrl());

                    $pId = $page->stickyGet('pId');
                    $paymentRecord = $this->paymentModel->tryLoad($pId);
                    if ($pId) {
                        $editCrumb->addCrumb('Edit payment');
                    } else {
                        $editCrumb->addCrumb('New payment');
                    }
                    $editCrumb->popTitle();

                    $formPayment = Form::addTo($paymentPage);
                    $formPayment->setModel($paymentRecord, $this->paymentEditFields);
                    $formPayment->onSubmit(function($f) use($invoiceRecord) {
                        foreach ($this->paymentRelations as $paiement => $relation) {
                            $f->model->set($paiement, $invoiceRecord->get($relation));
                        }
                        $f->model->save();

                        return  [
                            new JsToast(['message' => 'Saved! Redirecting to Invoice', 'duration' => 0]),
                            new JsExpression('document.location = [url]', ['url' => $this->invoice->paymentPage->getUrl()])
                        ];
                    });
                });


                // setup payment grid display
                $crumb = BreadCrumb::addTo($page, [null, 'big']);
                View::addTo($page, ['ui' => 'divider']);

                $crumb->addCrumb('Invoices', $this->invoice->getUrl(true));
                $crumb->addCrumb($invoiceRecord->getTitle(), $this->invoice->invoicePage->getUrl());
                $crumb->addCrumb($invoiceRecord->getTitle().' \'s payments');
                $crumb->popTitle();

                $m = Menu::addTo($page);
                $m->addItem(['Add Payment', 'icon' => 'plus'])->link($paymentEdit->getURL());

                $gl = GridLayout::addTo($page, [['columns'=>3, 'rows'=>1]]);
                $seg = View::addTo($gl, ['ui' => 'basic segment'], ['r1c1']);
                $card = Card::addTo($seg, ['header' => false, 'useLabel' => true]);
                $card->setModel($invoiceRecord, $this->paymentDisplayFields);

                View::addTo($page, ['ui' => 'hidden divider']);

                // Add payment table.
                $g = Table::addTo($page);
                $g->setModel($this->paymentModel);
                $actions = $g->addColumn(null, [Table\Column\ActionButtons::class]);
                $actions->addButton(['icon' => 'edit'], $this->invoice->jsIIF($paymentEdit->getURL(), 'pId'));
                $actions->addButton(['icon' => 'trash'], function ($js, $id) use ($g, $seg){
                    $g->model->load($id)->delete();

                    return [$js->closest('tr')->transition('fade left'), $seg->jsReload()];
                }, $this->invoice->confirmMsg);
            });
        }
    }

    /**
     * Find a related field between two model reference.
     * Link is field id for hasOne relation or the Reference name
     * for hasMany relation.
     */
    public function findRelatedField(Model $model, Model $related)
    {
        $link = null;
        $refs = $model->getRefs();
        foreach ($refs as $ref) {
            if ($ref->createTheirModel()->table === $related->table) {
                $link = $ref->link;
                break;
            }
        }
        return $link;
    }

    /**
     * Properly wired delete action when in Invoice page.
     */
    public function getDeleteInvoiceAction(): Model\UserAction
    {
        $ex = new ConfirmationExecutor(['title' => 'Delete Invoice!']);
        $ex->onHook(BasicExecutor::HOOK_AFTER_EXECUTE, function($x, $return){
            return [
                new JsToast($return),
                new JsExpression('document.location = [url]', ['url' => $this->invoice->getUrl()]),
            ];
        });
        $delete = $this->invoiceModel->getUSerAction('delete');
        $delete->ui['executor'] = $ex;
        $delete->confirmation = function ($a) {
            $m = $a->getModel();
            $title = $m->getField($m->title_field)->getCaption();
            $value = $m->getTitle();
            return "Delete invoice using {$title}: <b>{$value}</b>?";
        };
        $delete->callback =  function ($m) {
            if ($m->loaded()) {
                $m->delete();

                return 'Invoice  ' . $m->getTitle() . 'has been deleted!';
            }

            return 'Unable to delete invoice';
        };

        return $delete;
    }
}
