<?php
namespace AppBundle\Serializer;

/**
 * Class SerializerFactory
 * @package AppBundle\Serializer
 */
class SerializerFactory
{
    /**
     * @var SerializerInterface[]
     */
    private $services = [];

    /**
     * SerializeFactory constructor.
     * Tagged serializer services are automatically injected through di container.
     *
     * SerializerFactory constructor.
     * @param iterable $serializerServices
     */
    public function __construct(iterable $serializerServices)
    {
        $this->services = $serializerServices;
    }

    /**
     * Get a serializer service for a given class name or object.
     *
     * @param string|object $class
     * @return SerializerInterface
     * @throws \Exception
     */
    public function build($class): SerializerInterface
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (is_array($class)) {
            if (count($class) <= 0) {
                throw new \InvalidArgumentException('Cannot extract class name from empty array!');
            }

            $class = get_class($class[0]);
        }

        foreach ($this->services as $service) {
            if ($service->supports($class)) {
                return $service;
            }
        }

        throw new \Exception('Factory does not support this kind of object.');
    }
}