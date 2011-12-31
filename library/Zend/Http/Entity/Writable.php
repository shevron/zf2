<?php

namespace Zend\Http\Entity;

interface Writable
{
    public function write($chunk);

    public function setInputEncoding($encoding);
}