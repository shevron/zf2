<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_GData
 */

namespace Zend\GData\Books\Extension;

/**
 * Extends the base Link class with Books extensions
 *
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Books
 */
class BooksLink extends \Zend\GData\App\Extension\Link
{

    /**
     * Constructor for Zend_Gdata_Books_Extension_BooksLink which
     * Extends the base Link class with Books extensions
     *
     * @param string|null $href Linked resource URI
     * @param string|null $rel Forward relationship
     * @param string|null $type Resource MIME type
     * @param string|null $hrefLang Resource language
     * @param string|null $title Human-readable resource title
     * @param string|null $length Resource length in octets
     */
    public function __construct($href = null, $rel = null, $type = null,
            $hrefLang = null, $title = null, $length = null)
    {
        $this->registerAllNamespaces(\Zend\GData\Books::$namespaces);
        parent::__construct($href, $rel, $type, $hrefLang, $title, $length);
    }


}

