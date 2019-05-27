<?php
namespace AppBundle\Serializer;

use AppBundle\JsonAPI\ResourceIdentifier;
use AppBundle\JsonAPI\SingleResource;
use Pimcore\Model\DataObject\Data\ObjectMetadata;

class ObjectMetadataSerializer extends AbstractSerializer
{
    /**
     * @param string $className
     * @return bool
     */
    public function supports(string $className): bool
    {
        return $className === ObjectMetadata::class;
    }

    /**
     * @param ObjectMetadata $object
     * @return ResourceIdentifier
     * @throws \Exception
     */
    public function serializeResource($object): ResourceIdentifier
    {
        $wrappedObject = $object->getElement();

        if ($wrappedObject === null) {
            throw new \InvalidArgumentException('Element of ObjectMetadata is empty!');
        }

        $serializer = $this->getSerializer($wrappedObject);
        $resource = $serializer->serializeResource($wrappedObject);

        $metaData = $object->getData();

        foreach ($object->getColumns() as $column) {
            $resource->addMeta($column, $metaData[$column]);
        }

        return $resource;
    }

    /**
     * @param ObjectMetadata $object
     * @return ResourceIdentifier
     */
    public function serializeResourceIdentifier($object): ResourceIdentifier
    {
        $wrappedObject = $object->getElement();

        if ($wrappedObject === null) {
            throw new \InvalidArgumentException('Element of ObjectMetadata is empty!');
        }

        $resourceIdentifier = $this->getResourceIdentifier($wrappedObject->getId(), $wrappedObject->getClassName());
        $metaData = $object->getData();

        foreach ($object->getColumns() as $column) {
            $resourceIdentifier->addMeta($column, $metaData[$column]);
        }

        return $resourceIdentifier;
    }

    public function unserializeResource(array $data, SingleResource $resource)
    {
        throw new \Exception('method not implemented');
    }
}