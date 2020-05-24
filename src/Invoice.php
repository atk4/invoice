<?php

namespace atk4\invoice;

use atk4\data\UserAction;
use atk4\ui\ActionExecutor\UserConfirmation;
use atk4\ui\CRUD;
use atk4\ui\Exception;
use atk4\ui\Grid;
use atk4\ui\jQuery;
use atk4\ui\jsExpression;
use atk4\ui\jsToast;
use atk4\ui\TableColumn\Link;
use atk4\ui\View;
use atk4\ui\VirtualPage;
use atk4\ui\ActionExecutor;

/**
 * Default view for displaying invoice listing.
 *
 */
class Invoice extends View
{
    /** @var VirtualPage for editing Invoice and InvoiceItems */
    public $invoicePage;

    /** @var VirtuaPage for printing Invoice. */
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
    public $grid = null;
    public $ipp = 10;
    public $jsAction = null;

    /** @var string The current invoice id. Will be set as 'id' in Get params. */
    private $invoiceId;

    /** @var string The current Grid page. Will be set as 'p' in Get params. */
    private $page;

    /** @var string The current sorting field. Will be set as 'sortBy' in Get params. */
    private $sortBy;

    /** @var string The current search value for Grid. Will be set as '_q' in Get params. */
    private $search;

    public function init(): void
    {
        parent::init();

        if (!$this->grid) {
            $this->grid = new CRUD([
                'paginator' => ['urlTrigger' => 'p'],
                'sortTrigger' => 'sortBy',
                'useMenuActions' => true,
            ]);
        }

        $this->invoiceId = $this->app->stickyGet('id');
        $this->page = $this->app->stickyGet('p');
        $this->sortBy = $this->app->stickyGet('sortBy');
        $this->search = $this->app->stickyGet('_q');;

        if (!$this->jsAction) {
            $this->jsAction = new jsToast('Saved!');
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
     *
     * @throws Exception
     */
    public function displayInvoices()
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

        $g->addDecorator('ref_no', new Link($this->getURL('invoice') . '&id={$id}'));

        if ($this->hasPayment) {
            $g->addActionMenuItem('View Payments', $this->jsIIF($this->getURL($this->paymentPage->urlTrigger)));
        }

        $g->addActionMenuItem('Print Invoice', $this->jsIIF($this->printPage->getURL('popup')));
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

        call_user_func_array($fx, [$this->printPage, $this->invoiceId]);
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
        call_user_func_array($fx, [$this->invoicePage, $this->invoiceId]);
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

        call_user_func_array($fx, [$this->paymentPage, $this->invoiceId]);
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
    public function getURL($virtualPage = null, bool $includeParam = true): string
    {
        $params = [];
        $url = strtok($this->url(), '?');

        if ($virtualPage) {
            $params[$virtualPage] = $virtualPage === $this->printPage->urlTrigger ? 'popup' : 'callback';
        }

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
     *
     * @return UserAction\Generic
     * @throws \atk4\core\Exception
     * @throws \atk4\data\Exception
     */
    private function setAddAction(): UserAction\Generic
    {
        $ex = new \atk4\ui\ActionExecutor\UserAction(['title' => 'Add Invoice']);
        $ex->onHook('afterExecute', function($x, $r, $id) {
            return new jsExpression('document.location = [url]', ['url' => $this->getUrl($this->invoicePage->urlTrigger) . '&id= ' . $id]);
        });
        $add = $this->model->getAction('add');
        // tell CRUD we will take care of ui response
        $add->modifier = UserAction\Generic::MODIFIER_READ;
        $add->callback = function($m) {
            $m->save();

            return 'Saved! Redirecting to Invoice';
        };
        $add->ui['executor'] = $ex;

        return $add;
    }

    /**
     * Properly wired delete action.
     *
     * @return mixed
     * @throws \atk4\core\Exception
     */
    private function setDeleteAction(): UserAction\Generic
    {
        $ex = new UserConfirmation(['title' => 'Delete Invoice!']);
        $delete = $this->model->getAction('delete');
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
