<?php

namespace atk4\invoice\Layout;

use atk4\ui\FormLayout\Custom;

/**
 * Special layout for Invoice Form.
 */

class InvoiceForm extends Custom
{
    /** @var string  */
    public $defaultTemplate = __DIR__ . '/../../template/invoice-form.html';

    /**
     * If specified will appear on top of the group. Can be string or Label object.
     *
     * @var string
     */
    public $label;

    /**
     * Specify width of a group in numerical word e.g. 'width'=>'two' as per
     * Semantic UI grid system.
     *
     * @var string
     */
    public $width;

    /**
     * Set true if you want fields to appear in-line.
     *
     * @var bool
     */
    public $inline = false;

    public function init(): void
    {
        parent::init();
    }

    /**
     * Recursively renders this view.
     */
    public function recursiveRender()
    {
        $field_input = $this->template->cloneRegion('InputField');
        $field_no_label = $this->template->cloneRegion('InputNoLabel');


        $this->template->del('Content');

        foreach ($this->elements as $el) {
            // Buttons go under Button section
            $T = $el->short_name;
            if ($el instanceof \atk4\ui\Button) {
                $this->template->appendHTML('Buttons', $el->getHTML());

                continue;
            }

            $template = $el->short_name === 'ml' ? $field_no_label : $field_input;
            $label = $el->caption ?: $el->field->getCaption();

            // Anything but fields gets inserted directly
            if ($el instanceof \atk4\ui\FormField\CheckBox) {
                $template = $field_no_label;
                $el->template->set('Content', $label);
            }

            if ($this->label && $this->inline) {
                $el->placeholder = $label;
                $label = $this->label;
                $this->label = null;
            } elseif ($this->label || $this->inline) {
                $template = $field_no_label;
                $el->placeholder = $label;
            }

            // Fields get extra pampering
            $template->setHTML('Input', $el->getHTML());
            $template->trySet('label', $label);
            $template->trySet('label_for', $el->id . '_input');
            $template->set('field_class', $el->getFieldClass());

            if ($el->field->required) {
                $template->append('field_class', 'required ');
            }

            if (isset($el->width)) {
                $template->append('field_class', $el->width . ' wide ');
            }

            if ($el->hint && $template->hasTag('Hint')) {
                $hint = new \atk4\ui\Label([null, 'pointing', 'id' => $el->id . '_hint']);
                if (is_object($el->hint) || is_array($el->hint)) {
                    $hint->add($el->hint);
                } else {
                    $hint->set($el->hint);
                }
                $template->setHTML('Hint', $hint->getHTML());
            } elseif ($template->hasTag('Hint')) {
                $template->del('Hint');
            }

            $this->template->trySetHTML($el->short_name, $template->render());
        }

        // Now collect JS from everywhere
        foreach ($this->elements as $el) {
            if ($el->_js_actions) {
                $this->_js_actions = array_merge_recursive($this->_js_actions, $el->_js_actions);
            }
        }
    }
}