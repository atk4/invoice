<?php
/**
 * Invoice model
 */

namespace atk4\invoice\Model;

use atk4\ui\Exception;

class Invoice extends \atk4\data\Model
{
    use \atk4\invoice\Traits\SimpleTax;

    public $table = 'invoice';

    public $title_field = 'reference';

    public $period = '30'; //todo remove to ui builder code.

    public function init()
    {
        parent::init();
        //todo auto detect expression for footer field

        $this->addField('reference', ['required' => true, 'ui' => ['form' => ['width' => 'six']]]);

        $this->addField('date', ['type' => 'date', 'default' => new \DateTime(), 'required' => true, 'ui' => ['form' => ['width' => 'four']]]);
        $this->addField('due_date', ['type' => 'date', 'default' => (new \DateTime())->add(new \DateInterval('P'.$this->period.'D')),'ui' => ['form' => ['width' => 'four']]]);

        $this->addField('vat_rate', ['type' => 'number', 'default' => null]);

        $this->hasOne('client_id', Client::class, ['required' => true, 'ui' => ['form' => ['width' => 'three']]])
            ->addField('client', 'name');

        $this->hasMany('Payments', Payment::class)
            ->addField('paid_total', ['aggregate' => 'sum', 'field' => 'amount', 'type' => 'money','caption' => 'Paid', 'ui' => ['form' => ['width' => 'three']]]);

        $this->hasMany('Items', InvoiceItems::class)
            ->addField('subtotal', ['aggregate'=>'sum', 'field'=>'amount', 'type' => 'money']);

        //todo move tax rate into expression
        $this->addExpression('total_net', ['expr' => '[subtotal]', 'type' => 'money']);
        $this->addExpression('total_vat', ['expr' => "[subtotal] * [vat_rate]", 'type' => 'money']);
        $this->addExpression('total_gross', ['expr' => '[total_net] + [total_vat]', 'type' => 'money']);

        $this->addExpression('balance', ['expr' => '[total_gross] - [paid_total]', 'type' => 'money']);
    }

}
