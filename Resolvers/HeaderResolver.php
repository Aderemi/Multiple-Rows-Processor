<?php
/**
 * Created by PhpStorm.
 * User: Dammyololade
 * Date: 8/29/2018
 * Time: 7:22 PM
 */

namespace MultipleRows\Resolvers;

use MultipleRows\Contract\MultipleRowsProcessor;
use MultipleRows\Utilites\Str as StringClass;
use MultipleRows\Exceptions\MultipleRowsException;

/**
 * Class HeaderResolver
 * @package MultipleRows\Resolvers
 */
class HeaderResolver implements ResolverInterface
{

    /**
     * @var array
     */
    protected $ruleHeaders = [];

    /**
     * @var MultipleRowsProcessor
     */
    protected $processor;
    /**
     * @var array
     */
    protected $requestSheetHeader;

    /**
     * @var array
     */
    private $matchedList;

    /**
     * @var array
     */
    protected $action;

    /**
     * @var string
     */
    private $error;

    /**
     * @var array
     */
    protected $resolvedVariableHeaders = [];

    protected $ruleMap = [
        'required' => 'requiredHeaderColumn',
        'match' => 'regexMatchHeaderColumn',
        'contain' => 'containHeaderColumn',
        'start_with' => 'startWithHeaderColumn',
        'end_with' => 'endWithHeaderColumn'
    ];

    private $lightRule = ['match', 'contain', 'start_with', 'end_with'];

    public function __construct(MultipleRowsProcessor $processor)
    {
        $this->processor = $processor;
        $this->action = $processor->getAction();
        $this->ruleHeaders = $processor->getRuleHeaders();
        $this->requestSheetHeader = $processor->getHeaders();
    }

    /**
     * @return bool
     * @throws MultipleRowsException
     */
    public function validate(): bool
    {
        if(!isset($this->ruleHeaders[$this->action])){
            $this->mainErrorMessage = "Action is not found";
            return false;
        }

        if(empty($this->ruleHeaders[$this->action])) return true;

        $headers = StringClass::explode($this->ruleHeaders[$this->action], "|");
        foreach ($headers as $hd) {
            if(!$this->validateRule($hd)){
                return false;
            }
        }

        if(count($this->requestSheetHeader) === count($this->matchedList)) return true;
        return false;
    }

    /**
     * @param string $headerColumn
     * @return bool
     * @throws MultipleRowsException
     */
    protected function validateRule(string $headerColumn)
    {
        list($column, $rule) = StringClass::explode($headerColumn, ":");
        $columnIndex = array_search($column, $this->requestSheetHeader);
        if(empty($rule)){
            if($columnIndex){
                unset($this->requestSheetHeader[$columnIndex]);
            }
            return true;
        }
        $rules = StringClass::explode($rule, ",");

        $rulesBase = true;
        foreach ($rules as $rl) {
            $rulesBase &=  $this->ruleTest($rl, $column) || in_array($rl, $this->lightRule);
        }
        $rulesBase = (bool)$rulesBase;
        return (bool)$rulesBase;
    }

    /**
     * @param $rule
     * @param $value
     * @return bool
     * @throws MultipleRowsException
     */
    public function ruleTest(string $rule, string $value): bool
    {
        $method = $this->ruleMap[$rule] ?? "";
        if(empty($method) || !method_exists($this, $rule)){
            throw new MultipleRowsException("Header rule: {$value} is not defined");
        }
        return $this->$rule($value);
    }

    protected function requiredHeaderColumn(string $columnTitle)
    {
        $columnIndex = array_search($columnTitle, $this->requestSheetHeader);
        if(!$columnIndex){
            $this->error = "{$columnTitle} header is required";
            return false;
        }
        $this->matchedList[$columnIndex] = true;
        return true;
    }

    protected function regexMatchHeaderColumn(string $pattern)
    {
        $matched = false;
        array_walk($this->requestSheetHeader, function($column, $columnIndex) use($pattern, &$matched) {
            if(preg_match($pattern, $column)){
                $this->matchedList[$columnIndex] = true;
                $matched = true;
            }
        });

        if($matched) return true;
        $this->error = "{$pattern} does not match any header column";
        return false;
    }

    protected function containHeaderColumn(string $needle)
    {
        $matched = false;
        array_walk($this->requestSheetHeader, function($column, $columnIndex) use($needle, &$matched){
            if($needle != '' && mb_strpos($column, $needle) !== false){
                $this->matchedList[$columnIndex] = true;
                $matched = true;
            }
        });

        if($matched) return true;
        $this->error = "No header contains {$needle}";
        return false;
    }

    protected function startWithHeaderColumn(string $needle)
    {
        $matched = false;
        array_walk($this->requestSheetHeader, function($column, $columnIndex) use($needle, &$matched){
            if(substr($column, strlen($needle)) === (string) $needle){
                $this->matchedList[$columnIndex] = true;
                $matched = true;
            }
        });

        if($matched) return true;
        $this->error = "No header starts with {$needle}";
        return false;
    }

    protected function endWithHeaderColumn(string $needle)
    {
        $matched = false;
        array_walk($this->requestSheetHeader, function($column, $columnIndex) use($needle, &$matched){
            if(substr($column, -strlen($needle)) === (string) $needle){
                $this->matchedList[$columnIndex] = true;
                $matched = true;
            }
        });

        if($matched) return true;
        $this->error = "No header ends with {$needle}";
        return false;
    }

    public function getError(): string
    {
        return $this->error;
    }
}