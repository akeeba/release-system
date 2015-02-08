<?php

namespace Akeeba\ARS\Amazon\Guzzle\Service\Command\LocationVisitor\Request;

use Akeeba\ARS\Amazon\Guzzle\Http\Message\RequestInterface;
use Akeeba\ARS\Amazon\Guzzle\Http\Message\PostFileInterface;
use Akeeba\ARS\Amazon\Guzzle\Service\Command\CommandInterface;
use Akeeba\ARS\Amazon\Guzzle\Service\Description\Parameter;

/**
 * Visitor used to apply a parameter to a POST file
 */
class PostFileVisitor extends AbstractRequestVisitor
{
    public function visit(CommandInterface $command, RequestInterface $request, Parameter $param, $value)
    {
        $value = $param->filter($value);
        if ($value instanceof PostFileInterface) {
            $request->addPostFile($value);
        } else {
            $request->addPostFile($param->getWireName(), $value);
        }
    }
}
