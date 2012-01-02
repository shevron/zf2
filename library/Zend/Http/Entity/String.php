<?php

namespace Zend\Http\Entity;

use Zend\Http\Exception;

class String extends Entity implements Writable, Rewindable
{
    /**
     * Temporary data stream
     *
     * @var resource
     */
    protected $data = '';

    /**
     * Position on string
     *
     * @var unknown_type
     */
    protected $isRead = false;

    public function read()
    {
        if ($this->isRead) {
            return false;
        } else {
            $this->isRead = true;
            return $this->data;
        }
    }

    public function write($data)
    {
        $dataLen = strlen($data);
        $this->data .= $data;
        return $dataLen;
    }
    
    public function fromString($content)
    {
        $this->write($content);
    }

    public function rewind()
    {
        $this->isRead = false;
    }
}
