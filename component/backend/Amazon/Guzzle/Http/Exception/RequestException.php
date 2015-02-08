<?php

namespace Akeeba\ARS\Amazon\Guzzle\Http\Exception;

use Akeeba\ARS\Amazon\Guzzle\Common\Exception\RuntimeException;
use Akeeba\ARS\Amazon\Guzzle\Http\Message\RequestInterface;

/**
 * Http request exception
 */
class RequestException extends RuntimeException implements HttpException
{
    /** @var RequestInterface */
    protected $request;

    /**
     * Set the request that caused the exception
     *
     * @param RequestInterface $request Request to set
     *
     * @return RequestException
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get the request that caused the exception
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }
}
