<?php

namespace Zend\Http\Entity;

use Zend\Http\Exception;

class SmartBuffer extends Entity implements Writable, Rewindable
{
    /**
     * Temporary data stream
     *
     * @var resource
     */
    protected $stream;

    /**
     * Maximal memory usage
     *
     * @var integer
     */
    protected $maxMemory = 4194304; // 4mb of memory

    /**
     * Read chunk size. Usually there is no need to modify this
     *
     * @var integer
     */
    protected $chunkSize = 4096;  // Read chunk size

    /**
     * Number of bytes to skip on next read.
     *
     * This is more or less only used when handling a 'gzip' encoded input stream
     *
     * @var integer
     */
    protected $skipBytes = 0;

    public function __construct(array $config = array())
    {
        if (isset($config['maxMemory'])) {
            $this->maxMemory = (int) $config['maxMemory'];
        }

        if (isset($config['chunkSize'])) {
            $this->chunkSize = (int) $config['chunkSize'];
        }

        $this->stream = fopen('php://temp/maxmemory:' . $this->maxMemory, 'r+');
    }

    public function read()
    {
        return fread($this->stream, $this->chunkSize);
    }

    public function write($data)
    {
        if ($this->skipBytes) {
            $dataLen = strlen($data);
            if ($dataLen > $this->skipBytes) {
                $data = substr($data, $this->skipBytes);
                $this->skipBytes = 0;
            } else {
                $this->skipBytes -= $dataLen;
                return $dataLen;
            }
        }

        return fwrite($this->stream, $data) + $this->skipBytes;
    }

    public function setInputContentEncoding($encoding)
    {
        switch ($encoding) {
            case 'gzip':
                // read the gzip header, then handle just like 'deflate' encoding
                $this->skipBytes = 10;

            case 'deflate':
                stream_filter_append($this->stream, 'zlib.inflate', STREAM_FILTER_WRITE);
                break;

            default:
                // TODO: does 'Body' need it's own NS of exceptions?
                throw new Exception\InvalidArgumentException("Unable to handle input content encoding: $encoding");
                break;
        }
    }

    public function rewind()
    {
        fseek($this->stream, 0);
    }

    public function __destruct()
    {
        fclose($this->stream);
    }
}
