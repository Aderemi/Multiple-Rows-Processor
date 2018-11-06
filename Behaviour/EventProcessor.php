<?php
/**
 * Created by PhpStorm.
 * User: Akins
 * Date: 30/03/2018
 * Time: 8:24 PM
 */

namespace MultipleRows\Behaviour;


use Illuminate\Database\Eloquent\Model;
use MultipleRows\Contract\MultipleRowsProcessor;
use MultipleRows\Events\AfterCreate;
use MultipleRows\Events\AfterDelete;
use MultipleRows\Events\AfterUpdate;
use MultipleRows\Events\BeforeCreate;
use MultipleRows\Events\BeforeDelete;
use MultipleRows\Events\BeforeUpdate;
use Illuminate\Events\Dispatcher;

abstract class EventProcessor
{
    /**
     * Current model under process
     * @var Model
     */
    public $model;

    /**
     * Data that is used to fill the model
     * @var array
     */
    protected $data;

    /**
     * Some fields are not defined in sheet header instead they are derived
     * This array holds the derived key as value while field it is derived from is the key
     * @var array
     */
    protected $derivedDataMap;

    /**
     * Give convenient access to the sheet that is using this processor
     * @var MultipleRowsProcessor
     */
    protected $MultipleRows;

    /**
     * @var MultipleRowsProcessor
     */
    protected $multipleRows;

    /**
     * Manage filling of data into the model before creation actually happens
     * @param BeforeCreate $event
     * @return Model
     */
    public function onBeforeCreate(BeforeCreate $event)
    {
        $this->model = $event->getModel();
        $this->data = $event->getData();
        $this->fillModel();
        return $this->model;
    }

    /**
     * Manage filling of data into the model before modification actually happens
     * @param BeforeUpdate $event
     * @return Model
     */
    public function onBeforeUpdate(BeforeUpdate $event)
    {
        $this->model = $event->getModel();
        $this->data = $event->getData();
        $this->fillModel();
        return $this->model;
    }

    /**
     * Manage filling of data into the model before delete actually happens
     * @param BeforeDelete $event
     * @return Model
     */
    public function onBeforeDelete(BeforeDelete $event)
    {
        $this->model = $event->getModel();
        $this->data = $event->getData();
        $this->fillModel();
        return $this->model;
    }

    /**
     * Fired event after creation
     * @param AfterCreate $event
     * @return Model
     */
    public function onAfterCreate(AfterCreate $event)
    {
        $this->model = $event->getModel();
        $this->data = $event->getData();
        return $this->model;
    }

    /**
     * Run after modification
     * @param AfterUpdate $event
     * @return Model
     */
    public function onAfterUpdate(AfterUpdate $event)
    {
        $this->model = $event->getModel();
        $this->data = $event->getData();
        return $this->model;
    }

    /**
     * Run after deletion
     * @param AfterDelete $event
     * @return Model
     */
    public function onAfterDelete(AfterDelete $event)
    {
        $this->model = $event->getModel();
        $this->data = $event->getData();
        return $this->model;
    }

    public function apply(MultipleRowsProcessor $multipleRows)
    {
        $this->multipleRows = $multipleRows;
    }

    /**
     * Actually do the filling of the model
     */
    private function fillModel()
    {
        foreach ($this->data as $field => $datum){
            if($derivedField = $this->getDerivedField($field)){
                $callback = $this->getDerivedDataCallback($this->data, $derivedField);
                $this->setData($derivedField, call_user_func($callback));
            }
            $this->setData($field, $datum);
        }
    }

    /**
     * Getter for the derived keys, it also passed the keys through Field mappers
     * @param string $field
     * @return bool|mixed
     */
    private function getDerivedField(string $field)
    {
        $derivedField = $this->multipleRows->getFieldMap(true)[$field] ?? $field;
        return $this->derivedDataMap[$derivedField] ?? false;
    }

    /**
     * Most derived fields has to be compute through instruction, it is mandatory child that
     * has derived fields over-ride this function and return arrays of closures with the derived field has
     * key
     * @param $data
     * @param $index
     * @return string
     */
    protected function getDerivedDataCallback($data, $index)
    {
        return '';
    }

    /**
     * Setter for $this->model
     * @param string $key
     * @param string $value
     * @return null
     */
    protected function setData(string $key, string $value)
    {
        if(strpos($key, ".")){
            list($field, $relation) = explode('.', $key, 2);
            $attr = $this->model->$field ?? [];
            array_set($attr, $relation, $value);
            $this->model->$field = $attr;
        }else{
            $this->model->$key = $value;
        }
    }

    /**
     * Getter for $this->model
     * @param string $key
     * @return null
     */
    protected function getData(string $key)
    {
        if(strpos($key, ".")){
            list($field, $relation) = explode('.', $key, 2);
            $attr = $this->model->$field ?? [];
            return array_get($attr, $relation);
        }else{
            return $this->model->$key;
        }
    }


    public function subscribe(Dispatcher $events)
    {
        $events->listen(BeforeCreate::class, [$this, 'onBeforeCreate']);
        $events->listen(AfterCreate::class, [$this, 'onAfterCreate']);
        $events->listen(BeforeUpdate::class, [$this, 'onBeforeUpdate']);
        $events->listen(AfterUpdate::class, [$this, 'onAfterUpdate']);
        $events->listen(BeforeDelete::class, [$this, 'onBeforeDelete']);
        $events->listen(AfterDelete::class, [$this, 'onAfterDelete']);
    }
}