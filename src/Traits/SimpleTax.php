<?php
/**
 * Apply some method for calculating total.
 * InvoiceMgr will call jsUpdateField when field from invoice line are change.
 * It is possible to use another traits, just make sure all your field value update
 * goes via jsUpdateFields($rows, $f) method and that your traits implements this method.
 *
 */
namespace atk4\invoice\Traits;

trait SimpleTax
{
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

        if (!$this->getElement('subtotal') || !$this->getElement('tax') || !$this->getElement('total')) {
            return;
        }

        $s_total = $this->getSubTotal($rows);
        $tax = $this->getTotalTax($s_total);
        $resp = [];
        if ($field = $f->getField('subtotal')){
            $resp[] = $field->jsInput()->val(number_format($s_total, 2));
        }
        if ($field =  $f->getField('tax')) {
            $resp[] = $field->jsInput()->val(number_format($tax, 2));
        }
        if ($field = $f->getField('total')) {
            $resp[] = $field->jsInput()->val(number_format($s_total + $tax, 2));
        }

        return $resp;
    }

    /**
     * Return total of each item in a row.
     *
     * @param $itemRows
     *
     * @return float|int
     */
    protected function getSubTotal($itemRows)
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
    protected function getTotalTax($total)
    {
        return $total * $this->get('tax_rate');
    }
}
