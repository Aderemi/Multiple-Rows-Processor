<?php
/**
 * Created by PhpStorm.
 * User: Akins
 * Date: 10/31/2018
 * Time: 9:15 PM
 */

namespace MultipleRows\Utilities\ProcessorTraits;


trait NonMarkUpTrait
{
    /**
     * Prepare the content for this process to consume
     * @param string $content
     */
    protected function prepareContent(string $content)
    {
        $csv = explode("\n", $content);
        $ret = [];
        foreach ($csv as $key => $line)
        {
            $split = $this->sanitize(str_getcsv($line, $this->delimiter));

            if(count($split) == 1 && empty($split[0])) continue;
            $ret[$key] = $split;
        }
        $this->prepareHeader(array_shift($ret));
        $this->data = $ret;
    }
}