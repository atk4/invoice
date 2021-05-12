<?php

declare(strict_types = 1);
/**
 * Apply some method for calculating total.
 * InvoiceMgr will call jsUpdateField when field from invoice line are change.
 * It is possible to use another traits, just make sure all your field value update
 * goes via jsUpdateFields($rows, $f) method and that your traits implements this method.
 *
 */
namespace Atk4\Invoice\Traits;

use Atk4\Ui\Form;

trait SimpleTax
{
    /**
     * Update field value in form via javascript when onChange event is fire on MultiLine.
     */
    public function jsUpdateFields(array $rows, Form $f): ?array
    {
        if (!$this->getField('total_net') || !$this->getField('total_vat') || !$this->getField('total_gross')) {
            return null;
        }

        // calculate totals
        $total_net = $this->getSubTotal($rows);
        $total_vat = $this->getTotalTax($total_net, (float) $f->model->get('vat_rate'));

        // set total field values
        $resp = [];
        if ($field = $f->getControl('total_net')){
            $resp[] = $field->jsInput()->val(number_format($total_net, 2));
        }
        if ($field =  $f->getControl('total_vat')) {
            $resp[] = $field->jsInput()->val(number_format($total_vat, 2));
        }
        if ($field = $f->getControl('total_gross')) {
            $resp[] = $field->jsInput()->val(number_format($total_net + $total_vat, 2));
        }

        return $resp;
    }

    /**
     * Return total of each item in a row.
     */
    protected function getSubTotal(array $itemRows): float
    {
        $s_total = 0;
        foreach ($itemRows as $row => $cols) {
            $price = $cols['price'] ?? 0;
            $qty = $cols['qty'] ?? 0;
            $s_total = $s_total + ($qty * $price);
        }
        $this->total = $s_total;

        return $s_total;
    }

    /**
     * Return tax amount from total amount.
     */
    protected function getTotalTax(float $total, float $rate): float
    {
        return round($total * $rate / 100, 2);
    }
}
