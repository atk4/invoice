<?php

declare(strict_types = 1);

namespace atk4\invoice\Layout;


use Atk4\Ui\Form\Layout;

/**
 * Special layout for Invoice Form.
 */

class InvoiceForm extends Layout
{
    /** @var string  */
    public $defaultTemplate = __DIR__ . '/../../template/invoice-form.html';
}
