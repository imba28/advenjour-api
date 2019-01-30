<?php
namespace AppBundle\Serializer;

use AppBundle\JsonAPI\ResourceIdentifier;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\EventCategory;

class EventCategorySerializer extends AbstractSerializer
{
    public function serializeResourceIdentifier($object): ResourceIdentifier
    {
        if (!$object instanceof EventCategory) {
            $this->throwInvalidTypeException($object, EventCategory::class);
        }

        return $this->getResourceIdentifier($object->getId(), $object->getClassName());
    }

    /**
     * Serialize event category object. Extracts all properties that should be accessible via the api.
     *
     * @param EventCategory $object
     * @return ResourceIdentifier
     * @throws \Exception
     */
    public function serializeResource($object): ResourceIdentifier
    {
        if (!$object instanceof EventCategory) {
            $this->throwInvalidTypeException($object, EventCategory::class);
        }
        $this->getSerializer(Asset::class)->setThumbnails([
            'eventCategoryOverview'
        ]);

        $singleResource = $this->getSingleResource($object->getId(), $object->getClassName());
        $singleResource->setAttributes([
            'name' => $object->getName(),
        ]);

        if ($object->getImage() !== null) {
            $singleResource->setRelationships([
                'image' => $this->getSerializer(Asset::class)->serializeResourceIdentifier($object->getImage())
            ]);

            if ($this->includeFullResource('image')) {
                $singleResource->setIncludes([
                    $this->getSerializer(Asset::class)->serializeResource($object->getImage())
                ]);
            }
        }

        return $singleResource;
    }
}