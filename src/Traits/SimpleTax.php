<?php
/**
 * Apply some method for calculating total.
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

        if (!$this->getElement('sub_total') || !$this->getElement('tax') || !$this->getElement('g_total')) {
            return;
        }

        $s_total = $this->getSubTotal($rows);
        $tax = $this->getTotalTax($s_total);
        return [
            $f->getField('sub_total')->jsInput()->val(number_format($s_total, 2)),
            $f->getField('tax')->jsInput()->val(number_format($tax, 2)),
            $f->getField('g_total')->jsInput()->val(number_format($s_total + $tax, 2))
        ];
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
        return $total * $this->taxRate;
    }
}