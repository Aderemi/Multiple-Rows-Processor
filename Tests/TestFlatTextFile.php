<?php
/**
 * Created by PhpStorm.
 * User: Akins
 * Date: 21/04/2018
 * Time: 1:29 PM
 */

namespace MultipleRows\Tests;


use MultipleRows\Behaviour\EventProcessor;
use Illuminate\Database\Eloquent\Model;
use MultipleRows\Processors\CSVProcessor;
use MultipleRows\Processors\FlatTextProcessor;

class TestFlatTextFile extends FlatTextProcessor
{
    use TestTrait;
    const UNIQUE_FIELDS = ["product_sku", "name"];
}