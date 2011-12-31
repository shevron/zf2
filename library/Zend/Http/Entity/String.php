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

    /**
     * Number of bytes to skip on next read.
     *
     * This is more or less only used when handling a 'gzip' encoded input stream
     *
     * @var integer
     */
    protected $skipBytes = 0;

    /**
     * Should written data be passed through a deflate filter?
     *
     * @var boolean
     */
    protected $deflate = false;

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

        if ($this->deflate) {
            if ($dataLen > $this->skipBytes) {
                $data = substr($data, $this->skipBytes);
                $this->skipBytes = 0;
            } else {
                $this->skipBytes -= $dataLen;
                return $dataLen;
            }

            $data = gzinflate($data);
        }

        $this->data .= $data;
        return $dataLen;
    }

    public function setInputContentEncoding($encoding)
    {
        switch ($encoding) {
            case 'gzip':
                // read the gzip header, then handle just like 'deflate' encoding
                $this->skipBytes = 10;

            case 'deflate':
                $this->deflate = true;
                stream_filter_append($this->stream, 'zlib.inflate', STREAM_FILTER_WRITE);
                break;

            case false:
            case null:
                $this->skipBytes = 0;
                $this->deflate = false;
                break;

            default:
                // TODO: does 'Body' need it's own NS of exceptions?
                throw new Exception\InvalidArgumentException("Unable to handle input content encoding: $encoding");
                break;
        }
    }

    public function rewind()
    {
        $this->isRead = false;
    }
}
