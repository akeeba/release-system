<?php

namespace Akeeba\ARS\Amazon\Guzzle\Service\Command\LocationVisitor\Request;

use Akeeba\ARS\Amazon\Guzzle\Http\Message\RequestInterface;
use Akeeba\ARS\Amazon\Guzzle\Service\Command\CommandInterface;
use Akeeba\ARS\Amazon\Guzzle\Service\Description\Parameter;

/**
 * Visitor used to apply a parameter to a POST field
 */
class PostFieldVisitor extends AbstractRequestVisitor
{
    public function visit(CommandInterface $command, RequestInterface $request, Parameter $param, $value)
    {
        $request->setPostField($param->getWireName(), $this->prepareValue($value, $param));
    }
}
