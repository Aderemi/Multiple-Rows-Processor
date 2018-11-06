<?php
/**
 * Created by PhpStorm.
 * User: Akins
 * Date: 11/3/2018
 * Time: 8:23 AM
 */

namespace MultipleRows\Utilities\ProcessorTraits;


trait ProcessorBaseTrait
{
    protected function prepareHeader(array $header)
    {
        $this->idHeaderLocation = array_search($this->getUniqueIDField(), $header);
        if($this->idHeaderLocation < 0){
            $this->addError("Loaded file does not contain unique Identifier field");
        }

        if(in_array("", $header)){
            $this->addError("One of the header's elements is empty");
        }
        $this->headers = $header;
    }

    protected function sanitize(array $input)
    {
        $sanitized = array_map(function($elem) {
            return trim($elem);
        }, $input);
        return $sanitized;
    }
}