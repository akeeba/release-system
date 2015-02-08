<?php

namespace Akeeba\ARS\Amazon\Guzzle\Plugin\Cache;

use Akeeba\ARS\Amazon\Guzzle\Http\Message\RequestInterface;
use Akeeba\ARS\Amazon\Guzzle\Http\Message\Response;

/**
 * Strategy used to determine if a request can be cached
 */
interface CanCacheStrategyInterface
{
    /**
     * Determine if a request can be cached
     *
     * @param RequestInterface $request Request to determine
     *
     * @return bool
     */
    public function canCacheRequest(RequestInterface $request);

    /**
     * Determine if a response can be cached
     *
     * @param Response $response Response to determine
     *
     * @return bool
     */
    public function canCacheResponse(Response $response);
}
