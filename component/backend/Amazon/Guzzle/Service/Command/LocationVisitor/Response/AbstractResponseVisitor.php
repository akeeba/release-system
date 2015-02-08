<?php

namespace Akeeba\ARS\Amazon\Guzzle\Service\Command\LocationVisitor\Response;

use Akeeba\ARS\Amazon\Guzzle\Service\Command\CommandInterface;
use Akeeba\ARS\Amazon\Guzzle\Http\Message\Response;
use Akeeba\ARS\Amazon\Guzzle\Service\Description\Parameter;

/**
 * {@inheritdoc}
 * @codeCoverageIgnore
 */
abstract class AbstractResponseVisitor implements ResponseVisitorInterface
{
    public function before(CommandInterface $command, array &$result) {}

    public function after(CommandInterface $command) {}

    public function visit(
        CommandInterface $command,
        Response $response,
        Parameter $param,
        &$value,
        $context =  null
    ) {}
}
