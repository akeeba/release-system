<?php

namespace Akeeba\ARS\Amazon\Guzzle\Plugin\Backoff;

use Akeeba\ARS\Amazon\Guzzle\Http\Message\RequestInterface;
use Akeeba\ARS\Amazon\Guzzle\Http\Message\Response;
use Akeeba\ARS\Amazon\Guzzle\Http\Exception\HttpException;

/**
 * Implements an exponential backoff retry strategy.
 *
 * Warning: If no decision making strategies precede this strategy in the the chain, then all requests will be retried
 */
class ExponentialBackoffStrategy extends AbstractBackoffStrategy
{
    public function makesDecision()
    {
        return false;
    }

    protected function getDelay($retries, RequestInterface $request, Response $response = null, HttpException $e = null)
    {
        return (int) pow(2, $retries);
    }
}
