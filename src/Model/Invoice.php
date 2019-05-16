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
    //public $taxRate = 0.1;


    public function init()
    {
        parent::init();
        //todo auto detect expression for footer field

        $this->addField('reference', ['required' => true, 'ui' => ['form' => ['width' => 'six']]]);

        $this->addField('date', ['type' => 'date', 'default' => new \DateTime(), 'required' => true, 'ui' => ['form' => ['width' => 'four']]]);
        $this->addField('due_date', ['type' => 'date', 'default' => (new \DateTime())->add(new \DateInterval('P'.$this->period.'D')),'ui' => ['form' => ['width' => 'four']]]);

        $this->addField('tax_rate', ['type' => 'number', 'default' => 0.1]);

        $this->hasOne('client_id', new Client(), ['required' => true, 'ui' => ['form' => ['width' => 'three']]])->addField('client', 'name');

        $this->hasMany('Payments', new Payment())->addField('paid_total', ['aggregate' => 'sum', 'field' => 'amount', 'type' => 'money','caption' => 'Paid', 'ui' => ['form' => ['width' => 'three']]]);

        $this->hasMany('Items', new InvoiceItems())->addField('sub_total', ['aggregate'=>'sum', 'field'=>'amount', 'type' => 'money']);

        //todo move tax rate into expression
        $this->addExpression('tax', ['expr' => "[sub_total] * [tax_rate]", 'type' => 'money']);
        $this->addExpression('g_total', ['expr' => '[sub_total]+[tax]', 'type' => 'money']);

        $this->addExpression('balance', ['expr' => '[g_total] - [paid_total]', 'type' => 'money']);
    }

}
