<?php

namespace Zend\Http\Entity;

use Zend\Stdlib\ParametersDescription,
    Zend\Http\Headers;

interface FormDataHandler
{
    /**
     * Set the form data object
     *
     * @param  Zend\Stdlib\ParametersDescription $formData
     */
    public function setFormData(ParametersDescription $formData);

    /**
     * Prepare request headers
     *
     * This should be called before sending a request with this content.
     * Usually it will set the 'Content-type' and 'Content-length' headers.
     *
     * @param Zend\Http\Headers $headers
     */
    public function prepareRequestHeaders(Headers $headers);
}