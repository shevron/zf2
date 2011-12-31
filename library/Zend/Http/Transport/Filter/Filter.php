<?php

namespace Zend\Http\Transport\Filter;

interface Filter
{
    public function filter($content);
}