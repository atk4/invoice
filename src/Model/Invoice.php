<?php

declare(strict_types = 1);
/**
 * Invoice model
 */
namespace atk4\invoice\Model;

use atk4\data\Model;
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

        $this->hasOne('client_id', [Client::class, 'required' => true, 'caption' => 'Client'])
            ->withTitle();

        $this->addField('ref_no', ['required' => true]);

        $this->addField('date', ['type' => 'date', 'default' => new \DateTime(), 'required' => true]);
        $this->addField('due_date', ['type' => 'date', 'default' => (new \DateTime())->add(new \DateInterval('P'.$this->period.'D'))]);

        $this->addField('vat_rate', ['type' => 'number', 'default' => null, 'required' => true]);

        $this->hasMany('Items', InvoiceItems::class)
            ->addField('subtotal', ['aggregate'=>'sum', 'field'=>'amount', 'type' => 'money']);

        $this->hasMany('Payments', Payment::class)
            ->addField('total_paid', ['aggregate' => 'sum', 'field' => 'amount', 'type' => 'money', 'caption' => 'Paid']);

        $this->addExpression('total_net', ['expr' => '[subtotal]', 'type' => 'money']);
        $this->addExpression('total_vat', ['expr' => "round([total_net] * [vat_rate]/100,2)", 'type' => 'money']);
        $this->addExpression('total_gross', ['expr' => '[total_net] + [total_vat]', 'type' => 'money']);
        $this->addExpression('balance', ['expr' => '[total_gross] - [total_paid]', 'type' => 'money']);

        $this->setOrder('date');

        $this->getUserAction('add')->fields = ['ref_no', 'client_id', 'date', 'vat_rate'];

        // actions
        $this->initPayInvoiceAction();
    }
}
