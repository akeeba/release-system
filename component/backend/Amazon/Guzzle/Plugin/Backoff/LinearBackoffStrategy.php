<?php

namespace Akeeba\ARS\Amazon\Guzzle\Plugin\Backoff;

use Akeeba\ARS\Amazon\Guzzle\Http\Message\RequestInterface;
use Akeeba\ARS\Amazon\Guzzle\Http\Message\Response;
use Akeeba\ARS\Amazon\Guzzle\Http\Exception\HttpException;

/**
 * Implements a linear backoff retry strategy.
 *
 * Warning: If no decision making strategies precede this strategy in the the chain, then all requests will be retried
 */
class LinearBackoffStrategy extends AbstractBackoffStrategy
{
    /** @var int Amount of time to progress each delay */
    protected $step;

    /**
     * @param int $step Amount of time to increase the delay each additional backoff
     */
    public function __construct($step = 1)
    {
        $this->step = $step;
    }

    public function makesDecision()
    {
        return false;
    }

    protected function getDelay($retries, RequestInterface $request, Response $response = null, HttpException $e = null)
    {
        return $retries * $this->step;
    }
}
