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
        if (!$this->supports(get_class($object))) {
            $this->throwInvalidTypeException($object, Event::class);
        }

        return $this->getResourceIdentifier($object->getId(), $object->getClassName());
    }

    public function supports(string $className): bool
    {
        return $className === Event::class;
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
        if (!$this->supports(get_class($object))) {
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
            $assetSerializer = $this->getSerializer(Asset::class);
            $resource->addRelationship(
                'images', $assetSerializer->serializeResourceIdentifierArray($object->getImages()->getItems())
            );

            if ($this->includeFullResource('images')) {
                $resource->addInclude(
                    'images',
                    $assetSerializer->serializeResourceArray($object->getImages()->getItems())
                );
            }
        }

        if (count($object->getCategories()) > 0) {
            $eventSerializer = $this->getSerializer(EventCategory::class);

            $resource->addRelationship(
                'categories',
                $eventSerializer->serializeResourceIdentifierArray($object->getCategories())
            );

            if ($this->includeFullResource('categories')) {
                $resource->addInclude(
                    'categories',
                    $eventSerializer->serializeResourceArray($object->getCategories())
                );
            }
        }

        return $resource;
    }
}