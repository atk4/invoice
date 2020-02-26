<?php
/**
 * Client table
 */

namespace atk4\invoice\Model;

use atk4\data\Model;
use atk4\data\ValidationException;
use atk4\ui\FormField\Line;

class Client extends Model
{
    public $table = 'client';

    public function init()
    {
        parent::init();

        $this->addField('name');
        $this->addField('email');

        $this->addAction('send_statement', [ 'args'=>['subject'=>'line'], 'preview'=>'get_statement_preview']);
    }

    public function get_statement_preview() {
        return 'here goes your statement '.$this['name'];

    }
    public function send_statement() {
        /*
        if (!isset($this->app->mailer)) {
            throw new ValidationException('ouch');
        }
        */
        $email = $this->get_statement_preview();
        return 'sent to '.$this['email'];
    }
}