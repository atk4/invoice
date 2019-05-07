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

    /**
     * An array of field name that will trigger MultiLine onLineChange event.
     *
     * @var null
     */
    public $eventFields = null;

    /**
     * An array of field name to be set inside each MultiLine row.
     *
     * @var null
     */
    public $itemFields = null;

    public function init()
    {
        parent::init();

        $this->addField('item', ['type' => 'string', 'caption' => 'Item', 'required' => true, 'ui' => ['multiline' => ['width' => 8]]]);
        $this->addField('qty', ['type' => 'number', 'caption' => 'Qty', 'required' => true, 'ui' => ['multiline' => ['width' => 2]]]);
        $this->addField('rate', ['type' => 'money', 'caption' => 'Rate', 'required' => true, 'ui' => ['multiline' => ['width' => 2]]]);
        $this->addExpression('amount', ['expr' => '[qty] * [rate]', 'type' => 'money', 'caption' => 'Amount']);

        $this->hasOne('invoice_id', new Invoice());

        $this->eventFields = ['qty', 'rate'];
        $this->itemFields = ['item', 'qty', 'rate', 'amount'];
    }

    public function getEventFields()
    {
        return $this->eventFields;
    }

    public function getItemFields()
    {
        return $this->itemFields;
    }
}
