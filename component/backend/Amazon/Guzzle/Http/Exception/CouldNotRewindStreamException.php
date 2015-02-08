<?php

namespace Akeeba\ARS\Amazon\Guzzle\Http\Exception;

use Akeeba\ARS\Amazon\Guzzle\Common\Exception\RuntimeException;

class CouldNotRewindStreamException extends RuntimeException implements HttpException {}
