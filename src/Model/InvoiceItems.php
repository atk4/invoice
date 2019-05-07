<?php
/**
 * Invoice Items
 */

namespace atk4\invoice\Model;

use atk4\data\Model;

class InvoiceItems extends Model
{
    public $table = 'invoice_line';
    public $tableCaption = 'Invoice items';

    //public $eventFields = ['qty', 'rate'];

    public function init()
    {
        parent::init();

        $this->addField('item', ['type' => 'string', 'caption' => 'Item', 'required' => true, 'ui' => ['multiline' => ['width' => 8]]]);
        $this->addField('qty', ['type' => 'number', 'caption' => 'Qty', 'required' => true, 'ui' => ['multiline' => ['width' => 2]]]);
        $this->addField('rate', ['type' => 'money', 'caption' => 'Rate', 'required' => true, 'ui' => ['multiline' => ['width' => 2]]]);
        $this->addExpression('amount', ['expr' => '[qty] * [rate]', 'type' => 'money', 'caption' => 'Amount']);

        $this->hasOne('invoice_id', new Invoice());
    }

    /**
     *  Return Fields that trigger multiline onChange event.
     *
     */
    public function getEventFields()
    {
        return ['qty', 'rate'];
    }

    public function getItemFields()
    {
        return ['item', 'qty', 'rate', 'amount'];
    }
}
