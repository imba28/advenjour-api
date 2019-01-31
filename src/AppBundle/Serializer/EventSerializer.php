<?php
namespace AppBundle\Serializer;

use AppBundle\JsonAPI\ResourceIdentifier;
use AppBundle\JsonAPI\SingleResource;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Event;
use Pimcore\Model\DataObject\EventCategory;

class EventSerializer extends AbstractSerializer
{
    /**
     * @param $object
     * @return ResourceIdentifier
     * @throws \Exception
     */
    public function serializeResourceIdentifier($object): ResourceIdentifier
    {
        if (!$object instanceof Event) {
            $this->throwInvalidTypeException($object, Event::class);
        }

        return $this->getResourceIdentifier($object->getId(), $object->getClassName());
    }

    /**
     * Serialize event object. Extracts all properties that should be accessible via the api.
     *
     * @param Event $object
     * @return SingleResource|array
     * @throws \Exception
     */
    public function serializeResource($object): ResourceIdentifier
    {
        if (!$object instanceof Event) {
            $this->throwInvalidTypeException($object, Event::class);
        }

        $resource = $this->getSingleResource($object->getId(), $object->getClassName());
        $resource->setAttributes([
            'name' => $object->getName(),
            'description' => $object->getDescription(),
            'price' => $object->getPrice() ? [
                'value' => $object->getPrice()->getValue(),
                'unit' => $object->getPrice()->getUnit()->getAbbreviation(),
            ] : null,
            'date' => [
                'from' => $object->getFrom(),
                'to' => $object->getTo()
            ]
        ]);

        if ($object->getImages() && count($object->getImages()->getItems()) > 0) {
            $resource->addRelationship(
                'images', $this->getSerializer(Asset::class)->serializeResourceIdentifierArray($object->getImages()->getItems()),
            );

            if ($this->includeFullResource('images')) {
                $resource->addInclude('images', $this->getSerializer(Asset::class)->serializeResourceArray($object->getImages()->getItems()));
            }
        }

        if (count($object->getCategories()) > 0) {
            $resource->addRelationship(
                'categories', $this->getSerializer(EventCategory::class)->serializeResourceIdentifierArray($object->getCategories()),
            );

            if ($this->includeFullResource('categories')) {
                $resource->addInclude('categories', $this->getSerializer(EventCategory::class)->serializeResourceArray($object->getCategories()));
            }
        }

        return $resource;
    }
}