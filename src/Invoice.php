<?php

namespace atk4\invoice;

use atk4\data\UserAction;
use atk4\ui\ActionExecutor\UserConfirmation;
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
            $this->grid = new Grid(['paginator' => ['urlTrigger' => 'p'], 'sortTrigger' => 'sortBy']);
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
        $g = $this->add($this->grid);
        $g->ipp = $this->ipp;
        $g->setModel($this->model, $this->tableFields);
        $g->menu->addItem(['Add Invoice', 'icon' => 'plus'], $this->getAddInvoiceAction());
        $g->addQuickSearch(['ref_no', 'date', 'due_date'], true);
        $g->quickSearch->useAjax = false;
        $g->quickSearch->initValue = $this->search;

        $this->page = $g->paginator->getCurrentPage();

        $g->addDecorator('ref_no', new Link($this->getURL('invoice') . '&id={$id}'));

        $g->addActionMenuItem($this->getDeleteAction());
        // setup other actions
        foreach ($this->model->getActions() as $action_name => $action) {
            if (!in_array($action_name, ['edit', 'delete']) && $action->enabled && $action->scope == UserAction\Generic::SINGLE_RECORD) {
                // have single record action to reload grid after execution.
                $ex = new ActionExecutor\UserAction();
                $ex->onHook('afterExecute', function($ex, $return) use ($g) {
                   return [
                       new jsToast($return),
                       $g->container->jsReload()
                   ];
                });
                $action->ui['executor'] = $ex;

                $g->addActionMenuItem($action);
            }
        }

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
     * Always unload model for this action,
     * this allow us to call it from anywhere.
     *
     * @return UserAction\Generic
     * @throws \atk4\core\Exception
     * @throws \atk4\data\Exception
     */
    public function getAddInvoiceAction()
    {
        $m = clone($this->model);
        $m->unload();
        $ex = new \atk4\ui\ActionExecutor\UserAction(['title' => 'Add Invoice']);
        $ex->onHook('afterExecute', function($x, $m) {
            return [
                new jsToast(['message' => 'Saved! Redirecting to invoice page.', 'duration' => 0]),
                new jsExpression('document.location = [url]', ['url' => $this->getUrl($this->invoicePage->urlTrigger) . '&id= ' . $m->get($this->model->id_field)])
            ];
        });
        $add = $m->getAction('add_invoice');
        $add->ui['executor'] = $ex;

        return $add;
    }

    /**
     * Properly wired delete action when in when using grid.
     *
     * @return mixed
     * @throws \atk4\core\Exception
     */
    public function getDeleteAction()
    {
        $ex = new UserConfirmation(['title' => 'Delete Invoice!']);
        $ex->onHook('afterExecute', function($x, $return){
            $msg = 'Invoice: ' . $return['title'] . ' has been delete.';
            $id = $return['id'];
            return [
                new jsToast($msg),
                (new jQuery('tr[data-id="' . $id .'"]'))->closest('tr')->transition('fade left')
            ];
        });
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
                $id = $m->get($m->id_field);
                $title = $m->getTitle();
                $m->delete();

                return ['id' => $id, 'title' => $title];
            }
        };

        return $delete;
    }
}
