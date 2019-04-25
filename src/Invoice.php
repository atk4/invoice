<?php
/**
 *
 *
 */

namespace atk4\invoice;

use atk4\invoice\FormField\MultiLine;
use atk4\ui\Form;

class Invoice extends Form
{
    public $spot = 'Content';
    public $multiLine = null;

    public function init()
    {
        parent::init();

        $this->multiLine = $this->layout->add(new MultiLine(), $this->spot);
        $setup = $this->addField('lines_field', ['TextArea']);
        //$setup->set(json_encode(['id' => 1, 'name' => 'productX', 'qty' => 5, 'price' => 50]));
    }

    public function setMultiLineModel($m, $fields = null)
    {
        $this->multiLine->setModel($m, $fields);
    }
}
