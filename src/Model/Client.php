<?php
/**
 * Client table
 */

namespace atk4\invoice\Model;

use atk4\data\Model;

class Client extends Model
{
    public $table = 'client';

    public function init()
    {
        parent::init();

        $this->addField('name');
    }
}