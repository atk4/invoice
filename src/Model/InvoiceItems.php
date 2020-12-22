<?php

declare(strict_types = 1);
/**
 * Invoice Items
 */
namespace Atk4\Invoice\Model;

use Atk4\Data\Model;

class InvoiceItems extends Model
{
    public $table = 'invoice_line';
    public $caption = 'Invoice Line';

    protected function init(): void
    {
        parent::init();

        $this->addField('item', [
            'type' => 'string',
            'caption' => 'Item',
            'required' => true,
            'ui' => ['multiline' => ['sui-table-cell' => ['width' => 8]]]
        ]);
        $this->addField('qty', [
            'type' => 'number',
            'caption' => 'Qty',
            'required' => true,
            'default' => 1,
            'ui' => ['multiline' => ['sui-table-cell' => ['width' => 2]]]
        ]);
        $this->addField('price', [
            'type' => 'float',
            'caption' => 'Price',
            'required' => true,
            'default' => 1,
            'ui' => ['multiline' => ['sui-table-cell' => ['width' => 2]]]
        ]);
        $this->addExpression('amount', [
            'expr' => '[qty] * [price]',
            'type' => 'money',
            'caption' => 'Amount'
        ]);

        $this->hasOne('invoice_id', Invoice::class);
    }
}
