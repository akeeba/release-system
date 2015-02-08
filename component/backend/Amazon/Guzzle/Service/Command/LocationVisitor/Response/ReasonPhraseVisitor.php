<?php

namespace Akeeba\ARS\Amazon\Guzzle\Service\Command\LocationVisitor\Response;

use Akeeba\ARS\Amazon\Guzzle\Http\Message\Response;
use Akeeba\ARS\Amazon\Guzzle\Service\Description\Parameter;
use Akeeba\ARS\Amazon\Guzzle\Service\Command\CommandInterface;

/**
 * Location visitor used to add the reason phrase of a response to a key in the result
 */
class ReasonPhraseVisitor extends AbstractResponseVisitor
{
    public function visit(
        CommandInterface $command,
        Response $response,
        Parameter $param,
        &$value,
        $context =  null
    ) {
        $value[$param->getName()] = $response->getReasonPhrase();
    }
}
