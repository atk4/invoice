<?php

declare(strict_types = 1);

namespace atk4\invoice\Layout;


use atk4\ui\Form\Layout;

/**
 * Special layout for Invoice Form.
 */

class InvoiceForm extends Layout
{
    /** @var string  */
    public $defaultTemplate = __DIR__ . '/../../template/invoice-form.html';
}
