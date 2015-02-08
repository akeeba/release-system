<?php

namespace Akeeba\ARS\Amazon\Guzzle\Common;

use Akeeba\ARS\Amazon\Symfony\Component\EventDispatcher\EventDispatcher;
use Akeeba\ARS\Amazon\Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Akeeba\ARS\Amazon\Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class that holds an event dispatcher
 */
class AbstractHasDispatcher implements HasDispatcherInterface
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    public static function getAllEvents()
    {
        return array();
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    public function getEventDispatcher()
    {
        if (!$this->eventDispatcher) {
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }

    public function dispatch($eventName, array $context = array())
    {
        return $this->getEventDispatcher()->dispatch($eventName, new Event($context));
    }

    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->getEventDispatcher()->addSubscriber($subscriber);

        return $this;
    }
}
