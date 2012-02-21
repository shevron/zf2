<?php

namespace Zend\Http\Transport;

use Zend\Http\Request,
    Zend\Http\Response,
    Zend\Http\Entity\Entity,
    Zend\Http\Entity\Writable as WritableEntity,
    Zend\Http\Header\ContentEncoding as CEHeader,
    Zend\Log\Logger;

class Socket implements Transport
{
    /**
     * Content encoding filters registry
     *
     * @var array
     */
    static protected $contentEncodingFilters = array(
        'identity' => 'Zend\Http\Transport\Filter\Identity',
        'gzip'     => 'Zend\Http\Transport\Filter\Gzip',
        'deflate'  => 'Zend\Http\Transport\Filter\Deflate',
    );

    /**
     * Options object
     *
     * @var Zend\Http\Transport\SocketOptions
     */
    protected $options = null;

    /**
     * Socket client resource
     *
     * @var resource
     */
    protected $socket = null;

    /**
     * Indicates if we are connected and to what server
     *
     * @var string
     */
    protected $connectedTo = null;

    /**
     * PHP Stream context
     *
     * This can be used to apply additional options on the PHP stream wrapper
     * used to connect to the server - especially useful when HTTPS is used as
     * advanced SSL options can be defined.
     *
     * @var resource
     */
    protected $context = null;

    /**
     * A content encoding filter object
     *
     * Content encoding filters are used to handle Content-Encoding of the
     * HTTP response body
     *
     * @var null|Zend\Http\Transport\Filter\Filter
     */
    protected $contentEncodingFilter = null;

    /**
     * Create a new socket transport object
     *
     * @param array $config
     */
    public function __construct(Options $options = null)
    {
        if ($options) {
            $this->setOptions($options);
        } else {
            $this->options = new SocketOptions();
        }
    }

    /**
     * Set options for the socket transport object
     *
     * @param  Zend\Http\Transport\SocketOptions $options
     * @return Zend\Http\Transport\Socket
     */
    public function setOptions(Options $options)
    {
        if (! $options instanceof SocketOptions) {
            $options = new SocketOptions($options);
        }

        $this->options = $options;
        return $this;
    }

    /**
     * Get options for the socket transport object
     *
     * @return Zend\Http\Transport\SocketOptions
     * @see Zend\Http\Transport.Transport::getOptions()
     */
    public function getOptions()
    {
        return $this->options;
    }

	/**
     * Set the stream context for the TCP connection to the server
     *
     * Can accept either a pre-existing stream context resource, or an array
     * of stream options, similar to the options array passed to the
     * stream_context_create() PHP function. In such case a new stream context
     * will be created using the passed options.
     *
     * @param  mixed $context Stream context or array of context options
     * @return \Zend\Http\Client\Transport\Socket
     */
    public function setStreamContext($context)
    {
        if (is_resource($context) && get_resource_type($context) == 'stream-context') {
            $this->context = $context;

        } elseif (is_array($context)) {
            $this->context = stream_context_create($context);

        } else {
            // Invalid parameter
            throw new Exception\InvalidArgumentException(
                "Expecting either a stream context resource or array, got " . gettype($context)
            );
        }

        return $this;
    }

    /**
     * Get the stream context for the TCP connection to the server.
     *
     * If no stream context is set, will create a default one.
     *
     * @return resource
     */
    public function getStreamContext()
    {
        if (! $this->context) {
            $this->context = stream_context_create();
        }

        return $this->context;
    }

    /**
     * Send HTTP request and return the response
     *
     * @see              Zend\Http\Transport\Transport::send()
     * @param  $request  Zend\Http\Request
     * @param  $response Zend\Http\Response
     * @return           Zend\Http\Response
     */
    public function send(Request $request, Response $response = null)
    {
        $this->log("Sending {$request->getMethod()} request to {$request->uri()}", Logger::NOTICE);

        // Prepare request
        $this->prepareRequest($request);

        // Connect to remote server
        $this->connect($request);

        // Send request
        $this->sendRequest($request);

        // Read response
        $response = $this->readResponse($response);

        if (! $this->options->getKeepAlive() ||
           ($response->headers()->has('connection') &&
            $response->headers()->get('connection')->getFieldValue() == 'close')) {
            $this->disconnect();
        }

        return $response;
    }

    /**
     * Prepare any request headers that are affected by the transport
     *
     * @param Zend\Http\Request $request
     */
    protected function prepareRequest(Request $request)
    {
        if ($this->options->getKeepAlive()) {
            $request->headers()->addHeaderLine('Connection', 'keep-alive');
        } else {
            $request->headers()->addHeaderLine('Connection', 'close');
        }

        if (! $request->headers()->has('host')) {
            $host = $request->uri()->getHost();
            if ($host) {
                $scheme = $request->uri()->getScheme();
                $port = $request->uri()->getPort();
                if (($scheme == 'http' && $port != 80) ||
                    ($scheme == 'https' && $port != 443)) {
                    $host .= ":$port";
                }
            }

            $request->headers()->addHeaderLine('Host', $host);
        }
    }

    /**
     * Connect to the remote server
     *
     * @param  Zend\Http\Request $request
     * @throws Exception\ConfigurationException
     * @throws Exception\ConnectionException
     */
    protected function connect(Request $request)
    {
        $host = $request->uri()->getHost();
        $port = $request->uri()->getPort();
        $isSecure = ($request->uri()->getScheme() == 'https');
        $wrapper  = 'tcp://';

        if (! $port) {
            if ($isSecure) {
                $port = 443;
            } else {
                $port = 80;
            }
        }

        $remoteServer = "$host:$port";

        if ($this->connectedTo && $this->connectedTo != $remoteServer) {
            $this->disconnect();
        }

        if (! $this->connectedTo) {
            $context = $this->getStreamContext();
            if ($isSecure) {
                // Handle SSL options
                if ($this->options->getSslPassphrase()) {
                    if (! stream_context_set_option($context, 'ssl', 'passphrase', $this->options->getSslPassphrase())) {
                        throw new Exception\ConfigurationException('Unable to set SSL passphrase option');
                    }
                }

                if ($this->options->getSslCertificate()) {
                    if (! stream_context_set_option($context, 'ssl', 'local_cert', $this->options->getSslCertificate())) {
                        throw new Exception\ConfigurationException('Unable to set SSL local_cert option');
                    }
                }

                if (! stream_context_set_option($context, 'ssl', 'verify_peer', $this->options->getSslVerifyPeer())) {
                    throw new Exception\ConfigurationException('Unable to set SSL verify_peer option');
                }

                if ($this->options->getSslCaFile()) {
                    if (! stream_context_set_option($context, 'ssl', 'cafile', $this->options->getSslCaFile())) {
                        throw new Exception\ConfigurationException('Unable to set SSL cafile option');
                    }
                }
            }

            $this->socket = @stream_socket_client(
                $remoteServer, $errno, $errstr,
                $this->options->getTimeout(), STREAM_CLIENT_CONNECT, $context
            );

            if (! $this->socket) {
                throw new Exception\ConnectionException("Unable to connect to $remoteServer: [$errno] $errstr");
            }

            $this->log("TCP connection to $remoteServer established", Logger::INFO);

            $this->connectedTo = $remoteServer;

            if ($isSecure) {
                if (! @stream_socket_enable_crypto($this->socket, true, $this->options->getSslCryptoType())) {
                    $errorString = '';
                    while(($sslError = openssl_error_string()) != false) {
                        $errorString .= "; SSL error: $sslError";
                    }
                    $this->disconnect();
                    throw new Exception\ConnectionException("Unable to enable crypto on TCP connection {$remoteServer}: $errorString");
                }

                $this->log("Crypto layer (HTTPS) enabled on connection", Logger::INFO);
            }

        } else {
            $this->log("Already connected to $remoteServer, not reconnecting", Logger::DEBUG);
        }
    }

    /**
     * Send HTTP request to the server
     *
     * @param  Zend\Http\Request $request
     * @throws Exception\ConnectionException
     */
    protected function sendRequest(Request $request)
    {
        // Write request headers
        $this->log("Sending request headers", Logger::INFO);

        $requestUri = $request->uri()->getPath();
        if (! $requestUri) $requestUri = '/';

        if ($query = $request->uri()->getQuery()) {
            $requestUri .= "?$query";
        }

    	$headers = $request->getMethod() . " " .
    	           $requestUri . " " .
    	           "HTTP/" . $request->getVersion() . "\r\n" .
    			   $request->headers()->toString() . "\r\n";

        if (! fwrite($this->socket, $headers)) {
            throw new Exception\ConnectionException("Failed writing request headers to $this->connectedTo");
        }

        $body = $request->getContent();
        if ($body) {
            $this->log("Sending request body", Logger::INFO);
            $this->sendBody($body);
        }
    }

    /**
     * Send HTTP request body to the server
     *
     * @param  string|Zend\Http\Entity\Entity $body
     * @throws Exception\ConnectionException
     */
    protected function sendBody($body)
    {
        if ($body instanceof Entity) {
            while (($chunk = $body->read()) != null) {
                if (! fwrite($this->socket, $chunk)) {
                    throw new Exception\ConnectionException("Failed writing request body chunk to $this->connectedTo");
                }
            }
        } else {
            $result = fwrite($this->socket, $body);
            if ($result === false) {
                throw new Exception\ConnectionException("Failed writing request body chunk to $this->connectedTo");
            }
        }
    }

    /**
     * Read HTTP response from server
     *
     * @param  $response Zend\Http\Response
     * @return           Zend\Http\Response
     * @throws           Exception\ConnectionException
     * @throws           Exception\ProtocolException
     */
    protected function readResponse(Response $response = null)
    {
        $this->log("Reading response from server", Logger::INFO);

        if (! $response instanceof Response) {
            $responseClass = $this->options->getDefaultResponseClass();
            $response = new $responseClass;
        }

        // Read status line
        $line = $this->readLine();
        if (! $line) {
            throw new Exception\ProtocolException("Failed reading response status line from $this->connectedTo");
        }
        $line = trim($line);
        if (! preg_match('|^HTTP/([\d\.]+) (\d+) (.+)$|m', $line, $matches)) {
            throw new Exception\ProtocolException("Response status line is malformed: '$line'");
        }
        $this->log("Got HTTP response status line: $line", Logger::DEBUG);

        $response->setVersion($matches[1])
                 ->setStatusCode($matches[2])
                 ->setReasonPhrase($matches[3]);

        $this->readResponseHeaders($response);

        $this->readResponseBody($response);

        return $response;
    }

    /**
     * Read HTTP response headers from server
     *
     * @param  Zend\Http\Response $response
     * @throws Exception\ConnectionException
     */
    protected function readResponseHeaders(Response $response)
    {
        $header = null;

        $this->log("Reading response headers", Logger::DEBUG);
        while (! feof($this->socket)) {
            $line = $this->readLine();
            if ($line === false) {
                throw new Exception\ConnectionException("Failed reading response headers from $this->connectedTo");
            }

            $line = rtrim($line);

            if ($line) {
                // TODO: check for wrapped headers
                list($name, $value) = explode(':', $line, 2);
                $name = trim($name);
                $value = trim($value);

                $response->headers()->addHeaderLine($name, $value);

                $this->log("Got HTTP response header: $name", Logger::DEBUG);
            } else {
                break;
            }
        }
    }

    /**
     * Read HTTP response body from server
     *
     * @param  Zend\Http\Response $response
     * @throws Exception\ConfigurationException
     * @throws Exception\ProtocolException
     */
    protected function readResponseBody(Response $response)
    {
        /*
        $body = $response->getBody();
        if (! $body) {
            $bodyClass = $this->options->getDefaultResponseBodyClass();
            $body = new $bodyClass;
            $response->setBody($body);
        }

        $this->log("Reading repsonse body (using body class " . get_class($body) . ")", Logger::DEBUG);

        if (! $body instanceof WritableEntity) {
            throw new Exception\ConfigurationException("Response body object is not writable");
        }
        */

        $this->handleContentEncoding($response, $response->headers()->get('content-encoding'));

        // Read body based on provided headers
        if ($response->headers()->has('transfer-encoding')) {
            $transferEncoding = $response->headers()->get('transfer-encoding');
            if ($transferEncoding->getFieldValue() != 'chunked') {
                throw new Exception\ProtocolException("Unknown content transfer encoding: {$transferEncoding->getFieldValue()}");
            }

            // Read chunked body
            $this->log("Reading repsonse body using chunked transfer encoding", Logger::INFO);
            $response->setContent($this->readChunkedBody());
            $response->headers()->removeHeader($transferEncoding);

        } elseif ($response->headers()->has('content-length')) {
            $length = (int) $response->headers()->get('content-length')->getFieldValue();
            $this->log("Reading repsonse body based on provided length of $length bytes", Logger::INFO);
            $response->setContent($this->readBodyContentLength($length));

        } else {
            // Fallback: read until end of file
            $this->log("Reading repsonse body until server closes connection", Logger::INFO);
            $body = '';
            while (! feof($this->socket)) {
                $chunk = $this->readLength(4096);
                if ($chunk !== false) {
                    $body .= $this->contentEncodingFilter->filter($chunk);
                }
            }
            $response->setContent($body);
        }

        // Remove content encoding filter, if set
        if ($this->contentEncodingFilter) {
            $this->contentEncodingFilter = null;
        }
    }

    protected function handleContentEncoding(Response $response, $header)
    {
        if (! $header) {
            $this->contentEncodingFilter = new Filter\Identity();
            return;
        }

        $contentEnc = $header->getFieldValue();
        $this->log("Applying content encoding filter for '$contentEnc'", Logger::DEBUG);

        if (isset(static::$contentEncodingFilters[$contentEnc])) {
            $this->contentEncodingFilter = new static::$contentEncodingFilters[$contentEnc];
            $response->headers()->removeHeader($header);
        } else {
            $this->contentEncodingFilter = new Filter\Identity();
            $this->log("Unknown Content-Encoding: $contentEnc", Logger::NOTICE);
        }
    }

    /**
     * Read a 'chunked' transfer-encoded body
     *
     * @return string $body
     */
    protected function readChunkedBody()
    {
        $this->log("reading chunked body", Logger::DEBUG);
        $body = '';
        do {
            $nextChunk = $this->readNextChunkSize();
            if ($nextChunk > 0) {
                $this->log("reading next chunk of $nextChunk bytes", Logger::DEBUG);
                $body .= $this->readBodyContentLength($nextChunk);
            }
            // Read CRLF before next chunk
            $this->readLine();

        } while($nextChunk > 0);

        return $body;
    }

    /**
     * Read the next chunk size in a 'chunked' transfer-encoded body
     *
     * @throws Exception\ProtocolException
     */
    protected function readNextChunkSize()
    {
        $chunkLine = $this->readLine();
        if (! $chunkLine) {
            throw new Exception\ProtocolException("Unable to read next chunk size");
        }
        $chunkSize = strtok($chunkLine, "; \t\r\n");

        $this->log("got next chunk size: 0x$chunkSize", Logger::DEBUG);

        if (! ctype_xdigit($chunkSize)) {
            throw new Exception\ProtocolException("Unexpected chunk size value in response: $chunkSize");
        }

        return hexdec($chunkSize);
    }

    /**
     * Read the request body based on specified content length
     *
     * @param  integer $length
     * @return string $body
     * @throws Exception\ProtocolException
     */
    protected function readBodyContentLength($length)
    {
        $body = '';
        $readUntil = ftell($this->socket) + $length;
        for ($toRead = $length; $toRead > 0; $toRead = $readUntil - ftell($this->socket)) {
            if (feof($this->socket)) {
                throw new Exception\ProtocolException("Unexpected end of file, still expecting $toRead bytes");
            }
            $chunk = $this->readLength($toRead);
            if ($chunk !== false) {
                $body .= $this->contentEncodingFilter->filter($chunk);
            } else {
                // TODO: handle error
                break;
            }
        }

        return $body;
    }

    /**
     * Disconnect from remote server, if connected
     *
     * @return void
     */
    protected function disconnect()
    {
        if ($this->socket) {
            fclose($this->socket);
        }

        $this->connectedTo = null;
    }

    /**
     * Log a message if logger was set
     *
     * @param string  $message
     * @param integer $priority
     */
    protected function log($message, $priority)
    {

    }

    /**
     * Read a line from the server
     *
     * @return string | boolean
     */
    protected function readLine()
    {
        return fgets($this->socket);
    }

    /**
     * Read specified number of bytes from the server
     *
     * @param integer $length
     * @return string | boolean
     */
    protected function readLength($length)
    {
        return fread($this->socket, $length);
    }
}