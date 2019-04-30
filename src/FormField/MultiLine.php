<?php
/**
 * Allow to add multiple inputs into one table line.
 */

namespace atk4\invoice\FormField;

use atk4\data\Field_SQL_Expression;
use atk4\data\Model;
use atk4\data\ValidationException;
use atk4\dsql\Expression;
use atk4\ui\Exception;
use atk4\ui\FormField\Generic;
use atk4\ui\jsVueService;
use atk4\ui\Template;

class MultiLine extends Generic
{
    public $layoutWrap = false;
    public $multiLineTemplate = null;
    public $multiLine = null;
    public $linesFieldName = 'lines_field';
    public $fieldDefs = null;
    public $cb = null;
    public $rowErrors = null;

    public function init()
    {
        parent::init();

        $this->app->useSuiVue();

        $this->app->requireJS('../public/atk-invoice.js');


        if (!$this->multiLineTemplate) {
            $this->multiLineTemplate = new Template('<div id="{$_id}" class="ui basic segment"><atk-multiline v-bind="initData"></atk-multiline>{$Input}</div>');
        }

        $this->multiLine = $this->add(['View', 'template' => $this->multiLineTemplate]);

        $this->cb = $this->add('jsCallback');

        $this->form->addHook('loadPOST', function($form){
            $rows = json_decode($_POST[$this->short_name], true);
            if ($rows) {
                $this->rowErrors = $this->validateRows($rows);
                if ($this->rowErrors) {
                    throw new ValidationException([$this->short_name => 'multine error']);
                }
            }
        });

        // Add special form error handling.
        $this->form->addHook('displayError', function($form, $fieldName, $str) {
            if ($fieldName === $this->short_name) {
                $jsError = [(new jsVueService())->emitEvent('atkml-row-error', ['id' => $this->multiLine->name, 'errors' => $this->rowErrors])];
            } else {
                $jsError = [$form->js()->form('add prompt', $fieldName, $str)];
            }
            return $jsError;
        });
    }

    /**
     * Input field collecting multiple rows of data.
     *
     * @return string
     */
    public function getInput()
    {
        return $this->app->getTag('input', [
            'name'        => $this->short_name,
            'type'        => 'hidden',
            'value'       => $this->getValue(),
            'readonly'    => true,
        ]);
    }


    public function getValue()
    {
        return null;
    }


    /**
     * Validate each row and return errors if found.
     *
     * @param $rows
     *
     * @return array|null
     */
    public function validateRows($rows)
    {
        $rowErrors = [];

        foreach ($rows as $kr => $row) {
            $rowId = $this->getMlRowId($row);
            foreach ($row as $kc => $col) {
                foreach ($col as $fieldName => $value) {
                    if ($fieldName === '__atkml' ||  $fieldName === $this->model->id_field ) {
                        continue;
                    }
                    try {
                        $field = $this->model->getElement($fieldName);
                        // save field value only if field was editable in form at all
                        if (!$field->read_only) {
                            $this->model[$fieldName] = $this->app->ui_persistence->typecastLoadField($field, $value);
                        }

                    } catch (\atk4\core\Exception $e) {
                        $rowErrors[$rowId][] = ['field' => $fieldName, 'msg' => $e->getMessage()];
                    }
                }
            }
            $rowErrors = $this->addModelValidateErrors($rowErrors, $rowId);
        }

        if ($rowErrors) {
            return $rowErrors;
        }

        return null;
    }

    public function saveRows($rows, $parentModel = null, $ref = null)
    {
        $rows = json_decode($rows, true);

        if ($parentModel && !$parentModel->loaded()) {
            throw new Exception('Parent model need to be loaded');
        }

        // try load related data
        if ($ref) {
            $ids = [];
            foreach ($parentModel->ref($ref) as $id => $data) {
                $ids[] = $id;
            }
        }

        foreach ($rows as $kr => $row) {
            $rowId = $this->getMlRowId($row);
            foreach ($row as $kc => $col) {
                foreach ($col as $fieldName => $value) {
                    if ($fieldName === '__atkml') {
                        continue;
                    }
                    $field = $this->model->getElement($fieldName);

                    if (!$field instanceof Field_SQL_Expression) {
                        $field->set($value);
                    }

                }
            }
            $this->model->save();
        }


    }

    private function addModelValidateErrors($errors, $rowId)
    {
        //$errors = [];
        $e = $this->model->validate();
        if ($e) {
            foreach ($e as $f => $msg) {
                $errors[$rowId][] = ['field' => $f, 'msg' => $msg];
            }
        }
        return $errors;
    }

    private function getMlRowId($row)
    {
        $rowId = null;
        foreach ($row as $k => $col) {
            foreach ($col as $fieldName => $value) {
                if ($fieldName === '__atkml') {
                    $rowId = $value;
                }
            }
            if ($rowId) break;
        }
        return $rowId;
    }

    public function setModel($m, $fields = null)
    {

        $m = parent::setModel($m);
        if (!$fields) {
            $fields = $this->getModelFields($m);
        }
        $fields = array_merge([$m->id_field], $fields);


        foreach ($fields as $fieldName) {
            $field = $m->getElement($fieldName);

            if (!$field instanceof \atk4\data\Field) {
                continue;
            }
            $type = $field->type ?  $field->type : 'string';

            if (isset($field->ui['form'])) {
                $type = $field->ui['form'][0];
            }


            $this->fieldDefs[] = [
                'field'       => $field->short_name,
                'type'        => $type,
                'caption'     => $field->getCaption(),
                'default'     => $field->default,
                'isEditable'  => $field->isEditable(),
                'isHidden'    => $field->isHidden(),
                'isVisible'   => $field->isVisible(),
            ];

        }

        return $m;
    }

    /**
     * Returns array of names of fields to automatically include them in form.
     * This includes all editable or visible fields of the model.
     *
     * @param \atk4\data\Model $model
     *
     * @return array
     */
    protected function getModelFields(\atk4\data\Model $model)
    {
        $fields = [];
        foreach ($model->elements as $f) {
            if (!$f instanceof \atk4\data\Field) {
                continue;
            }

            if ($f->isEditable() || $f->isVisible()) {
                $fields[] = $f->short_name;
            }
        }

        return $fields;
    }

    public function renderView()
    {
        if (!$this->model) {
            throw new Exception('Multiline field needs to have it\'s model setup.');
        }

        if ($this->cb->triggered()){
            $this->cb->set(function() {
                try {
                    $this->renderCallback();
                } catch (\atk4\Core\Exception $e) {
                    $this->app->terminate(json_encode(['success' => false, 'error' => $e->getMessage()]));
                } catch (\Error $e) {
                    $this->app->terminate(json_encode(['success' => false, 'error' => $e->getMessage()]));
                }
            });
        }

        $this->multiLine->template->setHTML('Input', $this->getInput());
        parent::renderView();

        $this->multiLine->vue('atk-multiline',
                              [
                                  'data' => [
                                      'linesField'  => $this->short_name,
                                      'fields'      => $this->fieldDefs,
                                      'idField'     => $this->model->id_field,
                                      'url'         => $this->cb->getJSURL()
                                  ]
                              ],
                              'atkMultiline'
        );
    }

    /**
     * Render callback.
     *
     * @throws ValidationException
     * @throws \atk4\core\Exception
     * @throws \atk4\data\Exception
     */
    private function renderCallback()
    {
        $response = [
            'success' => true,
            'message' => 'Success',
        ];

        $this->getRowData();
        $dummyValues = $this->getExpressionValues($this->model);


        $this->app->terminate(json_encode(array_merge($response, ['expressions' => $dummyValues])));
    }

    /**
     * Looks inside the POST of the request and loads data into the current model.
     * Allow to Run expression base on rowData value.
     */
    private function getRowData()
    {
        $post = $_POST;

        foreach ($this->fieldDefs as $def) {
            $fieldName = $def['field'];
            if ($fieldName === $this->model->id_field) {
                continue;
            }
            $value = isset($post[$fieldName]) ? $post[$fieldName] : null;
            try {
                $this->model[$fieldName] = $value;
            } catch (ValidationException $e) {
                //bypass validation at this point.
            }
        }
    }

    /**
     * Return values of each field expression in a model.
     *
     * @param $m
     *
     * @return mixed
     * @throws \atk4\core\Exception
     * @throws \atk4\data\Exception
     */
    private function getExpressionValues($m)
    {
        $dummyFields = [];
        foreach ($this->getExpressionFields($m) as $k => $field) {
            $dummyFields[$k]['name'] = $field->short_name;
            $dummyFields[$k]['expr'] = $this->getDummyExpression($field);
        }

        $dummyModel = new Model($m->persistence, ['table' => $m->table]);
        foreach ($dummyFields as $f) {
            $dummyModel->addExpression($f['name'], ['expr'=>$f['expr'], 'type' => $m->getElement($f['name'])->type]);
        }
        $values = $dummyModel->loadAny()->get();
        unset($values[$m->id_field]);

        $formatValue = [];
        foreach ($values as $f => $value) {
            $field = $m->getElement($f);
            $formatValue[$f] = $this->app->ui_persistence->_typecastSaveField($field, $value);
        }


        return $formatValue;
    }

    /**
     * Get all field expression in model.
     *
     * @return array
     */
    private function getExpressionFields($m)
    {
        $fields = [];
        foreach ($m->elements as $f) {
            if (!$f instanceof Field_SQL_Expression) {
                continue;
            }

            $fields[] = $f;
        }

        return $fields;
    }

    /**
     * Return expression where field are replace with their current or default value.
     * ex: total field expression = [qty] * [price] will return 4 * 100
     * where qty and price current value are 4 and 100 respectively.
     *
     * @param $expr
     *
     * @return mixed
     * @throws \atk4\core\Exception
     */
    private function getDummyExpression($exprField)
    {
        $expr = $exprField->expr;
        $matches = [];

        preg_match_all('/\[[a-z0-9_]*\]|{[a-z0-9_]*}/i', $expr, $matches);

        foreach ($matches[0] as $match) {
            $fieldName = substr($match, 1, -1);
            $field = $this->model->getElement($fieldName);
            if ($field instanceof Field_SQL_Expression) {
                $expr = str_replace($match, $this->getDummyExpression($field), $expr);
            } else {
                $expr = str_replace($match, $this->getValueForExpression($exprField, $fieldName), $expr);
            }
        }

        return $expr;
    }

    /**
     * Return a value according to field use in expression and the expression type.
     * If field use in expression is null , the default value is return.
     *
     *
     * @param $exprField
     * @param $fieldName
     *
     * @return int|mixed|string
     */
    private function getValueForExpression($exprField, $fieldName)
    {
        switch($exprField->type) {
            // will return 0 or the field value.
            case 'money':
            case 'integer':
            case 'number':
                $value = $this->model[$fieldName] ? $this->model[$fieldName] : 0;
                break;
            // will return "" or field value enclosed in bracket: "value"
            default:
                $value = $this->model[$fieldName] ? '"'.$this->model[$fieldName].'"' : '""';
        }

        return $value;
    }
}
