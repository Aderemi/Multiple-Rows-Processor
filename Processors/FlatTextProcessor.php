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

abstract class FlatTextProcessor extends MultipleRowsProcessor
{
    use NonMarkUpTrait;
    private $delimiter = "\t";
}