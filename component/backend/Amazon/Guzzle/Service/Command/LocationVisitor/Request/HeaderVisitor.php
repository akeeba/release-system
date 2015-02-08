<?php

namespace Akeeba\ARS\Amazon\Guzzle\Service\Command\LocationVisitor\Request;

use Akeeba\ARS\Amazon\Guzzle\Common\Exception\InvalidArgumentException;
use Akeeba\ARS\Amazon\Guzzle\Http\Message\RequestInterface;
use Akeeba\ARS\Amazon\Guzzle\Service\Command\CommandInterface;
use Akeeba\ARS\Amazon\Guzzle\Service\Description\Parameter;

/**
 * Visitor used to apply a parameter to a header value
 */
class HeaderVisitor extends AbstractRequestVisitor
{
    public function visit(CommandInterface $command, RequestInterface $request, Parameter $param, $value)
    {
        $value = $param->filter($value);
        if ($param->getType() == 'object' && $param->getAdditionalProperties() instanceof Parameter) {
            $this->addPrefixedHeaders($request, $param, $value);
        } else {
            $request->setHeader($param->getWireName(), $value);
        }
    }

    /**
     * Add a prefixed array of headers to the request
     *
     * @param RequestInterface $request Request to update
     * @param Parameter        $param   Parameter object
     * @param array            $value   Header array to add
     *
     * @throws InvalidArgumentException
     */
    protected function addPrefixedHeaders(RequestInterface $request, Parameter $param, $value)
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException('An array of mapped headers expected, but received a single value');
        }
        $prefix = $param->getSentAs();
        foreach ($value as $headerName => $headerValue) {
            $request->setHeader($prefix . $headerName, $headerValue);
        }
    }
}
