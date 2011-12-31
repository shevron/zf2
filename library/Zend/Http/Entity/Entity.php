<?php

namespace Zend\Http\Entity;

abstract class Entity
{
    abstract public function read();

    public function toString()
    {
        if ($this instanceof Rewindable) {
            $this->rewind();
        }

        $body = '';
        while (($chunk = $this->read()) != false) {
            $body .= $chunk;
        }

        return $body;
    }

    public function __toString()
    {
        return $this->toString();
    }
}