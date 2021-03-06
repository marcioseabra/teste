<?php

declare(strict_types=1);

namespace Laminas\Log\Writer;

use DateTimeInterface;
use Laminas\Log\Exception;
use Laminas\Log\Formatter\FormatterInterface;
use Laminas\Stdlib\ArrayUtils;
use Mongo as MongoC;
use MongoClient;
use MongoCollection;
use MongoDate;
use Traversable;

use function extension_loaded;
use function get_class;
use function gettype;
use function is_array;
use function is_object;
use function sprintf;

/**
 * Mongo log writer.
 */
class Mongo extends AbstractWriter
{
    /**
     * MongoCollection instance
     *
     * @var MongoCollection
     */
    protected $mongoCollection;

    /**
     * Options used for MongoCollection::save()
     *
     * @var array
     */
    protected $saveOptions;

    /**
     * Constructor
     *
     * @param MongoC|MongoClient|array|Traversable $mongo
     * @param string $database
     * @param string $collection
     * @param array $saveOptions
     * @throws Exception\InvalidArgumentException
     * @throws Exception\ExtensionNotLoadedException
     */
    public function __construct($mongo, $database = null, $collection = null, array $saveOptions = [])
    {
        if (! extension_loaded('mongo')) {
            throw new Exception\ExtensionNotLoadedException('Missing ext/mongo');
        }

        if ($mongo instanceof Traversable) {
            // Configuration may be multi-dimensional due to save options
            $mongo = ArrayUtils::iteratorToArray($mongo);
        }
        if (is_array($mongo)) {
            parent::__construct($mongo);
            $saveOptions = $mongo['save_options'] ?? [];
            $collection  = $mongo['collection'] ?? null;
            $database    = $mongo['database'] ?? null;
            $mongo       = $mongo['mongo'] ?? null;
        }

        if (null === $collection) {
            throw new Exception\InvalidArgumentException('The collection parameter cannot be empty');
        }

        if (null === $database) {
            throw new Exception\InvalidArgumentException('The database parameter cannot be empty');
        }

        if (! ($mongo instanceof MongoClient || $mongo instanceof MongoC)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Parameter of type %s is invalid; must be MongoClient or Mongo',
                is_object($mongo) ? get_class($mongo) : gettype($mongo)
            ));
        }

        $this->mongoCollection = $mongo->selectCollection($database, $collection);
        $this->saveOptions     = $saveOptions;
    }

    /**
     * This writer does not support formatting.
     *
     * @param string|FormatterInterface $formatter
     * @param array|null $options (unused)
     * @return WriterInterface
     */
    public function setFormatter($formatter, ?array $options = null)
    {
        return $this;
    }

    /**
     * Write a message to the log.
     *
     * @param array $event Event data
     * @return void
     * @throws Exception\RuntimeException
     */
    protected function doWrite(array $event)
    {
        if (null === $this->mongoCollection) {
            throw new Exception\RuntimeException('MongoCollection must be defined');
        }

        if (isset($event['timestamp']) && $event['timestamp'] instanceof DateTimeInterface) {
            $event['timestamp'] = new MongoDate($event['timestamp']->getTimestamp());
        }

        $this->mongoCollection->save($event, $this->saveOptions);
    }
}
