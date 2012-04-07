<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend\Http
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Http;

use Zend\Uri\Http as HttpUri,
    Zend\Http\Header\Cookie,
    Zend\Http\Header\SetCookie,
    Zend\Http\CookieStore\AbstractCookieStore,
    Zend\Http\Transport\Transport as HttpTransport,
    Zend\Stdlib\Parameters,
    Zend\Stdlib\ParametersDescription,
    Zend\Stdlib\Dispatchable,
    Zend\Stdlib\RequestDescription,
    Zend\Stdlib\ResponseDescription;

/**
 * Http client
 *
 * @category   Zend
 * @package    Zend\Http
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Client implements Dispatchable
{
    /**#@+
     * @const string Supported HTTP Authentication methods
     */
    const AUTH_BASIC  = 'basic';
    const AUTH_DIGEST = 'digest';  // not implemented yet
    /**#@-*/

    /**#@+
     * @const string DIGEST Authentication
     */
    const DIGEST_REALM  = 'realm';
    const DIGEST_QOP    = 'qop';
    const DIGEST_NONCE  = 'nonce';
    const DIGEST_OPAQUE = 'opaque';
    const DIGEST_NC     = 'nc';
    const DIGEST_CNONCE = 'cnonce';
    /**#@-*/

    /**
     * Default transport adapter class
     *
     * @var string
     */
    static protected $defaultTransport = 'Zend\Http\Transport\Socket';

    /**
     * Default cookie storage container class
     *
     * @var string
     */
    static protected $defaultCookieStore = 'Zend\Http\CookieStore\Simple';

    /**
     * @var Transport
     */
    protected $transport;

    /**
     * @var array
     */
    protected $auth = array();

    /**
     * Cookie storage object
     *
     * @var Zend\Http\CookieStore\AbstractCookieStore
     */
    protected $cookieStore = null;

    /**
     * Global headers
     *
     * Global headers are headers that are set on all requests which are sent
     * by the client. These could include, for example, the 'User-agent' header
     *
     * @var Headers
     */
    protected $headers = null;

    /**
     * @var int
     */
    protected $redirectCounter = 0;

    /**
     * Options object
     *
     * @var Zend\Http\ClientOptions
     */
    protected $options = null;

    /**
     * Constructor
     *
     * @param string $uri
     * @param Zend\Http\ClientOptions $options
     */
    public function __construct(ClientOptions $options = null)
    {
        if ($options) {
            $this->setOptions($options);
        } else {
            $this->options = new ClientOptions();
        }
    }

    /**
     * Set configuration options for this HTTP client
     *
     * @param  Zend\Http\ClientOptions $options
     * @return Zend\Http\Client
     * @throws Client\Exception
     */
    public function setOptions(ClientOptions $options)
    {
        $this->options = $options;

        // Pass configuration options to the adapter if it exists
        if ($this->transport instanceof HttpTransport && $options->hasTransportOptions()) {
            $this->transport->setOptions($options->getTransportOptions());
        }

        return $this;
    }

    /**
     * Get options object
     *
     * @return \Zend\Http\ClientOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set the transport adapter
     *
     * @param  \Zend\Http\Transport\Transport|string transportort
     * @return \Zend\Http\Client
     * @throws \Zend\Http\Client\Exception
     */
    public function setTransport(HttpTransport $transport)
    {
        $this->transport = $transport;

        if ($this->options->hasTransportOptions()) {
            $this->transport->setOptions($this->options->getTransportOptions());
        }

        return $this;
    }

    /**
     * Get the transport adapter
     *
     * @return \Zend\Http\Transport\Transport
     */
    public function getTransport()
    {
        if (! $this->transport) {
            $this->transport = new static::$defaultTransport();
            $this->transport->setOptions($this->options->getTransportOptions());
        }

        return $this->transport;
    }

    /**
     * Get the redirections count
     *
     * @return integer
     */
    public function getRedirectionsCount()
    {
        return $this->redirectCounter;
    }

    /**
     * Return the current cookie storage object
     *
     * @return Zend\Http\CookieStore\AbstractCookieStore
     */
    public function getCookieStore()
    {
        if (! $this->cookieStore) {
            $this->cookieStore = new static::$defaultCookieStore;
        }

        return $this->cookieStore;
    }

    /**
     * Set an array of cookies
     *
     * @param  Zend\Http\CookieStore\AbstractCookieStore $cookieStore
     * @return Zend\Http\Client
     */
    public function setCookieStore(AbstractCookieStore $cookieStore)
    {
        $this->cookieStore = $cookieStore;
        return $this;
    }

    /**
     * Get globl headers container
     *
     * @return \Zend\Http\Headers
     */
    public function headers()
    {
        if (! $this->headers) {
            $this->headers = new Headers();
        }

        return $this->headers;
    }

    /**
     * Set the global headers container
     *
     * @param  \Zend\Http\Headers $headers
     * @return \Zend\Http\Client
     */
    public function setHeaders($headers)
    {
        if (is_array($headers)) {
            $newHeaders = new Headers();
            $newHeaders->addHeaders($headers);
            $headers = $newHeaders;
        }

        if (! $headers instanceof Headers) {
            throw new Exception\InvalidArgumentException("Headers should be either an array or a Headers object");
        }

        $this->headers = $headers;
        return $this;
    }

    /**
     * Create a HTTP authentication "Authorization:" header according to the
     * specified user, password and authentication method.
     *
     * @param string $user
     * @param string $password
     * @param string $type
     * @return Client
     */
    public function setAuth($user, $password, $type = self::AUTH_BASIC)
    {
        if (!defined('self::AUTH_' . strtoupper($type))) {
            throw new Exception\InvalidArgumentException("Invalid or not supported authentication type: '$type'");
        }
        if (empty($user) || empty($password)) {
            throw new Exception\InvalidArgumentException("The username and the password cannot be empty");
        }

        $this->auth = array (
            'user'     => $user,
            'password' => $password,
            'type'     => $type

        );

        return $this;
    }

    /**
     * Calculate the response value according to the HTTP authentication type
     *
     * @see http://www.faqs.org/rfcs/rfc2617.html
     * @param string $user
     * @param string $password
     * @param string $type
     * @param array $digest
     * @return string|boolean
     */
    protected function calcAuthDigest($user, $password, $type = self::AUTH_BASIC, $digest = array(), $entityBody = null)
    {
        if (!defined('self::AUTH_' . strtoupper($type))) {
            throw new Exception\InvalidArgumentException("Invalid or not supported authentication type: '$type'");
        }
        $response = false;
        switch(strtolower($type)) {
            case self::AUTH_BASIC :
                // In basic authentication, the user name cannot contain ":"
                if (strpos($user, ':') !== false) {
                    throw new Exception\InvalidArgumentException("The user name cannot contain ':' in Basic HTTP authentication");
                }
                $response = base64_encode($user . ':' . $password);
                break;
            case self::AUTH_DIGEST :
                if (empty($digest)) {
                    throw new Exception\InvalidArgumentException("The digest cannot be empty");
                }
                foreach ($digest as $key => $value) {
                    if (!defined('self::DIGEST_' . strtoupper($key))) {
                        throw new Exception\InvalidArgumentException("Invalid or not supported digest authentication parameter: '$key'");
                    }
                }
                $ha1 = md5($user . ':' . $digest['realm'] . ':' . $password);
                if (empty($digest['qop']) || strtolower($digest['qop']) == 'auth') {
                    $ha2 = md5($this->getMethod() . ':' . $this->getUri()->getPath());
                } elseif (strtolower($digest['qop']) == 'auth-int') {
                     if (empty($entityBody)) {
                        throw new Exception\InvalidArgumentException("I cannot use the auth-int digest authentication without the entity body");
                     }
                     $ha2 = md5($this->getMethod() . ':' . $this->getUri()->getPath() . ':' . md5($entityBody));
                }
                if (empty($digest['qop'])) {
                    $response = md5 ($ha1 . ':' . $digest['nonce'] . ':' . $ha2);
                } else {
                    $response = md5 ($ha1 . ':' . $digest['nonce'] . ':' . $digest['nc']
                                    . ':' . $digest['cnonce'] . ':' . $digest['qoc'] . ':' . $ha2);
                }
                break;
        }
        return $response;
    }

    /**
     * Dispatch
     *
     * @param RequestDescription $request
     * @param ResponseDescription $response
     * @return ResponseDescription
     */
    public function dispatch(RequestDescription $request, ResponseDescription $response = null)
    {
        $response = $this->send($request);
        return $response;
    }

    /**
     * Send HTTP request and return a response
     *
     * @param  Request $request
     * @return Response
     */
    public function send(Request $request, Response $response = null)
    {
        $this->redirectCounter = 0;
        $transport = $this->getTransport();

        while (true) {
            $this->prepareRequest($request);
            $response = $transport->send($request, $response);
            $this->handleResponse($response);

            // If we got redirected, look for the Location header
            if ($response->isRedirect() &&
                $response->headers()->has('Location') &&
                $this->redirectCounter < $this->options->getMaxRedirects()) {

                // Avoid problems with buggy servers that add whitespace at the
                // end of some headers
                $location = trim($response->headers()->get('Location')->getFieldValue());

                // Check whether we send the exact same request again, or drop the parameters
                // and send a GET request
                if ($response->getStatusCode() == 303 ||
                   ((! $this->options->getStrictRedirects()) && ($response->getStatusCode() == 302 ||
                       $response->getStatusCode() == 301))) {

                    $request->setMethod(Request::METHOD_GET);
                    $request->setContent(null);
                }

                $uri = HttpUri::merge($request->uri(), $location)->normalize();
                $request->setUri($uri);

                ++$this->redirectCounter;

            } else {
                // Not a redirection, no location or redirect limit reached
                break;
            }
        }

        return $response;
    }

    protected function prepareRequest(Request $request)
    {
        foreach($this->headers() as $header) {
            $key = $header->getFieldName();
            if (! $request->headers()->has($key)) {
                $request->headers()->addHeader($header);
            }
        }

        $existingCookies = $request->cookie(); /* @var Zend\Http\Header\Cookie */
        $cookieHeader = $this->getCookieStore()->getCookiesForRequest($request);

        if ($existingCookies) {
            $request->headers()->removeHeader($existingCookies);
            foreach($existingCookies as $key => $value) {
                $cookieHeader[$key] = $value;
            }
        }

        if (count($cookieHeader)) {
            $request->headers()->addHeader($cookieHeader);
        }

        // Handle POST content
        if ($request->getContent() instanceof Entity\FormDataHandler) {
            $request->getContent()->processRequestHeaders($request->headers());
        }
    }

    /**
     * Handle an incoming response
     *
     * Can perform some tasks on incoming responses, such as read and store set
     * cookies
     *
     * @param Zend\Http\Response $response
     */
    protected function handleResponse(Response $response)
    {
        $this->getCookieStore()->readCookiesFromResponse($response);
    }

    /**
     * HTTP DSL methods
     */

    /**
     * GET a URL
     *
     * This is a convenience method for quickly sending a GET request to the
     * provided URL, without manually creating a request object
     *
     * @param  Zend\Uri\Http|string $url
     * @return Zend\Http\Response
     */
    public function get($url)
    {
        $request = new Request($url);
        $request->setMethod(Request::METHOD_GET);

        return $this->send($request);
    }

    /**
     * Send an HTTP POST request to the specified URL with an optional payload
     *
     * @param  Zend\Uri\Http|string $url
     * @param  mixed                $content
     * @param  string               $contentType
     * @return Zend\Http\Response
     */
    public function post($url, $content = null, $contentType = null)
    {
        $request = new Request($url);
        $request->setMethod(Request::METHOD_POST);

        if ($content) {
            $request->setContent($content);
        }

        if ($contentType) {
            $request->headers()->addHeaderLine("Content-type", $contentType);
        } else {
            $request->headers()->addHeaderLine("Content-type", "application/octet-stream");
        }

        return $this->send($request);
    }

    /**
     * Send an HTTP PUT request to the specified URL with a payload
     *
     * @param  Zend\Uri\Http|string $url
     * @param  mixed                $content
     * @param  string               $contentType
     * @return Zend\Http\Response
     */
    public function put($url, $content, $contentType = null)
    {
        $request = new Request($url);
        $request->setMethod(Request::METHOD_PUT);

        if ($content) {
            $request->setContent($content);
        }

        if ($contentType) {
            $request->headers()->addHeaderLine("Content-type", $contentType);
        } else {
            $request->headers()->addHeaderLine("Content-type", "application/octet-stream");
        }

        return $this->send($request);
    }

    /**
     * Send an HTTP DELETE request to the specified URL
     *
     * @param  Zend\Uri\Http|string $url
     * @return Zend\Http\Response
     */
    public function delete($url)
    {
        $request = new Request($url);
        $request->setMethod(Request::METHOD_DELETE);

        return $this->send($request);
    }
}
