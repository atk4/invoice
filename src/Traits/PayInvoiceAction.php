<?php
/**
 * Trait implements action to fully pay invoice.
 *
 * Usage:
 * Add this trait in your Invoice model and call $this->initPayInvoiceAction() method from models init method.
 */
namespace atk4\invoice\Traits;

use atk4\data\UserAction\Generic;

trait PayInvoiceAction
{
    /**
     * Initialize action.
     *
     * Run this method from your models init() method.
     */
    public function initPayInvoiceAction()
    {
        $this->addAction('pay_invoice', [
            'caption' => 'Pay Full Invoice Amount',
            'description' => 'Pay',
            'enabled' => function() {
              return $this->get('balance') > 0;
            },
            'modifier' => Generic::MODIFIER_UPDATE,
            'scope' => Generic::SINGLE_RECORD,
            'args' => [
                // todo $this->refModel('Payments')->getField('method') is not working.
                'method' => ['required' => true, 'default' => 'cash', 'enum' => ['cash', 'debit', 'credit']],
            ],
        ]);
    }

    /**
     * Execute pay invoice action.
     *
     * @return string
     */
    public function pay_invoice($method)
    {
        $this->ref('Payments')->save([
            'method' => $method,
            'paid_on' => new \DateTime(),
            'amount' => $this['balance'],
            'client_id' => $this['client_id'],
        ]);
        
        return 'Invoice '.$this->getTitle().' is fully paid now';
    }
}
