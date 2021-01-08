<?php

declare(strict_types = 1);
/**
 * Client model
 */
namespace Atk4\Invoice\Model;

use Atk4\Data\Model;
use Atk4\Invoice\Traits\SendStatementAction;

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

        $this->hasMany('Invoices', ['model' => [Invoice::class]]);
        $this->hasMany('Payments', ['model' => [Payment::class]]);

        // actions
        $this->initSendStatementAction();
    }
}
