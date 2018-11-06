<?php
/**
 * Created by PhpStorm.
 * User: Akins
 * Date: 10/31/2018
 * Time: 8:36 PM
 */

namespace MultipleRows\Processors;


use MultipleRows\Contract\MultipleRowsProcessor;
use MultipleRows\Utilities\ProcessorTraits\ProcessorBaseTrait;

abstract class JSONProcessor extends MultipleRowsProcessor
{
    use ProcessorBaseTrait;

    protected function prepareContent(string $content)
    {
        $jsonArr = json_decode($content);
        $ret = [];
        $body = $this->disperseType($jsonArr);

        foreach ($body as $key => $row)
        {
            $split = $this->sanitize($row);
            if(count($split) == 1 && empty($split[0])) continue;
            $ret[$key] = $split;
        }
        $this->headers = $ret[0];
    }

    private function disperseType(array $jsonArray)
    {
        if(isset($jsonArray['header'], $jsonArray['body'])){
            return $this->mapType($jsonArray);
        }
        if(array_keys($jsonArray[0]) === range(0, count($jsonArray[0]) - 1)){
            return $this->arrayDumpType($jsonArray);
        }
        else{
            foreach (array_keys($jsonArray[0]) as $property){
                if(!is_string($property)){
                    $this->addError("The supplied JSON structure processing is not yet available");
                }
            }
            return $this->objectDumpType($jsonArray);
        }
    }

    private function mapType(array $jsonArray)
    {
        $this->prepareHeader($jsonArray['header']);
        return $jsonArray['body'];
    }

    private function arrayDumpType(array $jsonArray)
    {
        $this->prepareHeader(array_shift($jsonArray));
        return $jsonArray;
    }

    private function objectDumpType(array $jsonArray)
    {
        $this->prepareHeader(array_keys($jsonArray[0]));
        return array_map(function($value){
            return array_values($value);
        }, $jsonArray);
    }
}