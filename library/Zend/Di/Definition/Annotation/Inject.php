<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Di
 */

namespace Zend\Di\Definition\Annotation;

use Zend\Code\Annotation\AnnotationInterface;

class Inject implements AnnotationInterface
{

    protected $content = null;

    public function initialize($content)
    {
        $this->content = $content;
    }
}
