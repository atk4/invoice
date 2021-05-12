<?php

declare(strict_types = 1);
/**
 * Trait implements action to fully pay invoice.
 *
 * Usage:
 * Add this trait in your Invoice model and call $this->initPayInvoiceAction() method from models init method.
 */
namespace Atk4\Invoice\Traits;

use Atk4\Data\Model\UserAction;

trait PayInvoiceAction
{
    /**
     * Initialize action.
     *
     * Run this method from your models init() method.
     */
    public function initPayInvoiceAction(): void
    {
        $this->addUserAction('pay_invoice', [
            'caption' => 'Pay Full Invoice Amount',
            'description' => 'Pay',
            'enabled' => function($m) {
              return $m->get('balance') > 0;
            },
            'modifier' =>UserAction::MODIFIER_UPDATE,
            'appliesTo' => UserAction::APPLIES_TO_SINGLE_RECORD,
            'args' => [
                // todo $this->refModel('Payments')->getField('method') is not working.
                'method' => ['required' => true, 'default' => 'cash', 'enum' => ['cash', 'debit', 'credit']],
            ],
        ]);
    }

    /**
     * Execute pay invoice action.
     */
    public function pay_invoice(string $method): string
    {
        $paymentRecord = $this->ref('Payments')->createEntity();
        $paymentRecord->save([
            'method' => $method,
            'paid_on' => new \DateTime(),
            'amount' => $this->get('balance'),
            'client_id' => $this->get('client_id'),
        ]);

        return 'Invoice ' . $this->getTitle() . ' is fully paid now';
    }
}
