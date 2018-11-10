<?php
/**
 * Created by PhpStorm.
 * User: Aderemi Dayo<aderemi.dayo.o@gmail.com>
 * Date: 30/03/2018
 * Time: 8:03 PM
 */

namespace MultipleRows\Contract;

use MultipleRows\Behaviour\EventProcessor;
use MultipleRows\Events\AfterDelete;
use MultipleRows\Events\AfterUpdate;
use MultipleRows\Events\AfterCreate;
use MultipleRows\Events\BeforeCreate;
use MultipleRows\Events\BeforeDelete;
use MultipleRows\Events\BeforeUpdate;
use MultipleRows\Resolvers\HeaderResolver;
use Illuminate\Database\Eloquent\Model;
use MultipleRows\Exceptions\MultipleRowsException;
use MultipleRows\Resolvers\ResolverInterface;
use \Validator;

abstract class MultipleRowsProcessor
{
    /**
     * MultipleRows is having has-a relationship with Processor,
     * this property holds the Processor
     * @var EventProcessor
     */
    private $processor;

    /**
     * Action resolver resolves what to do base on content of headers and action specifies
     * @var ResolverInterface
     */
    protected $actionResolver;

    /**
     * Errors for current data being processed
     * @var array
     */
    private $errors = [];

    /**
     * The whole errors for all lines in the file
     * @var array
     */
    private $mainErrors = [];

    /**
     * The header
     * @var array
     */
    protected $headers = [];

    /**
     * The remaining part of the file when the header has been removed
     * @var array
     */
    protected $data = [];

    /**
     * Current line from $data which is under processing
     * @var array
     */
    private $_data = [];

    /**
     * There must be a unique key identifier this property holds its header value
     * @var array
     */
    private $uniqueFields;

    /**
     * Some sheets may specify headers that must be present
     * for the sheet to be processed.
     * @var array
     */
    protected $constantHeaders;

    /**
     * This contains headers that are indefinite
     * hence has to be resolved at runtime
     * e.g category, sub-category in groceries and restaurant, menu in foods
     * mostly used when the occurrence of the variable is indeterminate
     * @var array
     */
    protected $variableHeaders;

    /**
     * contains the rules to validate
     * variable headers
     * @var array
     */
    protected $variableHeaderRules;

    /**
     * The resolved headers
     * @var array
     */
    private $resolvedHeaders;

    /**
     * @var HeaderResolver
     */
    protected $headerResolver;

    /**
     * Some fields are mapped from a header to a database column
     * @var array
     */
    protected $fieldMap = [];

    /**
     * Field maps that contains underscore(_) as to be calculated to something that
     * really make sense in the database schema... this property hold the values
     * @var array
     */
    private $calculatedFieldMap = [];

    /**
     * Holds uniqueID of data in the file that are affected by current process
     * @var array
     */
    protected $affected = [];

    /**
     * Holds uniqueID, field, from_ and to_ of data in the file that are changed by current process
     * @var array
     */
    protected $delta = [];

    /**
     * Holds setting options that are set from config and subclasses
     * @var array
     */
    protected $options = [];

    /**
     * Current line in the file under process
     * @var integer
     */
    private $currentLine = 1;

    /**
     * Action to perform on the sheet
     * @var string
     */
    protected $action = "";

    /**
     * ORM Model that persist each line into the database
     * NOTE: Process and MultipleRows are sharing this
     * @var Model
     */
    protected $model;

    /**
     * ORM Model that hold pointer to the database file that containing the file under process
     * NOTE: We assume files are uploaded and pointer to the uploaded file is saved in the database
     * @var Model
     */
    private $loadedFile;

    /**
     * ORM Model that records each file processes
     * @var Model
     */
    private $runsRecorder;

    /**
     * ORM Model that records errors(i.e. $this->mainErrors) for each file processes runsRecorder records
     * @var Model
     */
    private $errorRecorder;

    /**
     * ORM Model that records changes(i.e. $this->delta) for each file processes runsRecorder records
     * @var Model
     */
    private $changeRecorder;

    /**
     * ORM Model that records affected Unique identifier(i.e. $this->affected) for each file processes runsRecorder records
     * @var Model
     */
    private $uniqueAffectedRecorder;

    /**
     * Used in modifying sub-document for no sql, it holds the where clause index for mapped field with underscore (_)
     * @var array
     */
    protected $_ = [];

    /**
     * Index of unique identifier in the header array
     * @var int
     */
    protected $idHeaderLocation;

    /**
     * returns laravel validation rules for each record in the CSV
     * @param string $method
     * @param array $data
     * @return array
     */
    protected abstract function rules(string $method, array $data) : array;

    /**
     * Returns the unique identifier field for this sheet
     * NOTE: this must be present in the header at all time
     * @return string
     */
    public abstract function getUniqueIDField() : string;

    /**
     * Return model class which will persist the record
     * @return Model
     */
    protected abstract function model(): Model;


    protected abstract function prepareContent(string $content);

    /**
     * Returns the Processor for the Multiple rows Process
     * @return EventProcessor
     */
    protected abstract function eventProcessor(): EventProcessor;

    /**
     * Returns array of headers that might be a form of placeholder like
     * [
     *  'create' => 'name:required|discount|/^category*#\d$/:match|sub_cat:contain|system:start_with|batch:end_with,required'
     *  'update' => 'name:required'
     * ]
     * each action is represented with a key and each column is separated with a |,
     * in the example:
     * name column is required
     * discount column is not
     * the third rule is regex to match headers like category#1, category#2, category#3
     * the fourth rule match column header that contain 'sub_cat' like sub_categories
     * the fifth rule match column header that start with 'system' like system_generated
     * the sixth rule match column header that end with 'batch' like first_batch
     * match a given regex, the header must contain sub_category and so on
     * @return array
     */
    public abstract function getRuleHeaders(): array;


    /**
     * MultipleRows constructor.
     * @param Model|null $file
     * @param string $action
     * @param array $options
     * @throws MultipleRowsException
     */
    public function __construct(Model $file = null, string $action = "", array $options = [])
    {
        $this->loadConfigAndRecorders();
        $this->model = $this->model();
        $this->processor = $this->eventProcessor();
        $this->processor->apply($this);

        if(!is_null($file)){
            $this->setProcessFile($file);
        }

        if(!empty($action)){
            $this->setAction($action);
        }

        if(!is_null($file) && !empty($action)){
            $this->setResolver($this->resolver());
            return $this->initialize();
        }
        return $this;
    }

    /**
     * Initializes the whole processes
     * @param Model $file
     * @param string $action
     * @return $this
     * @throws MultipleRowsException
     */
    public function initialize(string $action = "", Model $file = null)
    {
        if(!isset($this->processor))
            throw new MultipleRowsException("Processor is not set for this sheet operation");

        if(empty($this->data) && is_null($file))
            $this->addError("No loaded file for this sheet operation");
        else
            if(empty($this->data)){
                $this->setProcessFile($file);
            }

        if(empty($this->action) && empty($action))
            $this->addError("No loaded file for this sheet operation");
        else
            if(empty($this->action)) $this->setAction($action);

        if(count($this->errors) > 0)
        {
            return $this;
        }

        return $this->disperse();
    }

    /**
     * Setter for the file to be processed
     * @param Model $file
     */
    protected function setProcessFile(Model $file)
    {
        $this->loadedFile = $file;
        $this->loadFile();
    }

    /**
     * Load the file from drive
     * @return $this
     */
    public function loadFile()
    {
        $fileName = $this->loadedFile->toArray()["file_name"];
        $this->prepareContent(\Storage::disk($this->getStorage())
            ->get($fileName));
        return $this;
    }

    /**
     * Disperses to various process actions
     * @return $this|MultipleRowsProcessor
     */
    protected function disperse()
    {
        if( !$this->actionResolver->validate() ){
            $this->addError($this->actionResolver->getError());
            return $this;
        }

        $actionMethod = $this->getAction();
        return $this->$actionMethod();
    }

    private function getActionCase($key)
    {
        return $this->options[$key] ?? null;
    }
    /**
     * Create all records in the file in the database
     * @return $this
     */
    public function create()
    {
        foreach ($this->data as $datum){
            $this->validate($datum, "POST");
            if(!empty($this->errors)){
                $this->pushErrorsToMain();
                continue;
            }
            $createFunction = $this->options['create-method'] ?? 'save';
            $this->processor->model = $this->model();
            $this->processor->model = event(new BeforeCreate($datum, $this->processor->model))[0];
            if(!empty($this->errors)){
                $this->pushErrorsToMain();
                continue;
            }
            $this->processor->model->$createFunction();
            event(new AfterCreate($datum, $this->processor->model->fresh()));
            $this->addAffected($datum);
        }
        return $this;
    }

    /**
     * Update all records in the file in the database
     * @return $this
     * @throws MultipleRowsException
     */
    public function update()
    {
        foreach ($this->data as $datum){
            $this->validate($datum, "PUT");
            if(!empty($this->errors)){
                $this->pushErrorsToMain();
                continue;
            }
            $this->processor->model = $this->model::where($this->getUniqueIDField(),
                $datum[$this->getUniqueIDField()])->first();
            $this->processor->model = event(new BeforeUpdate($datum, $this->processor->model))[0];
            if(!empty($this->errors)){
                $this->pushErrorsToMain();
                continue;
            }
            $updateFunction = $this->options['update-method'] ?? 'update';
            $this->addDelta($datum);
            $this->processor->model->$updateFunction();
            event(new AfterUpdate($datum, $this->processor->model->fresh()));
        }
        return $this;
    }

    /**
     * Delete all records in the file from the database
     * @return $this
     */
    public function delete()
    {
        foreach ($this->data as $datum){
            $this->validate($datum, "DELETE");
            if(!empty($this->errors)){
                $this->pushErrorsToMain();
                continue;
            }
            $this->processor->model = $this->model::where($this->getUniqueIDField(),
                $datum[$this->getUniqueIDField()])->first();
            $this->processor->model = event(new BeforeDelete($datum, $this->processor->model))[0];
            if(!empty($this->errors)){
                $this->pushErrorsToMain();
                continue;
            }
            $deleteFunction = $this->options['delete-method'] ?? 'delete';
            $this->processor->model->$deleteFunction();
            $this->addAffected($datum);
            event(new AfterDelete($datum, $this->processor->model->fresh()));
        }
        return $this;
    }

    /**
     * Validate current data according to defined rules in the subclass
     * @param array $data
     * @param string $method
     * @return bool
     */
    private function validate(array &$data, string $method)
    {
        $data = $this->matchHeader($data);
        if(!$data) return false;
        $validator = \Validator::make($data, $this->rules($method, $data));
        if ($validator->fails()) {
            $errors = $validator->getMessageBag()->all();
            array_walk($errors, [$this, "addError"], $data[$this->getUniqueIDField()]);
        }
        $keysBeforeCalculation = array_keys($data);
        $data = $this->mapFieldName($data);
        $this->calculatedFieldMap = array_combine($keysBeforeCalculation, array_keys($data));
    }

    /**
     * Current line data is supplied to this function and it returns associative array containing
     * matching index from the header
     * @param $data
     * @return array|bool
     */
    private function matchHeader($data)
    {
        $this->currentLine++;
        if(count($data) != count($this->headers)){
            array_push($this->errors, "There is error on line Number {$this->currentLine}");
            return false;
        }
        $this->_data = $data;
        $ret = array();
        for($i = 0; $i < count($this->headers); $i++){
            $ret[$this->headers[$i]] = $data[$i];
        }
        return $ret;
    }

    /**
     * Consumed current line data with field matched against header and return data
     * with index mapped from FieldMap
     * @param array $data
     * @return mixed
     */
    protected function mapFieldName(array $data)
    {
        foreach ($data as $key => $value){
            if(isset($this->fieldMap[$key])){
                if(strpos($this->fieldMap[$key], ".")){
                    if(strpos($this->fieldMap[$key], "._.")){
                        $pieces = explode('.',  $this->fieldMap[$key]);
                        $ret = "";
                        for($i = 0; $i <= count($pieces) - 1; ++$i) {
                            $ret .= $i > 0 ? "." : "";
                            if($pieces[$i] == "_"){
                                $model = $this->getWorkingModel();
                                if(!$model)
                                    $ret .= "0";
                                else{
                                    $ret .= $this->getUnderScoreDataIndex($pieces[$i - 1], $model);
                                }
                            }else{
                                $ret .= $pieces[$i];
                            }
                        }
                        unset($data[$key]);
                        $data[$ret] = $value;
                    }
                }else{
                    unset($data[$key]);
                    $data[$this->fieldMap[$key]] = $value;
                }
            }
        }
        return $data;
    }

    /**
     * Underscore really represent index of value array of the sub-document
     * This function returns the index of the sub-document to be modified or
     * new one to be added
     * @param $_index
     * @param Model $model
     * @return mixed
     */
    private function getUnderScoreDataIndex(string $_index, Model $model = null)
    {
        $model = $model ?? $this->getWorkingModel();
        $count = 0;
        foreach ($model->$_index as $value){
            $_fields = $this->getUnderScoreFields($_index);
            if(!is_array($_fields)){
                if($this->getDataForHeader($_fields) == $value[$_fields]){
                    return $count;
                }else{
                    $count++;
                }
            }else{
                for($i = count($_fields); $i > 0; --$i){
                    $_field = $_fields[$i - 1];
                    if($this->getDataForHeader($_field) != $value[$_field]){
                        break;
                    }
                }
                if($i == 0)
                    return $count;
                else
                    $count++;
            }
        }
        return $count;
    }

    /**
     * Getter for underscore
     * @param $_index
     * @return mixed
     */
    private function getUnderScoreFields($_index)
    {
        return $this->_[$_index];
    }

    /**
     * Get value from the current line data
     * @param $headerIndex
     * @param array $data
     * @return mixed|null
     */
    private function getDataForHeader($headerIndex, $data = [])
    {
        $data = !empty($data) ? $data : $this->_data;
        $headerIndex = array_search($headerIndex, $this->headers);
        if($headerIndex === false || !isset($data[$headerIndex])){
            $this->addError("Data not present for {$headerIndex}", 0, $data[$this->idHeaderLocation]);
            return null;
        }
        return $data[$headerIndex];
    }

    /**
     * Add error for current line process
     * @param string $error
     * @param int $index
     * @param string $uniqueIdValue
     */
    public  function addError(string $error, int $index = 0, string $uniqueIdValue = "")
    {
        $identifier = $this->_data[$this->idHeaderLocation] ?? "Sheet Level Error";
        $error = empty($uniqueIdValue) ? "{" . $identifier . "} -> " . $error : "{" . $uniqueIdValue . "} -> " .  $error;
        if (!in_array($error, $this->errors))array_push($this->errors, $error);
    }

    /**
     * Push each line errors into the main errors contain
     */
    private  function pushErrorsToMain()
    {
        $this->mainErrors = array_merge($this->mainErrors, $this->errors);
        $this->errors = array();
    }

    /**
     * Record what has changed
     * @param $datum
     * @throws MultipleRowsException
     */
    private function addDelta($datum)
    {
        $fromValues = $this->getWorkingModel()->toArray();
        foreach ($datum as $key => $value){
            $currentValue = $this->getMappedData($key, $fromValues, $datum);
            if($currentValue != $value){
                $delta = ["fieldName" => $key,
                    "from_" => $currentValue,
                    "to_" => $value,
                    $this->getUniqueIDField() => $this->getMappedData($this->getUniqueIDField(), $fromValues, $datum)
                ];
                if(!in_array($delta, $this->delta)){
                    array_push($this->delta, $delta);
                    $this->addAffected($datum);
                }
            }
        }
    }

    /**
     * Record who the process has affected
     * @param $datum
     */
    private function addAffected($datum)
    {
        if (!in_array($datum[$this->getUniqueIDField()], $this->affected))
            array_push($this->affected, $datum[$this->getUniqueIDField()]);
    }

    /**
     * Get a mapped key(can carry dot notation) from the data
     * @param string $key
     * @param array $data
     * @param array $toValue
     * @return mixed|null|string
     * @throws MultipleRowsException
     */
    protected function getMappedData(string $key, array $data, array $toValue)
    {
        if(isset($this->fieldMap[$key])){
            if(strpos($this->fieldMap[$key], ".")){
                $pieces = explode('.',  $this->fieldMap[$key]);
                $ret = "+++***+++";
                for($i = 0; $i < count($pieces); $i++){
                    if(!isset($data[$pieces[$i]])){
                        if($pieces[$i] == "_"){
                            return $this->underScore($pieces[$i - 1], $toValue, $data);
                        }
                        if($ret != "+++***+++" && !isset($ret[$pieces[$i]])){
                            return null;
                        }
                    }
                    $ret = $ret == "+++***+++" ? $data[$pieces[$i]] : $ret[$pieces[$i]];
                }
                return $ret;
            }
            return array_get($data, $this->fieldMap[$key]);
        }
        return array_get($data, $key);
    }

    /**
     * @param string $_index
     * @param array $pointer
     * @param array $data
     * @return null|array
     * @throws MultipleRowsException
     */
    protected function underScore(string $_index, array $pointer, array $data)
    {
        if(!isset($this->_[$_index])){
            throw new MultipleRowsException("Underscore pointer is not present in sheet class");
        }
        $_data = $data[$_index];
        foreach ($_data as $datum) {
            if(!is_array($this->_[$_index])){
                if($datum[$this->_[$_index]] == $pointer[$this->_[$_index]]){
                    return $datum;
                }
            }else{
                for($i = count([$this->_[$_index]]) - 1; $i >= 0; --$i){
                    $underS = $this->_[$_index][$i];
                    if(!isset($datum[$underS]) || !isset($pointer[$underS])){
                        throw new MultipleRowsException("Underscore pointer is not present in sheet class");
                    }
                    if($datum[$underS] != $pointer[$underS]){
                        break;
                    }
                }
                if($i == 0)
                    return $datum;
            }
        }
        return null;
    }

    /**
     * Get current working model for current line in process
     * @param null $id
     * @return mixed
     */
    private function getWorkingModel($id = null)
    {
        $id = $id ?? $this->_data[$this->idHeaderLocation];
        return $this->model::where($this->getUniqueIDField(), $id)->first();
    }

    /**
     * Compare two headers to check if they are equal
     * @param $headerOne
     * @param $headerTwo
     * @return bool
     */
    protected function areEqual($headerOne, $headerTwo)
    {
        return (
            is_array($headerOne) && is_array($headerTwo) &&
            count($headerOne) == count($headerTwo) &&
            array_diff($headerOne, $headerTwo) === array_diff($headerTwo, $headerOne)
        );
    }

    /**
     * Check if this process has changed anything in the database
     * @return boolean
     */
    public function isChanged(): bool
    {
        $this->pushErrorsToMain();
        if(count($this->mainErrors) || count($this->affected) || count($this->delta)) return true;
        return false;
    }

    /**
     * Getter for unique fields, for some record with multi unique identifier
     * NOTE: this is still nascent
     * @return array
     */
    public function getUniqueFields()
    {
        return $this->uniqueFields;
    }

    /**
     * Get all errors
     * @return array
     */
    public function getErrors()
    {
        $this->pushErrorsToMain();
        return $this->mainErrors;
    }

    /**
     * Get storage where files are uploaded to
     * @return string
     */
    protected function getStorage()
    {
        if (env('APP_ENV') == 'local') {
            return "public";
        }
        return 's3';
    }

    /**
     * Set processor
     * @param EventProcessor $processor
     */
    public function setProcessor(EventProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Get processor
     * @return mixed
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * Set resolver
     * @param EventProcessor $resolver
     */
    public function setResolver(ResolverInterface $resolver)
    {
        $this->actionResolver = $resolver;
    }

    /**
     * Get resolver
     * @return ResolverInterface
     */
    public function getResolver()
    {
        return $this->actionResolver;
    }

    /**
     * @return ResolverInterface
     */
    protected function resolver()
    {
        return new HeaderResolver($this);
    }

    /**
     * @param string $action
     */
    protected function setAction(string $action)
    {
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $recorder
     */
    protected function setRunRecorder(string $recorder)
    {
        $this->runsRecorder = new $recorder();
    }

    /**
     * @param string $recorder
     */
    protected function setChangeRecorder(string $recorder)
    {
        $this->changeRecorder = new $recorder();
    }

    /**
     * @param string $recorder
     */
    protected function setErrorRecorder(string $recorder)
    {
        $this->errorRecorder = new $recorder();
    }

    /**
     * @param string $recorder
     */
    protected function setUniqueAffectedRecorder(string $recorder)
    {
        $this->uniqueAffectedRecorder = new $recorder();
    }

    /**
     * @return array
     */
    public function getAffected(): array
    {
        return $this->affected;
    }


    /**
     * @return array
     */
    public function getDelta(): array
    {
        return $this->delta;
    }

    /**
     * @return array
     */
    public function getMainErrors(): array
    {
        return $this->mainErrors;
    }

    /**
     * Get either calculated or unmodified field map
     * @var bool $calculated
     * @return array
     */
    public function getFieldMap(bool $calculated = false): array
    {
        return $calculated ? $this->calculatedFieldMap : $this->fieldMap;
    }

    /**
     * @return int
     */
    public function getErrorCount(): int
    {
        return count($this->mainErrors);
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Config.php is loaded by this function
     * @throws MultipleRowsException
     * @return array
     */
    private function loadConfig()
    {
        $configFile = dirname((new \ReflectionClass(static::class))->getFileName()) . "/Config.php";
        if(!file_exists($configFile)){
            throw new MultipleRowsException("Configuration file is not found");
        }

        $config = include_once($configFile);
        if(array_diff(['run_recorder', 'error_recorder', 'affected_recorder', 'delta_recorder'], array_keys($config))){
            throw new MultipleRowsException("Configuration file errors: 'run_recorder', 'error_recorder', 'affected_recorder' and 'delta_recorder' must be present");
        }
        return $config;
    }

    /**
     * Load recorders from config files
     * @throws MultipleRowsException
     */
    private function loadConfigAndRecorders()
    {
        $config = $this->loadConfig();
        $this->setErrorRecorder($config['error_recorder']);
        $this->setRunRecorder($config['run_recorder']);
        $this->setUniqueAffectedRecorder($config['affected_recorder']);
        $this->setChangeRecorder($config['delta_recorder']);
        $this->options = array_merge($config, $this->options);
    }

    public function __destruct()
    {
        $this->pushErrorsToMain();

        /** @var int $id */
        if($this->isChanged())
            $id = $this->runsRecorder::create(["filename" => $this->loadedFile->file_name, "load_by" => session('admin')->fullname, "load_type" => $this->action])->id;

        //check if there are errors in main errors and record them into the error recorder
        if (!empty($this->mainErrors)) {
            $data = [];
            foreach ($this->mainErrors as $error) {
                if ($id != null || !empty($id)) {
                    array_push($data, ["LoadRun_id" => $id, "errors" => $error]);
                }
            }
            $this->errorRecorder::insert($data);
        } 

        //check if there is value in affected unique field and record them into affected unique recorder
        if (!empty($this->affected)) {
            $data = [];
            foreach ($this->affected as $uniqueAffected) {
                if ($id != null or ! empty($id)) {
                    array_push($data, ["LoadRun_id" => $id, "affected_sku_list" => $uniqueAffected]);
                }
            }
            $this->uniqueAffectedRecorder::insert($data);
        }

        //Deltas are changes that are committed during update processes
        if (!empty($this->delta)) {
            $data = [];
            foreach ($this->delta as $deltas) {
                if ($id != null or ! empty($id)) {
                    $deltas["LoadRun_id"] = $id;
                    array_push($data, $deltas);
                }
            }
            $this->changeRecorder::insert($data);
        }
    }
}