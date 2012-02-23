<?php

namespace Zend\Http\Entity;

use Zend\Http\Exception;

class String extends Entity implements Writable, Rewindable
{
    /**
     * Entity content
     *
     * @var string
     */
    protected $data = '';

    /**
     * Position on string
     *
     * @var boolean
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

    /**
     * Write data to the stream 
     * 
     * @see Zend\Http\Entity.Writable::write()
     */
    public function write($data)
    {
        $dataLen = strlen($data);
        $this->data .= $data;
        return $dataLen;
    }
    
    /**
     * Set entity contents from string
     *
     * @see Zend\Http\Entity\Writable::fromString()
     */
    public function fromString($content)
    {
        $this->write($content);
    }

    /**
     * Rewind entity 
     * 
     * @see Zend\Http\Entity.Rewindable::rewind()
     */
    public function rewind()
    {
        $this->isRead = false;
    }
}
