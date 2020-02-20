<?php
/**
 * Invoice
 *
 */

namespace atk4\invoice;

use atk4\ui\Exception;
use atk4\ui\Grid;
use atk4\ui\jQuery;
use atk4\ui\jsExpression;
use atk4\ui\jsToast;
use atk4\ui\View;

class Invoice extends View
{

    public $invoicePage = null;

    public $confirmMsg = 'Are you sure?';

    public $tableFields = null;

    public $hasPayment = false;
    public $paymentPage = null;

    public $printPage = null;

    public $grid = null;
    public $ipp = 10;
    public $jsAction = null;
    
    private $modelId;
    private $page;
    private $sortBy;
    private $search;

    public function init()
    {
        parent::init();

        if (!$this->grid) {
            $this->grid = new Grid(['paginator' => ['urlTrigger' => 'p'], 'sortTrigger' => 'sortBy']);
        }

        $this->modelId = $this->stickyGet('id');
        $this->page    = $this->stickyGet('p');
        $this->sortBy  = $this->stickyGet('sortBy');
        $this->search  = $this->stickyGet('_q');

        if (!$this->jsAction) {
            $this->jsAction = new jsToast('Saved!');
        }

        $this->invoicePage = $this->add(['VirtualPage', 'urlTrigger' => 'invoice']);

        if ($this->hasPayment) {
            $this->paymentPage = $this->add(['VirtualPage', 'urlTrigger' => 'payment']);
        }

        $this->printPage = $this->add(['VirtualPage', 'urlTrigger' => 'print']);

        $this->displayInvoices();
    }

    /**
     * Default View.
     *
     * @throws Exception
     */
    public function displayInvoices()
    {
        $g = $this->add($this->grid);
        $g->ipp = $this->ipp;
        $g->setModel($this->model, $this->tableFields);
        $g->menu->addItem(['Add Invoice', 'icon' => 'plus'])->link($this->invoicePage->getURL());
        $g->addQuickSearch(['reference', 'date', 'due_date'], true);
        $g->quickSearch->useAjax = false;
        $g->quickSearch->initValue = $this->search;

        $g->addActionButton(['icon' => 'edit'], $this->jsIIF($this->invoicePage->getURL()));

        if ($this->hasPayment) {
            $g->addActionButton(['icon' => 'dollar sign'], $this->jsIIF($this->paymentPage->getURL()));
        }

        $g->addActionButton(['icon' => 'print'], $this->jsIIF($this->printPage->getURL('popup')));


        $g->addActionButton(['icon' => 'trash'], function ($jschain, $id) {
            $this->model->load($id)->delete();

            return $jschain->closest('tr')->transition('fade left');
        }, $this->confirmMsg);
    }

    /**
     * Return a typecast field model value if available.
     *
     * @param $field
     *
     * @return mixed
     *
     * @throws \atk4\core\Exception
     * @throws \atk4\data\Exception
     */
    public function get($field)
    {
        if ($this->app->ui_persistence) {
            return $this->app->ui_persistence->typecastSaveField($this->model->getField($field), $this->model->get($field));
        } else {
            return $this->model->get($field);
        }
    }

    /**
     * Callback for setting printing page.
     *
     * @param $fx
     */
    public function setPrintPage($fx)
    {
        if (!($this->getPage('popup') === 'print')) {
            return;
        }

        call_user_func_array($fx, [$this->printPage, $this->modelId]);
    }

    public function getDir($dirName)
    {
        return dirname(__DIR__).'/'.$dirName;
    }

    /**
     * Callback for setting the Invoice page.
     *
     * @param $fx
     */
    public function setInvoicePage($fx)
    {
        if (!($this->getPage() === 'invoice')) {
            return;
        }
        call_user_func_array($fx, [$this->invoicePage, $this->modelId]);
    }

    /**
     * Callback for setting the payment page.
     *
     * @param $fx
     */
    public function setPaymentPage($fx)
    {
        if (!$this->hasPayment || !($this->getPage() === 'payment')) {
            return;
        }

        call_user_func_array($fx, [$this->paymentPage, $this->modelId]);
    }

    /**
     * Return current VirtualPage
     *
     * @return false|int|string
     */
    public function getPage($type = 'callback')
    {
        return array_search($type, $_GET);
    }

    /**
     * Return an IIF js function that will set document.location via javascript.
     *
     * @param $url
     * @param string $arg
     *
     * @return jsExpression
     */
    public function jsIIF($url, $arg = 'id')
    {
        return new jsExpression(
            "(function(url, id){document.location = url + '&{$arg}=' + id})([url],[id])",
            ['url' => $url, 'id' => (new jQuery())->closest('tr')->data('id')]
        );
    }

    /**
     * Manage url for this view.
     *
     * @return string
     */
    public function getURL()
    {
        $params = [];
        $url = strtok($this->url(), '?');
        if ($this->page) {
            $params[$this->grid->paginator->urlTrigger] = $this->page;
        }
        if ($this->sortBy) {
            $params[$this->grid->sortTrigger] = $this->sortBy;
        }
        if ($this->search) {
            $params['_q'] = $this->search;
        }

        if ($query = http_build_query($params)){
            $url  = $url.'?'.$query;
        };

        return $url;
    }
}
