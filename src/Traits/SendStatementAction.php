<?php

declare(strict_types = 1);
/**
 * Trait implements statement generating and sending action for client model.
 *
 * Usage:
 * Add this trait in your Client model and call $this->initSendStatementAction() method from models init method.
 */
namespace atk4\invoice\Traits;

use atk4\data\Model\UserAction;
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
    public function initSendStatementAction(): void
    {
        $this->addUserAction('send_statement', [
            'appliesTo' => UserAction::APPLIES_TO_SINGLE_RECORD,
            'modifier' => UserAction::MODIFIER_READ,
            'args' => [
                'subject' => ['type' => 'string', 'required' => true],
                'message' => ['type' => 'text'],
            ],
            'preview' => 'get_statement_preview',
        ]);
    }

    /**
     * Statement preview view.
     */
    public function get_statement_preview(): string
    {
        $v = new View();

        // header
        $ct = CardTable::addTo($v);
        $ct->header = false;
        $ct->invokeInit();
        $ct->setModel($this, ['name', 'email']);

        // invoice table
        $t = Table::addTo($v);
        $t->invokeInit();
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
     */
    public function send_statement(): string
    {
        /*
        if (!isset($this->getApp()->mailer)) {
            throw new ValidationException('ouch');
        }
        */
        $email = $this->get_statement_preview();
        
        // @todo - implement actual email sending here. Maybe we can use atk4/outbox for that?
        
        return 'Statement sent to '. $this['email'];
    }
}
