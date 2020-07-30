<?php

declare(strict_types = 1);

namespace atk4\invoice\Layout;

use atk4\ui\View;

/**
 * Map model field value to template tag.
 */
class InvoicePrint extends View
{
    public $defaultTemplate =  __DIR__ . '/../../template/invoice-print.html';

    public $uiPersistence;

    protected function renderView(): void
    {
        parent::renderView();
        if ($this->model && $this->uiPersistence) {
            $values = $this->uiPersistence->typecastSaveRow($this->model, $this->model->get());
            foreach ($values as $k => $v) {
                $this->template->trySet($k, $v);
            }
        }
    }
}
