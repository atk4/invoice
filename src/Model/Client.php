<?php

declare(strict_types = 1);
/**
 * Client model
 */
namespace atk4\invoice\Model;

use atk4\data\Model;
use atk4\invoice\Traits\SendStatementAction;

class Client extends Model
{
    use SendStatementAction;

    public $table = 'client';
    public $caption = 'Client';

    protected function init(): void
    {
        parent::init();

        $this->addField('name', ['required' => true]);
        $this->addField('email');

        $this->hasMany('Invoices', Invoice::class);
        $this->hasMany('Payments', Payment::class);

        // actions
        $this->initSendStatementAction();
    }
}
