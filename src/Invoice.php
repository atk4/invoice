<?php

declare(strict_types = 1);

namespace Atk4\Invoice;

use Atk4\Data\Model\UserAction;
use Atk4\Ui\Crud;
use Atk4\Ui\Grid;
use Atk4\Ui\Jquery;
use Atk4\Ui\JsExpression;
use Atk4\Ui\JsToast;
use Atk4\Ui\Table\Column\Link;
use Atk4\Ui\UserAction\BasicExecutor;
use Atk4\Ui\UserAction\ConfirmationExecutor;
use Atk4\Ui\UserAction\ModalExecutor;
use Atk4\Ui\View;
use Atk4\Ui\VirtualPage;

/**
 * Default view for displaying invoice listing.
 *
 */
class Invoice extends View
{
    /** @var VirtualPage for editing Invoice and InvoiceItems */
    public $invoicePage;

    /** @var VirtualPage for printing Invoice. */
    public $printPage;

    /** @var VirtualPage for displaying and editing Payments  */
    public $paymentPage;

    /** @var bool Whether or not Payment page is to be create. */
    public $hasPayment = false;

    /** @var array  A list of Invoice fields to display in table. */
    public $tableFields;

    /** @var string Default delete msg. */
    public $confirmMsg = 'Are you sure?';

    /** @var integer|null Current Invoice id */
    public $currentId = null;

    /** @var Grid default seed*/
    public $grid;
    public $ipp = 10;
    public $jsAction;

    /** @var string The current invoice id. Will be set as 'id' in Get params. */
    private $invoiceId;

    /** @var string The current Grid page. Will be set as 'p' in Get params. */
    private $page;

    /** @var string The current sorting field. Will be set as 'sortBy' in Get params. */
    private $sortBy;

    /** @var string The current search value for Grid. Will be set as '_q' in Get params. */
    private $search;

    protected function init(): void
    {
        parent::init();

        if (!$this->grid) {
            $this->grid = new Crud([
                'paginator' => ['urlTrigger' => 'p'],
                'sortTrigger' => 'sortBy',
                'useMenuActions' => true,
            ]);
        }

        $this->invoiceId = $this->getApp()->stickyGet('id');
        $this->page = $this->getApp()->stickyGet('p');
        $this->sortBy = $this->getApp()->stickyGet('sortBy');
        $this->search = $this->getApp()->stickyGet('_q');

        if (!$this->jsAction) {
            $this->jsAction = new JsToast('Saved!');
        }

        $this->invoicePage = VirtualPage::addTo($this, ['urlTrigger' => 'invoice']);

        if ($this->hasPayment) {
            $this->paymentPage = VirtualPage::addTo($this, ['urlTrigger' => 'payment']);
        }

        $this->printPage = VirtualPage::addTo($this, ['urlTrigger' => 'print']);

        $this->displayInvoices();
    }

    /**
     * Default View.
     */
    public function displayInvoices(): void
    {
        $this->setAddAction();
        $this->setDeleteAction();

        $g = $this->add($this->grid);
        $g->ipp = $this->ipp;
        $g->setModel($this->model, $this->tableFields);
        $g->addQuickSearch(['ref_no', 'date', 'due_date'], true);
        $g->quickSearch->useAjax = false;
        $g->quickSearch->initValue = $this->search;

        $this->page = $g->paginator->getCurrentPage();

        $g->addDecorator('ref_no', new Link($this->invoicePage->getUrl() . '&id={$id}'));

        if ($this->hasPayment) {
            $g->addActionMenuItem('View Payments', $this->jsIIF($this->paymentPage->getUrl()));
        }

        $g->addActionMenuItem('Print Invoice', $this->jsIIF($this->printPage->getURL('popup')));
    }

    /**
     * Return a typecast field model value if available.
     */
    public function get(string $fieldName)
    {
        if ($this->getApp()->ui_persistence) {
            return $this->getApp()->ui_persistence->typecastSaveField($this->model->getField($fieldName), $this->model->get($fieldName));
        } else {
            return $this->model->get($fieldName);
        }
    }

    /**
     * Callback for setting printing page.
     */
    public function setPrintPage(callable $fx): void
    {
        if (!($this->getPage('popup') === 'print')) {
            return;
        }

        $this->printPage->set($fx, [$this->invoiceId]);
    }

    public function getDir($dirName)
    {
        return dirname(__DIR__).'/'.$dirName;
    }

    /**
     * Callback for setting the Invoice page.
     */
    public function setInvoicePage(callable $fx): void
    {
        if (!($this->getPage() === 'invoice')) {
            return;
        }
        $this->invoicePage->set($fx, [$this->invoiceId]);
    }

    /**
     * Callback for setting the payment page.
     */
    public function setPaymentPage(callable $fx): void
    {
        if (!$this->hasPayment || !($this->getPage() === 'payment')) {
            return;
        }

        $this->paymentPage->set($fx, [$this->invoiceId]);
    }

    /**
     * Return current VirtualPage
     */
    public function getPage(string $type = 'callback'): string
    {
        return array_search($type, $_GET) ? array_search($type, $_GET) : '';
    }

    /**
     * Return an IIF js function that will set document.location via javascript.
     */
    public function jsIIF(string $url, string $arg = 'id'): JsExpression
    {
        return new JsExpression(
            "(function(url, id){document.location = url + '&{$arg}=' + id})([url],[id])",
            ['url' => $url, 'id' => (new Jquery())->closest('tr')->data('id')]
        );
    }

    /**
     * Manage url for this view.
     */
    public function getURL(bool $includeParam = true): string
    {
        $params = [];
        $url = strtok($this->url(), '?');

        if ($includeParam) {

            // check for paginator page.
            if ($this->sortBy) {
                $params[$this->grid->sortTrigger] = $this->sortBy;
            }
            if ($this->search) {
                $params['_q'] = $this->search;
            }

            if ($this->invoiceId) {
                $params['id'] = $this->invoiceId;
            }

            if ($this->page) {
                $params[$this->grid->paginator->urlTrigger] = $this->page;
            }
        }

        if ($query = http_build_query($params)){
            $url = $url.'?'.$query;
        };

        return $url;
    }

    /**
     * Properly wired add Invoice action.
     */
    private function setAddAction(): UserAction
    {
        $ex = new ModalExecutor(['title' => 'Add Invoice']);
        $ex->onHook(BasicExecutor::HOOK_AFTER_EXECUTE, function($x, $r, $id) {

            return new JsExpression('document.location = [url]', ['url' => $this->getUrl($this->invoicePage->urlTrigger) . '&id= ' . $id]);
        });
        $add = $this->model->getUserAction('add');
        // tell Crud we will take care of ui response
        $add->modifier = UserAction::MODIFIER_READ;
        $add->callback = function($m) {
            $m->save();

            return 'Saved! Redirecting to Invoice';
        };
        $add->ui['executor'] = $ex;

        return $add;
    }

    /**
     * Properly wired delete action.
     */
    private function setDeleteAction(): UserAction
    {
        $ex = new ConfirmationExecutor(['title' => 'Delete Invoice!']);
        $delete = $this->model->getUserAction('delete');
        $delete->ui['executor'] = $ex;
        $delete->confirmation = function ($a) {
            $m = $a->getModel();
            $title = $m->getField($m->title_field)->getCaption();
            $value = $m->getTitle();
            return "Delete invoice using {$title}: <b>{$value}</b>?";
        };

        $delete->ui['confirm'] = null;
        $delete->callback =  function ($m) {
            if ($m->loaded()) {
                $title = $m->getTitle();
                $m->delete();

                return 'Invoice: ' . $title . ' has been delete.';
            }
        };

        return $delete;
    }
}
