<?php
/**
 * Paiment model
 */

namespace atk4\invoice\Model;

use atk4\data\Model;

class Paiement extends Model
{
    public $table = 'paiement';
    public $title_field = 'method';

    public function init()
    {
        parent::init();

        $this->addField('method', ['required' => true, 'default' => 'cash', 'enum' => ['cash', 'debit', 'credit']]);
        $this->addField('paid_on', ['type' => 'date']);
        $this->addField('amount', ['type' => 'money']);
        $this->addField('details');

        $this->hasOne('invoice_id', new Invoice());
        $this->hasOne('client_id', new Client());
    }
}
