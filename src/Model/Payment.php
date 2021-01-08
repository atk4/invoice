<?php

declare(strict_types = 1);
/**
 * Payment model
 */
namespace Atk4\Invoice\Model;

use Atk4\Data\Model;

class Payment extends Model
{
    public $table = 'payment';
    public $caption = 'Payment';
    public $title_field = 'method';

    protected function init(): void
    {
        parent::init();

        $this->addField('method', ['required' => true, 'default' => 'cash', 'enum' => ['cash', 'debit', 'credit']]);
        $this->addField('paid_on', ['type' => 'date']);
        $this->addField('amount', ['type' => 'money']);
        $this->addField('details');

        $this->setOrder('paid_on');

        $this->hasOne('invoice_id', ['model' => [Invoice::class]]);
        $this->hasOne('client_id', ['model' => [Client::class], 'caption' => 'Client', 'ui' => ['visible' => false]]);
    }
}
