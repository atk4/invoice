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
            'scope' => Generic::SINGLE_RECORD,
            'args' => [
                'method' => $this->refModel('Payments')->getField('method'), // use actual model field settings :)
            ],
            'confirmation' => 'Do you really want to fully pay this invoice?',
        ]);
    }

    /**
     * Execute pay invoice action.
     *
     * @return string
     */
    public function pay_invoice($method)
    {
        if (!$this['balance'] || $this['balance'] < 0 ) {
            return 'Invoice '.$this->getTitle().' is already fully paid.';
        }

        $m = $this->ref('Payments')->save([
            'method' => $method,
            'paid_on' => new \DateTime(),
            'amount' => $this['balance'],
            'client_id' => $this['client_id'],
        ]);
        
        return 'Invoice '.$this->getTitle().' is fully paid now';
    }
}
