<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Module
 */
namespace Zend\Module\Consumer;

/**
 * LocatorRegistered 
 *
 * By implementing this interface in a Module class, the instance of the Module 
 * class will be automatically injected into any DI-configured object which has 
 * a constructor or setter parameter which is type hinted with the Module class 
 * name. Implementing this interface obviously does not require adding any 
 * methods to your class.
 * 
 * @category   Zend
 * @package    Zend_Module
 * @subpackage Consumer
 */
interface LocatorRegistered {}
