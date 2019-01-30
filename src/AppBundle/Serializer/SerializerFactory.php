<?php
namespace AppBundle\Serializer;

use AppBundle\Model\DataObject\User;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Event;
use Pimcore\Model\DataObject\EventCategory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SerializerFactory
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function build($class): SerializerInterface
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        switch ($class) {
            case Asset::class:
                return $this->container->get(AssetSerializer::class);

            case Event::class:
                return $this->container->get(EventSerializer::class);

            case EventCategory::class:
                return $this->container->get(EventCategorySerializer::class);

            case User::class:
                return $this->container->get(UserSerializer::class);
        }

        throw new \Exception('Factory does not handle this kind of object.');
    }
}