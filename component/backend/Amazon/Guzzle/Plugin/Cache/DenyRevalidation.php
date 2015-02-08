<?php

namespace Akeeba\ARS\Amazon\Guzzle\Plugin\Cache;

use Akeeba\ARS\Amazon\Guzzle\Http\Message\RequestInterface;
use Akeeba\ARS\Amazon\Guzzle\Http\Message\Response;

/**
 * Never performs cache revalidation and just assumes the request is invalid
 */
class DenyRevalidation extends DefaultRevalidation
{
    public function __construct() {}

    public function revalidate(RequestInterface $request, Response $response)
    {
        return false;
    }
}
