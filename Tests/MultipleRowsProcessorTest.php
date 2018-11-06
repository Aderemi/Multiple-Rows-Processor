<?php
/**
 * Created by PhpStorm.
 * User: Akins
 * Date: 19/04/2018
 * Time: 8:49 AM
 */

namespace MultipleRows\Tests;

use MultipleRows\Contract\MultipleRowsProcessor;
use \PHPUnit_Framework_TestCase;
use MultipleRows\Behaviour\EventProcessor;

class MultipleRowsProcessorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var MultipleRowsProcessor
     */
    private $csvProcessor;
    private $xmlProcessor;
    private $flatFileProcessor;
    private $jsonProcessor;

    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->csvProcessor = new TestCSVSheet(new TestModel());
        $this->xmlProcessor = new TestXMLSheet(new TestModel());
        $this->flatFileProcessor = new TestFlatTextFile(new TestModel());
        $this->jsonProcessor = new TestJsonSheet(new TestModel());
    }

    public function testSetProcessor()
    {
        $this->csvProcessor->setProcessor(new TestEventProcessor());
        $this->assertObjectHasAttribute('processor', $this->csvProcessor, "Processor set successfully");
    }

    /**
     * @depends testSetProcessor
     */
    public function testGetProcessor()
    {
        $this->assertInstanceOf(EventProcessor::class, $this->csvProcessor->getProcessor(), "Processor get succesfully");
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(MultipleRowsProcessor::class, $this->csvProcessor->initialize(), "Processor get succesfully");

    }

    public function testCreate()
    {

    }

    public function testUpdate()
    {

    }

    public function testDelete()
    {

    }

    public function testGetUniqueFields()
    {

    }

    public function testGetErrors()
    {

    }

    public function testLoadSheet()
    {

    }

    public function testGetAffected()
    {

    }

    public function testGetDelta()
    {

    }

    public function testGetMainErrors()
    {

    }
}
