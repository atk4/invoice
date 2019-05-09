<?php
/**
 * Invoice
 *
 */

namespace atk4\invoice;

use atk4\ui\Exception;
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

    private $modelId;

    public function init()
    {
        parent::init();

        $this->app->useSuiVue();

        $this->modelId = $this->stickyGet('id');


        if (!$this->jsAction) {
            $this->jsAction = new jsToast('Saved!');
        }

        $this->invoicePage = $this->add(['VirtualPage', 'urlTrigger' => 'invoice']);

        if ($this->hasPayment) {
            $this->paymentPage = $this->add(['VirtualPage', 'urlTrigger' => 'payment']);
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
        $g->menu->addItem(['Add Invoice', 'icon' => 'plus'])->link($this->invoicePage->getURL());
        $g->addQuickSearch(['reference', 'date', 'due_date'], true);

        $g->addAction(['icon' => 'edit'], $this->jsIIF($this->invoicePage->getURL()));

        if ($this->hasPayment) {
            $g->addAction(['icon' => 'dollar sign'], $this->jsIIF($this->paymentPage->getURL()));
        }

        $g->addAction(['icon' => 'trash'], function ($jschain, $id) {
            $this->model->load($id)->delete();

            return $jschain->closest('tr')->transition('fade left');
        }, $this->confirmMsg);
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
    public function getPage()
    {
        return array_search('callback', $_GET);
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
     * Redefine url function.
     * When isClean is true, will remove all query parameter.
     *
     * @param array $page
     * @param bool $isClean
     *
     * @return string
     */
//    public function url($page = [], $isClean = false)
//    {
//        $url = parent::url($page);
//        if ($isClean) {
//            $url = strtok($url, '?');
//        }
//        return $url;
//    }
}
