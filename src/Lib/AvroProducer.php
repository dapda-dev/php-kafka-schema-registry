<?php

namespace Kafka\SchemaRegistry\Lib;

use RdKafka\ProducerTopic;
use Kafka\SchemaRegistry\Helpers\TopicSuffix;

class AvroProducer
{
    /** @var ProducerTopic */
    private $producer;

    /** @var MessageSerializer */
    private $serializer;

    private $defaultKeySchema;
    private $defaultValueSchema;

    public function __construct(ProducerTopic $producer, $registryUrl, $registryAuthKey = null, $defaultKeySchema = null, $defaultValueSchema = null, $options = [])
    {
        $this->producer           = $producer;
        $this->defaultKeySchema   = $defaultKeySchema;
        $this->defaultValueSchema = $defaultValueSchema;

        $this->serializer = new MessageSerializer(new CachedSchemaRegistryClient($registryUrl, $registryAuthKey), $options);
    }

    public function produce($partition, $msgflags, $value, $key = null, $keySchema = null, $valueSchema = null, $format = null)
    {
        $keySchema   = $keySchema ?: $this->defaultKeySchema;
        $valueSchema = $valueSchema ?: $this->defaultValueSchema;

        if ($value && $valueSchema) {
            $value = $this->serializer->encodeRecordWithSchema(TopicSuffix::getCleanedTopic($this->producer->getName()), $valueSchema, $value, false, $format);
        }

        if ($key && $keySchema) {
            $key = $this->serializer->encodeRecordWithSchema(TopicSuffix::getCleanedTopic($this->producer->getName()), $keySchema, $key, true, $format);
        }

        return $this->producer->produce($partition, $msgflags, $value, $key);
    }
}
