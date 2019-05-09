<?php
/**
 * Invoice
 *
 */

namespace atk4\invoice;

use atk4\ui\BreadCrumb;
use atk4\ui\Exception;
use atk4\ui\Form;
use atk4\ui\jQuery;
use atk4\ui\jsExpression;
use atk4\ui\jsToast;
use atk4\ui\View;

class Invoice extends View
{

    /**
     * The form for this view.
     * if none is supply then one is assign per default.
     *
     * @var null
     */
    public $form = null;

    public $invoiceEditPage = null;

    /**
     * The jsAction return by form.
     *
     * @var null
     */
    public $jsAction = null;

    /**
     * Options for the MultiLine Field.
     *
     * @var array
     */
    public $options = ['size' => 'small'];

    /**
     * The item refs use for MultiLine field.
     *
     * @var string]null
     */
    public $itemsRef = null;

    /**
     * The item reference field name for MultiLine field.
     *
     * @var string]null
     */
    public $itemsRefId = null;

    /**
     * An array of field name to include in MultiLine field row.
     *
     * @var array|null
     */
    public $itemsRefFields = null;

    /**
     * An array of field name to include in form layout
     * above the Multiline field rows.
     *
     * @var array|null
     */
    public $headerFields = null;

    /**
     * An array of field name to include in form layout
     * below the MultiLine field rows.
     *
     * @var array|null
     */
    public $footerFields = null;

    /**
     * An array of field name that will trigger
     * MultiLine onLineChange event.
     * These fields should be part of the Items reference model
     * set for MultiLine field.
     *
     * @var null
     */
    public $eventFields = null;

    public $tableFields = null;

    public $action;

    public $hasPayment = false;
    public $paymentPage = null;

    public $crumb;
    private $modelId;

    public function init()
    {
        parent::init();

        $this->app->useSuiVue();

        $this->modelId = $this->stickyGet('id');

        //$this->crumb = new BreadCrumb([null, 'big']);
        $page = $this->getPage();

        if (!$this->jsAction) {
            $this->jsAction = new jsToast('Saved!');
        }

        $this->invoiceEditPage = $this->add(['VirtualPage', 'urlTrigger' => 'invoice']);
        if (!$this->form) {
            $this->form = new Form();
        }

        if ($this->hasPayment) {
            $this->paymentPage = $this->add(['VirtualPage', 'urlTrigger' => 'payment']);
            //$this->setPaymentPage();
        }

        $this->displayInvoices();

    }

    /**
     * Default View.
     *
     * @throws Exception
     */
    public function displayInvoices()
    {
        $g = $this->add('Grid');
        $g->setModel($this->model, $this->tableFields);
        $g->menu->addItem(['Add Invoice', 'icon' => 'plus'])->link($this->invoiceEditPage->getURL());
        $g->addQuickSearch(['reference', 'date', 'due_date'], true);

        $g->addAction(['icon' => 'edit'], $this->jsIIF($this->invoiceEditPage->getURL()));

        if ($this->hasPayment) {
            $g->addAction(['icon' => 'dollar sign'], $this->jsIIF($this->paymentPage->getURL()));
        }

        $g->addAction(['icon' => 'trash'], function ($jschain, $id) {
            $this->model->load($id)->delete();

            return $jschain->closest('tr')->transition('fade left');
        }, 'Are you sure?');
    }


    public function setPaymentPage($crumbTitle, $paymentModel, $paymentRef)
    {
        if (!($this->getPage() === 'payment')) {
            return;
        }
        $crumb = $this->paymentPage->add(['BreadCrumb',null, 'big']);

        $this->paymentPage->add(['ui' =>'divider']);

        $crumb->addCrumb($crumbTitle, $this->url([], true));

        $paymentModel->addCondition($paymentRef, $this->id);


        $g = $this->paymentPage->add('Grid');


        $g->setModel($paymentModel);
    }

    public function getPage()
    {
        return array_search('callback', $_GET);
    }

    /**
     * Return an IIF js function that will set document.location via javascript.
     *
     * @param $url
     *
     * @return jsExpression
     */
    public function jsIIF($url)
    {
        return new jsExpression(
            '(function(url, id){document.location = url + "&id=" + id})([url],[id])',
            ['url' => $url, 'id' => (new jQuery())->closest('tr')->data('id')]
        );
    }

    /**
     * Redefine url function.
     * When isClean is true, will remove all query parameter.
     *
     * @param array $page
     * @param bool $isClean
     *
     * @return string
     */
    public function url($page = [], $isClean = false)
    {
        $url = parent::url($page);
        if ($isClean) {
            $url = strtok($url, '?');
        }
        return $url;
    }

    public function setJsAction($actions)
    {
        $this->jsAction = $actions;
    }

    /**
     * Set form model and layout.
     *
     * @param $headerCaption
     * @param $itemCaption
     * @param null $onChangeMethod
     *
     * @throws Exception
     * @throws \atk4\data\Exception
     */
    public function setInvoiceEditPage($crumbTitle, $itemCaption, $onChangeMethod = null)
    {
        if (!($this->getPage() === 'invoice')) {
            return;
        }

        if (!$this->model) {
            throw new Exception('Model need to be set for this method.');
        }

        $crumb = $this->invoiceEditPage->add(['BreadCrumb',null, 'big']);

        $this->invoiceEditPage->add(['ui' =>'divider']);

        $crumb->addCrumb($crumbTitle, $this->url([], true));

        $this->invoiceEditPage->add($this->form);

        if ($this->modelId) {
            $this->model->load($this->modelId);
            $crumb->addCrumb($this->model->getTitle());
        } else {
            $crumb->addCrumb('New Invoice');
        }
        $crumb->popTitle();

        $this->form->add(['Button', 'Cancel'])->link($this->url([], true));

        $m = $this->form->setModel($this->model, false);

        $headerLayout = $this->form->layout->addSubLayout('Generic');
        $headerGroup = $headerLayout->addGroup();
        $headerGroup->setModel($m, $this->headerFields);

        $itemLayout = $this->form->layout->addSubLayout('Generic');
        $itemLayout->add(['Header',$itemCaption, 'size' => 4]);

        $ml = $itemLayout->addField('ml', ['MultiLine', 'options' => $this->options]);
        $ml->setModel($m, $this->itemsRefFields, $this->itemsRef, $this->itemsRefId);

        if ($onChangeMethod) {
            $ml->onLineChange($onChangeMethod, $this->eventFields);
        }

        $columnsLayout = $this->form->layout->addSubLayout('Columns');
        $columnsLayout->addColumn(12);
        $c = $columnsLayout->addColumn(4);
        $c->setModel($m, $this->footerFields);

        $this->form->onSubmit(function($f) use ($ml) {
            $f->model->save();
            $ml->saveRows();
            if (is_callable($this->jsAction)) {
                return call_user_func($this->jsAction);
            }
            return $this->jsAction;
        });
    }
}
