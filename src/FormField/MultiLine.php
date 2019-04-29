<?php
/**
 * Allow to add multiple inputs into one table line.
 */

namespace atk4\invoice\FormField;

use atk4\data\Field_SQL_Expression;
use atk4\data\Model;
use atk4\data\ValidationException;
use atk4\dsql\Expression;
use atk4\ui\Template;

class MultiLine extends \atk4\ui\View
{
    public $multiLineTemplate = null;
    public $multiLine = null;
    public $linesFieldName = 'lines_field';
    public $fieldDefs = null;
    public $cb = null;

    public function init()
    {
        parent::init();

        $this->app->useSuiVue();

        $this->app->requireJS('../public/atk-invoice.js');


        if (!$this->multiLineTemplate) {
            $this->multiLineTemplate = new Template('<div id="{$_id}" class="ui basic segment"><atk-multiline v-bind="initData"></atk-multiline></div>');
        }

        $this->multiLine = $this->add(['View', 'template' => $this->multiLineTemplate]);

        $this->cb = $this->add('jsCallback');
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
        if ($this->cb->triggered()){
            $this->cb->set(function() {
                try {
                    $this->renderCallback();
                } catch (ValidationException $e) {
                    $this->app->terminate(json_encode(['success' => false, 'error' => 'field validation', 'fields' => $e->errors ]));
                } catch (\atk4\Core\Exception $e) {
                    $this->app->terminate(json_encode(['success' => false, 'error' => $e->getMessage()]));
                } catch (\Error $e) {
                    $this->app->terminate(json_encode(['success' => false, 'error' => $e->getMessage()]));
                }
            });
        }

        parent::renderView();

        $this->multiLine->vue('atk-multiline',
                              [
                                  'data' => [
                                      'linesField'  => $this->linesFieldName,
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

        $this->loadPOST();
        $dummyValues = $this->getExpressionValues($this->model);


        $this->app->terminate(json_encode(array_merge($response, ['expressions' => $dummyValues])));
    }

    /**
     * Looks inside the POST of the request and loads it into the current model.
     */
    private function loadPOST()
    {
        $post = $_POST;

        $errors = [];

        foreach ($this->fieldDefs as $def) {
            $fieldName = $def['field'];
            try {
                if ($fieldName === $this->model->id_field) {
                    continue;
                }
                $field = $this->model->getElement($fieldName);
                $value = isset($post[$fieldName]) ? $post[$fieldName] : null;

                // save field value only if field was editable in form at all
                if (!$field->read_only) {
                    $this->model[$fieldName] = $this->app->ui_persistence->typecastLoadField($field, $value);
                }

            } catch (\atk4\core\Exception $e) {
                $errors[$fieldName] = $e->getMessage();
            }
        }

        if ($errors) {
            throw new \atk4\data\ValidationException($errors);
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
