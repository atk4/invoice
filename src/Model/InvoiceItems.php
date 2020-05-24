<?php
/**
 * Invoice Items
 */
namespace atk4\invoice\Model;

use atk4\data\Model;

class InvoiceItems extends Model
{
    public $table = 'invoice_line';
    public $caption = 'Invoice Line';

    public function init(): void
    {
        parent::init();

        $this->addField('item', ['type' => 'string', 'caption' => 'Item', 'required' => true, 'ui' => ['multiline' => ['width' => 8]]]);
        $this->addField('qty', ['type' => 'number', 'caption' => 'Qty', 'required' => true, 'ui' => ['multiline' => ['width' => 2]]]);
        $this->addField('price', ['type' => 'money', 'caption' => 'Price', 'required' => true, 'ui' => ['multiline' => ['width' => 2]]]);
        $this->addExpression('amount', ['expr' => '[qty] * [price]', 'type' => 'money', 'caption' => 'Amount']);

        $this->hasOne('invoice_id', Invoice::class);
    }
}
