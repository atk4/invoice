<?php
/**
 * Invoice model
 */

namespace atk4\invoice\Model;

use atk4\ui\Exception;

class Invoice extends \atk4\data\Model
{
    public $table = 'invoice';
    public $tableCaption = 'Invoice';

    public $period = '30';
    public $taxRate = 0.1;


    public $items = null;
    public $itemsRef = null;
    public $itemsRefId = null;

    public $headerFields = null;
    public $footerFields = null;


    public function init()
    {
        parent::init();

        if (!$this->items) {
            throw new Exception('Items model need to be set.');
        }

        $this->addField('reference', ['required' => true, 'ui' => ['form' => ['width' => 'six']]]);

        $this->addField('date', ['type' => 'date', 'default' => new \DateTime(), 'required' => true, 'ui' => ['form' => ['width' => 'four']]]);
        $this->addField('due_date', ['type' => 'date', 'default' => (new \DateTime())->add(new \DateInterval('P'.$this->period.'D')),'ui' => ['form' => ['width' => 'four']]]);

        $this->hasOne('bill_to_id', new \atk4\invoice\Model\Client(), ['required' => true, 'ui' => ['form' => ['width' => 'three']]]);

        $this->hasMany($this->itemsRef, $this->items)->addField('sub_total', ['aggregate'=>'sum', 'field'=>'amount', 'type' => 'money']);

        $this->addExpression('tax', ['expr' => "[sub_total] * {$this->taxRate}", 'type' => 'money']);
        $this->addExpression('g_total', ['expr' => '[sub_total]+[tax]', 'type' => 'money']);

        $this->hasMany('Paiements', new Paiement())->addField('paid_total', ['aggregate' => 'sum', 'field' => 'amount', 'type' => 'money', 'ui' => ['form' => ['width' => 'three']]]);

        $this->addExpression('balance', ['expr' => '[g_total] - [paid_total]', 'type' => 'money']);

        $this->headerFields = ['reference', 'date', 'due_date', 'bill_to_id', 'paid_total'];
        $this->footerFields = ['sub_total', 'tax', 'g_total'];
    }

    /**
     * Return an array of field name that need to be place above the MultiLine.
     *
     * @return array
     */
    public function getHeaderFields()
    {
        return $this->headerFields;
    }

    /**
     * Return an array of field name that need to be set in each item row of the MultiLine field.
     *
     * @return mixed
     */
    public function getItemFields()
    {
        return $this->items->getItemFields();
    }

    /**
     * Return an array of field name that need to be place below MultiLine field.
     * @return array
     */
    public function getFooterFields()
    {
        return $this->footerFields;
    }

    /**
     * Return the fields that will trigger Multiline onChange event.
     *
     * @return mixed
     * @throws Exception
     */
    public function getEventFields()
    {

        return $this->items->getEventFields();
    }

    /**
     * Return total of each item in a row.
     *
     * @param $itemRows
     *
     * @return float|int
     */
    public function getSubTotal($itemRows)
    {
        $s_total = 0;
        foreach ($itemRows as $row => $cols) {
            $qty = array_column($cols, 'qty')[0];
            $rate = array_column($cols, 'rate')[0];
            $s_total = $s_total + ($qty * $rate);
        }
        $this->total = $s_total;

        return $s_total;
    }

    /**
     * Return tax amount from total amount.
     *
     * @param $total
     *
     * @return float|int
     */
    public function getTotalTax($total)
    {
        return $total * $this->taxRate;
    }

    /**
     * Update field value in form via javascript when onChange event is fire on MultiLine.
     *
     * @param $rows  The items rows with new value.
     * @param $f     The form where multiline is set.
     *
     * @return array
     */
    public function jsUpdateFields($rows, $f)
    {
        $s_total = $this->getSubTotal($rows);
        $tax = $this->getTotalTax($s_total);
        return [
            $f->getField('sub_total')->jsInput()->val(number_format($s_total, 2)),
            $f->getField('tax')->jsInput()->val(number_format($tax, 2)),
            $f->getField('g_total')->jsInput()->val(number_format($s_total + $tax, 2))
        ];
    }
}
