<?php
/**
 * Created by PhpStorm.
 * User: Akins
 * Date: 10/31/2018
 * Time: 8:36 PM
 */

namespace MultipleRows\Processors;


use MultipleRows\Contract\MultipleRowsProcessor;
use MultipleRows\Utilities\ProcessorTraits\NonMarkUpTrait;
use MultipleRows\Utilities\ProcessorTraits\ProcessorBaseTrait;

abstract class CSVProcessor extends MultipleRowsProcessor
{
    use NonMarkUpTrait, ProcessorBaseTrait;
    protected $delimiter = ",";
}