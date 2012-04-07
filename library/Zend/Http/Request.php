<?php

namespace Zend\Http;

use Zend\Stdlib\RequestDescription,
    Zend\Stdlib\Message,
    Zend\Stdlib\ParametersDescription,
    Zend\Stdlib\Parameters,
    Zend\Uri\Http as HttpUri;

class Request extends Message implements RequestDescription
{

    /**#@+
     * @const string METHOD constant names
     */
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_GET     = 'GET';
    const METHOD_HEAD    = 'HEAD';
    const METHOD_POST    = 'POST';
    const METHOD_PUT     = 'PUT';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_TRACE   = 'TRACE';
    const METHOD_CONNECT = 'CONNECT';
    /**#@-*/

    /**#@+
     * @const string Version constant numbers
     */
    const VERSION_11 = '1.1';
    const VERSION_10 = '1.0';
    /**#@-*/

    /**
     * @var string
     */
    protected $method = self::METHOD_GET;

    /**
     * @var Zend\Uri\Http
     */
    protected $uri = null;

    /**
     * @var string
     */
    protected $version = self::VERSION_11;

    /**
     * @var \Zend\Stdlib\ParametersDescription
     */
    protected $queryParams = null;

    /**
     * @var \Zend\Stdlib\ParametersDescription
     */
    protected $postParams = null;

    /**
     * @var string|\Zend\Http\Headers
     */
    protected $headers = null;

    /**
     * Create a new Request object, optionally setting the request URI
     *
     * @param \Zend\Uri\Http|string $uri
     */
    public function __construct($uri = null)
    {
        if ($uri) {
            $this->setUri($uri);
        }
    }

    /**
     * A factory that produces a Request object from a well-formed Http Request string
     *
     * @param string $string
     * @return \Zend\Http\Request
     */
    public static function fromString($string)
    {
        $request = new static();

        $lines = preg_split('/\r\n/', $string);

        // first line must be Method/Uri/Version string
        $matches = null;
        $regex = '^(?P<method>\S+)\s(?<uri>[^ ]*)(?:\sHTTP\/(?<version>\d+\.\d+)){0,1}';
        $firstLine = array_shift($lines);
        if (!preg_match('#' . $regex . '#', $firstLine, $matches)) {
            throw new Exception\InvalidArgumentException('A valid request line was not found in the provided string');
        }

        $request->setMethod($matches['method']);
        $request->setUri($matches['uri']);

        if ($matches['version']) {
            $request->setVersion($matches['version']);
        }

        if (count($lines) == 0) {
            return $request;
        }

        $isHeader = true;
        $headers = $rawBody = array();
        while ($lines) {
            $nextLine = array_shift($lines);
            if ($nextLine == '') {
                $isHeader = false;
                continue;
            }
            if ($isHeader) {
                $headers[] .= $nextLine;
            } else {
                $rawBody[] .= $nextLine;
            }
        }

        if ($headers) {
            $request->headers = implode("\r\n", $headers);
        }

        if ($rawBody) {
            $request->setContent(implode("\r\n", $rawBody));
        }

        return $request;
    }

    /**
     * Set the method for this request
     *
     * @param string $method
     * @return Request
     */
    public function setMethod($method)
    {
        if (! is_string($method)) {
            throw new Exception\InvalidArgumentException('Invalid HTTP method passed: expecting a string');
        }

        // For known methods, set uppercase form
        $upperMethod = strtoupper($method);
        if (defined('static::METHOD_' . $upperMethod)) {
            $this->method = $upperMethod;

        // For custom methods validate and set as is
        } else {
            if (! static::validateRequestMethod($method)) {
                throw new Exception\InvalidArgumentException("Invalid HTTP method '$method' passed");
            }

            $this->method = $method;
        }

        return $this;
    }

    /**
     * Return the method for this request
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set the URL for this request.
     *
     * If an object is provided, it will be copied.
     *
     * @param  string|Zend\Uri\Http $uri
     * @return Zend\Http\Request
     */
    public function setUri($uri)
    {
        $this->uri = new HttpUri($uri);
        return $this;
    }

    /**
     * Return the URI for this request object as an instance of Zend\Uri\Http
     *
     * @return HttpUri
     */
    public function uri()
    {
        return $this->uri;
    }

    /**
     * Set the HTTP version for this object, one of 1.0 or 1.1 (Request::VERSION_10, Request::VERSION_11)
     *
     * @throws Exception\InvalidArgumentException
     * @param string $version (Must be 1.0 or 1.1)
     * @return Request
     */
    public function setVersion($version)
    {
        if (!in_array($version, array(self::VERSION_10, self::VERSION_11))) {
            throw new Exception\InvalidArgumentException('Version provided is not a valid version for this HTTP request object');
        }
        $this->version = $version;
        return $this;
    }

    /**
     * Return the HTTP version for this request
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Provide an alternate Parameter Container implementation for query parameters in this object, (this is NOT the
     * primary API for value setting, for that see query())
     *
     * @param \Zend\Stdlib\ParametersDescription $query
     * @return Request
     */
    public function setQuery(ParametersDescription $query)
    {
        $this->queryParams = $query;
        return $this;
    }

    /**
     * Return the parameter container responsible for query parameters
     *
     * @return \Zend\Stdlib\ParametersDescription
     */
    public function query()
    {
        if ($this->queryParams === null) {
            $this->queryParams = new Parameters();
        }

        return $this->queryParams;
    }

    /**
     * Provide an alternate Parameter Container implementation for post parameters in this object, (this is NOT the
     * primary API for value setting, for that see post())
     *
     * @param \Zend\Stdlib\ParametersDescription $post
     * @return Request
     */
    public function setPost(ParametersDescription $post)
    {
        if (! $this->content) {
            $this->setContent(new Entity\UrlEncodedFormData());
        }

        if ($this->content instanceof Entity\FormDataHandler) {
            $this->content->setFormData($post);
        }

        $this->postParams = $post;
        return $this;
    }

    /**
     * Return the parameter container responsible for post parameters
     *
     * @return \Zend\Stdlib\ParametersDescription
     */
    public function post()
    {
        if ($this->postParams === null) {
            $this->setPost(new Parameters());
        }

        return $this->postParams;
    }

    /**
     * Return the Cookie header, this is the same as calling $request->headers()->get('Cookie');
     *
     * @convenience $request->headers()->get('Cookie');
     * @return Header\Cookie
     */
    public function cookie()
    {
        return $this->headers()->get('Cookie');
    }

    /**
     * Provide an alternate Parameter Container implementation for headers in this object, (this is NOT the
     * primary API for value setting, for that see headers())
     *
     * @param \Zend\Http\Headers $headers
     * @return \Zend\Http\Request
     */
    public function setHeaders(Headers $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Return the header container responsible for headers
     *
     * @return \Zend\Http\Headers
     */
    public function headers()
    {
        if ($this->headers === null || is_string($this->headers)) {
            // this is only here for fromString lazy loading
            $this->headers = (is_string($this->headers)) ? Headers::fromString($this->headers) : new Headers();
        }

        return $this->headers;
    }

    /**
     * Set message content
     *
     * @param  mixed $value
     * @return Message
     */
    public function setContent($value)
    {
        $ret = parent::setContent($value);

        if ($value instanceof Entity\FormDataHandler) {
            $value->setFormData($this->post());
        }

        return $ret;
    }

    /**
     * Is this an OPTIONS method request?
     *
     * @return bool
     */
    public function isOptions()
    {
        return ($this->method === self::METHOD_OPTIONS);
    }

    /**
     * Is this a GET method request?
     *
     * @return bool
     */
    public function isGet()
    {
        return ($this->method === self::METHOD_GET);
    }

    /**
     * Is this a HEAD method request?
     *
     * @return bool
     */
    public function isHead()
    {
        return ($this->method === self::METHOD_HEAD);
    }

    /**
     * Is this a POST method request?
     *
     * @return bool
     */
    public function isPost()
    {
        return ($this->method === self::METHOD_POST);
    }

    /**
     * Is this a PUT method request?
     *
     * @return bool
     */
    public function isPut()
    {
        return ($this->method === self::METHOD_PUT);
    }

    /**
     * Is this a DELETE method request?
     *
     * @return bool
     */
    public function isDelete()
    {
        return ($this->method === self::METHOD_DELETE);
    }

    /**
     * Is this a TRACE method request?
     *
     * @return bool
     */
    public function isTrace()
    {
        return ($this->method === self::METHOD_TRACE);
    }

    /**
     * Is this a CONNECT method request?
     *
     * @return bool
     */
    public function isConnect()
    {
        return ($this->method === self::METHOD_CONNECT);
    }

    /**
     * Return the formatted request line (first line) for this http request
     *
     * @return string
     */
    public function renderRequestLine()
    {
        return $this->method . ' ' . (string) $this->uri . ' HTTP/' . $this->version;
    }

    /**
     * @return string
     */
    public function toString()
    {
        $str = $this->renderRequestLine() . "\r\n";
        if ($this->headers) {
            $str .= $this->headers->toString();
        }
        $str .= "\r\n";
        $str .= $this->getContent();
        return $str;
    }

    /**
     * Allow PHP casting of this object
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Validate an HTTP request method
     *
     * According to the HTTP/1.1 standard, valid request methods are composed
     * of 1 or more TOKEN characters, which are printable ASCII characters
     * other than "separator" characters
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec5.html#sec5.1.1
     * @param  string $method
     * @return boolean
     */
    public static function validateRequestMethod($method)
    {
        return (bool) preg_match(
            '/^[^\x00-\x1f\x7f-\xff\(\)<>@,;:\\\\"<>\/\[\]\?={}\s]+$/', $method
        );
    }
}
