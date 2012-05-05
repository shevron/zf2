<?php

namespace Zend\Http\CookieStore;

use Zend\Http\Header\SetCookie as SetCookieHeader,
    Zend\Http\Header\Cookie as CookieHeader,
    Zend\Http\Request,
    Zend\Http\Response;

abstract class AbstractCookieStore implements \IteratorAggregate
{
    /**
     * Add a cookie to the storage from a Set-Cookie header
     *
     * @param  \Zend\Http\Header\SetCookie $header
     * @return \Zend\Http\CookieStore\AbstractCookieStore
     */
    public function addCookieFromHeader(SetCookieHeader $header)
    {
        $this->addCookie(
            $header->getName(),
            $header->getValue(),
            $header->getDomain(),
            $header->getExpires(true),
            $header->getPath(),
            $header->isSecure(),
            $header->isHttponly()
        );

        return $this;
    }

    /**
     * Read all cookies from an HTTP response
     *
     * @param  \Zend\Http\Response $response
     * @return \Zend\Http\CookieStore\AbstractCookieStore
     */
    public function readCookiesFromResponse(Response $response)
    {
        $cookies = $response->headers()->get('Set-Cookie');
        if ($cookies) {
            foreach($cookies as $cookieHeader) {
                $this->addCookieFromHeader($cookieHeader);
            }
        }

        return $this;
    }

    /**
     * Get the 'Cookie:' header object containing all cookies matched for
     * a specific request
     *
     * @param  \Zend\Http\Request $request
     * @return \Zend\Http\Header\Cookie
     */
    public function getCookiesForRequest(Request $request)
    {
        $cookies = $this->getMatchingCookies($request->uri());
        return CookieHeader::fromSetCookieArray($cookies);
    }

    abstract public function addCookie($name, $value, $domain, $expires = null, $path = null, $secure = false, $httpOnly = true);

    /**
     * Get matching cookies for a URL
     *
     * @param Zend\Uri\Http|string $url
     * @param boolean              $includeSessionCookies
     * @param integer              $now
     */
    abstract public function getMatchingCookies($url, $includeSessionCookies = true, $now = null);
}