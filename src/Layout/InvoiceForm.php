<?php

namespace atk4\invoice\Layout;


use atk4\ui\FormLayout\Generic;

/**
 * Special layout for Invoice Form.
 */

class InvoiceForm extends Generic
{
    /** @var string  */
    public $defaultTemplate = __DIR__ . '/../../template/invoice-form.html';
}
