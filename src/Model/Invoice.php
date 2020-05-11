<?php
/**
 * Invoice model
 */
namespace atk4\invoice\Model;

use atk4\data\Model;
use atk4\ui\ActionExecutor\UserAction;
use atk4\ui\Exception;
use atk4\invoice\Traits\SimpleTax;
use atk4\invoice\Traits\PayInvoiceAction;

class Invoice extends Model
{
    use SimpleTax;
    use PayInvoiceAction;

    public $table = 'invoice';
    public $caption = 'Invoice';
    public $title_field = 'ref_no';

    /* @var int How long is invoice due period in days */
    public $period = '30';

    public function init(): void
    {
        parent::init();

        $this->hasOne('client_id', Client::class, ['required' => true, 'ui' => ['form' => ['width' => 'three']]])
            ->withTitle();

        $this->addField('ref_no', ['required' => true, 'ui' => ['form' => ['width' => 'six']]]);

        $this->addField('date', ['type' => 'date', 'default' => new \DateTime(), 'required' => true, 'ui' => ['form' => ['width' => 'four']]]);
        $this->addField('due_date', ['type' => 'date', 'default' => (new \DateTime())->add(new \DateInterval('P'.$this->period.'D')),'ui' => ['form' => ['width' => 'four']]]);

        $this->addField('vat_rate', ['type' => 'number', 'default' => null]);

        $this->hasMany('Items', InvoiceItems::class)
            ->addField('subtotal', ['aggregate'=>'sum', 'field'=>'amount', 'type' => 'money']);

        $this->hasMany('Payments', Payment::class)
            ->addField('total_paid', ['aggregate' => 'sum', 'field' => 'amount', 'type' => 'money', 'caption' => 'Paid', 'ui' => ['form' => ['width' => 'three']]]);

        $this->addExpression('total_net', ['expr' => '[subtotal]', 'type' => 'money']);
        $this->addExpression('total_vat', ['expr' => "round([total_net] * [vat_rate]/100,2)", 'type' => 'money']);
        $this->addExpression('total_gross', ['expr' => '[total_net] + [total_vat]', 'type' => 'money']);
        $this->addExpression('balance', ['expr' => '[total_gross] - [total_paid]', 'type' => 'money']);

        $this->setOrder('date');

        // actions
        $this->initPayInvoiceAction();
    }
}
