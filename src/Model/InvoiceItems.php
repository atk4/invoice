<?php
/**
 * Invoice Items
 */

namespace atk4\invoice\Model;

use atk4\data\Model;

class InvoiceItems extends Model
{
    public $table = 'invoice_line';

    public function init()
    {
        parent::init();

        $this->addField('item', ['type' => 'string', 'caption' => 'Item', 'required' => true, 'ui' => ['multiline' => ['width' => 8]]]);
        $this->addField('qty', ['type' => 'number', 'caption' => 'Qty', 'required' => true, 'ui' => ['multiline' => ['width' => 2]]]);
        $this->addField('rate', ['type' => 'money', 'caption' => 'Rate', 'required' => true, 'ui' => ['multiline' => ['width' => 2]]]);
        $this->addExpression('amount', ['expr' => '[qty] * [rate]', 'type' => 'money', 'caption' => 'Amount']);

        $this->hasOne('invoice_id', Invoice::class);
    }

}
