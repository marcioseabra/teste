<?php

namespace Laminas\DeveloperTools\Collector;

use Laminas\DeveloperTools\EventLogging\EventContextProvider;
use Laminas\EventManager\EventInterface;
use Laminas\Mvc\MvcEvent;

/**
 * Memory Data Collector.
 */
class MemoryCollector extends AbstractCollector implements EventCollectorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'memory';
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return PHP_INT_MAX - 1;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(MvcEvent $mvcEvent)
    {
        if (! isset($this->data)) {
            $this->data = [];
        }

        $this->data['memory'] = memory_get_peak_usage(true);
        $this->data['end'] = memory_get_usage(true);
    }

    /**
     * Saves the current memory usage.
     *
     * @param string         $id
     * @param EventInterface $event
     */
    public function collectEvent($id, EventInterface $event)
    {
        $contextProvider   = new EventContextProvider($event);
        $context['name']   = $contextProvider->getEvent()->getName();
        $context['target'] = $contextProvider->getEventTarget();
        $context['file']   = $contextProvider->getEventTriggerFile();
        $context['line']   = $contextProvider->getEventTriggerLine();
        $context['memory'] = memory_get_usage(true);

        if (! isset($this->data['event'][$id])) {
            $this->data['event'][$id] = [];
        }

        $this->data['event'][$id][] = $context;
    }

    /**
     * Returns the used Memory (peak)
     *
     * @return integer Memory
     */
    public function getMemory()
    {
        return $this->data['memory'];
    }

    /**
     * Event memory collected?
     *
     * @return integer Memory
     */
    public function hasEventMemory()
    {
        return isset($this->data['event']);
    }

    /**
     * Returns the detailed application memory.
     *
     * @return array
     */
    public function getApplicationEventMemory()
    {
        $result = [];

        if (! isset($this->data['event']['application'])) {
            return $result;
        }

        $app = $this->data['event']['application'];

        $previous = null;
        foreach ($app as $name => $context) {
            $result[$name] = $context;
            $result[$name]['difference'] = ($previous)
                ? ($context['memory'] - $previous['memory'])
                : ($context['memory']);
            $previous = prev($app);
            next($app);
        }

        return $result;
    }
}
