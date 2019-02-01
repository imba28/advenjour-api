<?php
namespace AppBundle\Serializer;

use AppBundle\JsonAPI\ResourceIdentifier;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;

abstract class AbstractPimcoreModelSerializer extends AbstractSerializer
{
    protected $supportedClasses = [];

    public function serializeResourceIdentifier($object): ResourceIdentifier
    {
        if (!$object instanceof ElementInterface) {
            $this->throwInvalidTypeException($object, ElementInterface::class);
        }

        $type = $object->getType();
        if ($object instanceof Concrete) {
            $type = $object->getClassName();
        }

        return new ResourceIdentifier($object->getId(), $type);
    }
}