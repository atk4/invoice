<?php
/**
 * Paiment model
 */

namespace atk4\invoice\Model;

use atk4\data\Model;

class Payment extends Model
{
    public $table = 'payment';
    public $title_field = 'method';

    public function init()
    {
        parent::init();

        $this->addField('method', ['required' => true, 'default' => 'cash', 'enum' => ['cash', 'debit', 'credit']]);
        $this->addField('paid_on', ['type' => 'date']);
        $this->addField('amount', ['type' => 'money']);
        $this->addField('details');

        $this->hasOne('invoice_id', Invoice::class);
        $this->hasOne('client_id', Client::class, ['ui' => ['is_visible' => false]]);
    }
}
