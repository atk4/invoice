<?php
/**
 *
 *
 */

namespace atk4\invoice;

use atk4\data\Model;
use atk4\ui\jsToast;
use atk4\ui\View;

class Invoice extends View
{

    /**
     * The form for this view.
     * if none is supply then one is assign per default.
     *
     * @var null
     */
    public $form = null;

    public $jsAction = null;

    /**
     * The multiline field name for form.
     *
     * @var string
     */
    //public $multiLine = 'ml';

    /**
     * Options for the MultiLine Field.
     *
     * @var array
     */
    public $options = ['size' => 'small'];


    public function init()
    {
        parent::init();

        if (!$this->form) {
            $this->form = $this->add('Form');
        }

        if (!$this->jsAction) {
            $this->jsAction = new jsToast('Saved!');
        }
    }

    /**
     * Set form model and layout.
     *
     * @param Model $m
     */
    public function setFormLayout($m)
    {
        $m = $this->form->setModel($m, false);

        $headerLayout = $this->form->layout->addSubLayout('Generic');
        $headerLayout->add(['Header', $m->tableCaption, 'size' => 4]);
        $headerGroup = $headerLayout->addGroup();
        $headerGroup->setModel($m, $m->getHeaderFields());

        $itemLayout = $this->form->layout->addSubLayout('Generic');
        $itemLayout->add(['Header', $m->items->tableCaption, 'size' => 4]);

        $ml = $itemLayout->addField('ml', ['MultiLine', 'options' => $this->options]);
        $ml->setModel($m, $m->getItemFields(), $m->itemsRef, $m->itemsRefId);
        $ml->onChange([$m, 'jsUpdateFields'], $m->getEventFields());

        $columnsLayout = $this->form->layout->addSubLayout('Columns');
        $columnsLayout->addColumn(12);
        $c = $columnsLayout->addColumn(4);
        $c->setModel($m, $m->getFooterFields());

        $this->form->onSubmit(function($f) use ($ml) {
            $f->model->save();
            $ml->saveRows();
            return $this->jsAction;
        });
    }
}
