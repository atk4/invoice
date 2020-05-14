<?php
/**
 * Manage invoice pages.
 */
namespace atk4\invoice;

use atk4\data\Model;
use atk4\ui\ActionExecutor\UserConfirmation;
use atk4\ui\BreadCrumb;
use atk4\ui\Button;
use atk4\ui\Card;
use atk4\ui\Exception;
use atk4\ui\GridLayout;
use atk4\ui\Header;
use atk4\ui\Form;
use atk4\ui\jsExpression;
use atk4\ui\Menu;
use atk4\ui\jsToast;
use atk4\ui\Table;
use atk4\ui\View;
use atk4\ui\VirtualPage;

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
    public $paymentDisplayFields = null;


    /** @var array @deprecated Use custom form instead. */
    public $headerFields = [];
    /** @var array @deprecated use custom form instead. */
    public $footerFields = [];

    public function init(): void
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

            $crumb = BreadCrumb::addTo($page, [null, 'big']);

            View::addTo($page, ['ui' =>'divider']);

            $crumb->addCrumb('Invoices', $this->invoice->getUrl());

            $menu = Menu::addTo($page);
            if ($this->paymentModel) {
                $menu->addItem(['Payments', 'icon' => 'dollar sign'])->link($this->invoice->getURL($this->invoice->paymentPage->urlTrigger));
            }
            $menu->addItem(['Print', 'icon' => 'print'])->link($this->invoice->getURL($this->invoice->printPage->urlTrigger));

            $form = Form::addTo($page, ['canLeave' => false, 'layout' => [new Layout\InvoiceForm()]]);

            if ($id) {
                $this->invoiceModel->load($id);
                $crumb->addCrumb($this->invoiceModel->getTitle());
                $menu->addItem(['Add Invoice', 'icon' => 'plus'], $this->invoice->getAddInvoiceAction())->addClass('floated right');
                $menu->addItem(['Delete', 'icon' => 'times'], $this->getDeleteInvoiceAction());
            }

            $crumb->popTitle();

            Button::addTo($form,['Back'])->link($this->invoice->getUrl());

            $f_model = $form->setModel($this->invoiceModel);

            $form->addField('total_vat', ['readonly' => true]);
            $form->addField('total_net', ['readonly' => true]);
            $form->addField('total_gross', ['readonly' => true]);

            $ml = $form->addField('ml', ['MultiLine', 'options' => ['size' => 'small'], 'caption' => 'Items']);
            $ml->setModel($f_model, $this->itemFields, $this->itemRef, $this->itemLink);
            $ml->onLineChange([$this->invoiceModel, 'jsUpdateFields'], ['qty', 'price']);

            $form->onSubmit(function($f) use ($ml) {
                $f->model->save();
                $ml->saveRows();

                return [
                    new jsToast('Saved!'),
                    new jsExpression('document.location = [url]', ['url' => $this->invoice->getUrl('invoice')])
                ];
            });
        });

        // set page for printing invoice.
        $this->invoice->setPrintPage(function($page, $id) {
            $invoice_items = $this->invoiceModel->load($id)->ref($this->itemRef);
            $container = View::addTo($page)->setStyle(['width' => '900px', 'margin-top' => '20px']);
            $gl_top = GridLayout::addTo($container, [['rows' => 5, 'columns' => 2]])->setStyle(['width' => '900px', 'margin-top' => '20px']);

            $comp_view = $gl_top->add(['View', 'defaultTemplate' => $this->invoice->getDir('template').'/company.html'], 'r1c1');
            $comp_view->template->set('name', 'My Company');
            $comp_view->template->set('image', $this->invoice->getDir('public').'/images/logo.png');

            $inv_info = $gl_top->add('View', 'r1c2');
            Header::addTo($inv_info, ['Invoice', 'subHeader' => '#'.$this->invoiceModel->getTitle()])->addClass('aligned right');
            Header::addTo($inv_info, ['Balance', 'size' => 3, 'subHeader' => $this->invoice->get('balance')])->addClass('aligned right');

            $bill_to = View::addTo($container, ['ui' => 'basic segment']);
            Header::addTo($bill_to, ['Bill to: '.$this->invoiceModel->ref($this->clientRef ?: 'client')->getTitle(), 'size'=> 4]);
            $table_view  = View::addTo($container);
            $table = Table::addTo($table_view)->setModel($invoice_items);

            View::addTo($container, ['ui' => 'hidden divider']);

            $gl_bottom = GridLayout::addTo($container, [['rows' => 1, 'columns' => 4]]);
            $card_container = $gl_bottom->add(['View', 'ui' => 'aligned right'], 'r1c4');
            $card = Card::addTo($card_container, ['header' => false]);
            $card->setModel($this->invoiceModel, $this->footerFields);
        });

        // set payment page.
        if ($this->paymentModel) {
            // set payment page.
            $this->invoice->setPaymentPage(function($page, $id) {
                $this->invoiceModel->load($id);
                $this->paymentModel->addCondition($this->findRelatedField($this->paymentModel, $this->invoiceModel), $id);

                $refs = $this->paymentModel->getRefs();

                $balance = 'Balance: '.$this->invoice->get('balance');

                // setup payment editing page.
                $paymentEdit = VirtualPage::addTo($page, ['urlTrigger' => 'p-edit']);
                $editCrumb = BreadCrumb::addTo($paymentEdit, [null, 'big']);
                View::addTo($paymentEdit, ['ui' =>'divider']);

                Header::addTo($paymentEdit, [$balance]);
                $editCrumb->addCrumb('Invoices', $this->invoice->getURL());
                $editCrumb->addCrumb($this->invoiceModel->getTitle(), $this->invoice->getUrl('invoice'));
                $editCrumb->addCrumb($this->invoiceModel->getTitle().' \'s payments', $this->invoice->getURL('payment'));

                $pId = $page->stickyGet('pId');
                if ($pId) {
                    $this->paymentModel->load($pId);
                    $editCrumb->addCrumb('Edit payment');
                } else {
                    $editCrumb->addCrumb('New payment');
                }
                $editCrumb->popTitle();

                $formPayment = Form::addTo($paymentEdit);
                $formPayment->setModel($this->paymentModel, $this->paymentEditFields);
                $formPayment->onSubmit(function($f) {
                    foreach ($this->paymentRelations as $paiement => $relation) {
                        $f->model[$paiement] = $this->invoiceModel[$relation];
                    }
                    $f->model->save();

                    return  [
                        new jsToast(['message' => 'Saved! Redirecting to Invoice', 'duration' => 0]),
                        new jsExpression('document.location = [url]', ['url' => $this->invoice->getUrl('payment')])
                    ];
                });

                // setup payment grid display
                $crumb = BreadCrumb::addTo($page, [null, 'big']);
                View::addTo($page, ['ui' => 'divider']);

                $crumb->addCrumb('Invoices', $this->invoice->getUrl());
                $crumb->addCrumb($this->invoiceModel->getTitle(), $this->invoice->getUrl('invoice'));
                $crumb->addCrumb($this->invoiceModel->getTitle().' \'s payments');
                $crumb->popTitle();

                $m = Menu::addTo($page);
                $m->addItem(['Add Payment', 'icon' => 'plus'])->link($paymentEdit->getURL());

                $gl = GridLayout::addTo($page, [['columns'=>3, 'rows'=>1]]);
                $seg = $gl->add(['View', 'ui' => 'basic segment'], 'r1c1');
                $card = Card::addTo($seg, ['header' => false, 'useLabel' => true]);
                $card->setModel($this->invoiceModel, $this->paymentDisplayFields);

                View::addTo($page, ['ui' => 'hidden divider']);

                // Add payment table.
                $g = Table::addTo($page);
                $g->setModel($this->paymentModel);
                $actions = $g->addColumn(null, 'ActionButtons');
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
     *
     * @param Model $model
     * @param Model $related
     *
     * @return string|null
     */
    public function findRelatedField(Model $model, Model $related)
    {
        $link = null;
        $refs = $model->getRefs();
        foreach ($refs as $ref) {
            if ($ref->getModel()->table === $related->table) {
                $link = $ref->link;
                break;
            }
        }
        return $link;
    }

    /**
     * Properly wired delete action when in Invoice page.
     *
     * @return mixed
     * @throws \atk4\core\Exception
     */
    public function getDeleteInvoiceAction()
    {
        $ex = new UserConfirmation(['title' => 'Delete Invoice!']);
        $ex->onHook('afterExecute', function($x, $return){
            return [
                new jsToast($return),
                new jsExpression('document.location = [url]', ['url' => $this->invoice->getUrl()]),
            ];
        });
        $delete = $this->invoiceModel->getAction('delete');
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
        };

        return $delete;
    }
}
