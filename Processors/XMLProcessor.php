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

abstract class XMLProcessor extends MultipleRowsProcessor
{
    use ProcessorBaseTrait;

    protected function prepareContent(string $content)
    {
        $xml = simplexml_load_string($content);
        return $this->disperseType($xml);
    }

    private function disperseType(\SimpleXMLElement $XMLElement)
    {
        if(!is_null($XMLElement->header) && !is_null($XMLElement->body)){
            return $this->headerItemsType($XMLElement);
        }
        if(!is_null($XMLElement->item)){
            return $this->itemsOnlyType($XMLElement);
        }
        else{
            $this->addError("The supplied XML structure processing is not yet available");
        }
        return [];
    }

    private function headerItemsType($XMLElement)
    {
        foreach($XMLElement->header as $hd){
            $header = [];
            foreach($hd->value as $value) array_push($header, $value);
            $this->prepareHeader($header);
        }
        $body = [];
        foreach($XMLElement->body as $items){
            $row = [];
            foreach($items->item as $item)
                foreach ($item->value as $value)array_push($row, $value);
            array_push($body, $row);
        }
        return $body;
    }

    private function itemsOnlyType($XMLElement)
    {
        foreach($XMLElement->item as $item){
            $header = [];
            foreach($item as $head => $value){
                array_push($header, $head);
            }
            $this->prepareHeader($header);
            break;
        }
        $body = [];
        foreach($XMLElement->item as $item){
            $row = [];
            foreach($item as $head => $value){
                array_push($row, $value);
            }
            array_push($body, $row);
        }
        return $body;
    }
}