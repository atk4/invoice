<?php
/**
 * Trait implements statement generating and sending action for client model.
 *
 * Usage:
 * Add this trait in your Client model and call $this->initSendStatementAction() method from models init method.
 */
namespace atk4\invoice\Traits;

use atk4\data\UserAction\Generic;
use atk4\ui\CardTable;
use atk4\ui\Table;
use atk4\ui\View;

trait SendStatementAction
{
    /**
     * Initialize action.
     *
     * Run this method from your models init() method.
     */
    public function initSendStatementAction()
    {
        $this->addAction('send_statement', [
            'scope' => Generic::SINGLE_RECORD,
            'args' => [
                'subject' => ['type' => 'string', 'required' => true],
                'message' => ['type' => 'text'],
            ],
            'preview' => 'get_statement_preview',
        ]);
    }

    /**
     * Statement preview view.
     *
     * @return string
     */
    public function get_statement_preview() {
        $v = new View();

        // header
        $ct = CardTable::addTo($v);
        $ct->header = false;
        $ct->init();
        $ct->setModel($this, ['name', 'email']);

        // invoice table
        $t = Table::addTo($v);
        $t->init();
        $t->setModel($this->ref('Invoices'), ['date','ref_no','vat_rate','total_net','total_vat','total_gross','total_paid','balance']);
        $t->addTotals([
            'vat_rate'=>'Total:',
            'total_net'=>['sum'],
            'total_vat'=>['sum'],
            'total_gross'=>['sum'],
            'total_paid'=>['sum'],
            'balance'=>['sum'],
        ]);

        return $v->render();
    }

    /**
     * Execute send statement action.
     *
     * @return string
     */
    public function send_statement() {
        /*
        if (!isset($this->app->mailer)) {
            throw new ValidationException('ouch');
        }
        */
        $email = $this->get_statement_preview();
        
        // @todo - implement actual email sending here. Maybe we can use atk4/outbox for that?
        
        return 'Statement sent to '.$this['email'];
    }
}
