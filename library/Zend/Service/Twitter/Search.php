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
 * @package    Zend_Service
 * @subpackage Twitter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @category   Zend
 * @package    Zend_Service
 * @subpackage Twitter
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
 
/**
 * @namespace
 */
namespace Zend\Service\Twitter;

use Zend\Feed,
    Zend\Http,
    Zend\Json,
    Zend\Rest;

class Search extends Rest\Client\RestClient
{
    /**
     * Return Type
     * @var String
     */
    protected $_responseType = 'json';

    /**
     * Response Format Types
     * @var array
     */
    protected $_responseTypes = array(
        'atom',
        'json'
    );

    /**
     * Uri Compoent
     *
     * @var \Zend\Uri\Http
     */
    protected $_uri;

    /**
     * Constructor
     *
     * @param  string $returnType
     * @return void
     */
    public function __construct($responseType = 'json')
    {
        $this->setResponseType($responseType);
        $this->setUri("http://search.twitter.com");

        $this->setHeaders('Accept-Charset', 'ISO-8859-1,utf-8');
    }

    /**
     * set responseType
     *
     * @param string $responseType
     * @throws Exception\InvalidArgumentException
     * @return Search
     */
    public function setResponseType($responseType = 'json')
    {
        if (!in_array($responseType, $this->_responseTypes, TRUE)) {
            throw new Exception\InvalidArgumentException('Invalid Response Type');
        }
        $this->_responseType = $responseType;
        return $this;
    }

    /**
     * Retrieve responseType
     *
     * @return string
     */
    public function getResponseType()
    {
        return $this->_responseType;
    }

    /**
     * Get the current twitter trends.  Currnetly only supports json as the return.
     *
     * @throws Http\Client\Exception
     * @return array
     */
    public function trends()
    {
        $response     = $this->restGet('/trends.json');

        return Json::decode($response->getBody());
    }

    /**
     * Performs a Twitter search query.
     *
     * @throws Http\Client\Exception
     */
    public function execute($query, array $params = array())
    {

        $_query = array();

        $_query['q'] = $query;

        foreach($params as $key=>$param) {
            switch($key) {
                case 'geocode':
                case 'lang':
                case 'since_id':
                    $_query[$key] = $param;
                    break;
                case 'rpp':
                    $_query[$key] = (intval($param) > 100) ? 100 : intval($param);
                    break;
                case 'page':
                    $_query[$key] = intval($param);
                    break;
                case 'show_user':
                    $_query[$key] = 'true';
            }
        }

        $response = $this->restGet('/search.' . $this->_responseType, $_query);

        switch($this->_responseType) {
            case 'json':
                return Json\Json::decode($response->getBody());
                break;
            case 'atom':
                return Feed\Reader::importString($response->getBody());
                break;
        }

        return ;
    }
}
