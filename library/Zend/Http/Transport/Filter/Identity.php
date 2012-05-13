<?php

namespace Zend\Http\Transport\Filter;

class Identity implements Filter
{
    /**
     * This filter does nothing
     * 
     * @see Zend\Http\Transport\Filter.Deflate::filter()
     */
    public function filter($content)
    {
        return $content;
    }
}