<?php
namespace AppBundle\Serializer;

use AppBundle\JsonAPI\ResourceIdentifier;
use AppBundle\JsonAPI\SingleResource;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Event;
use Pimcore\Model\DataObject\Quest;

/**
 * Class QuestSerializer
 * @package AppBundle\Serializer
 */
class QuestSerializer extends AbstractPimcoreModelSerializer
{
    /**
     * @param string $className
     * @return bool
     */
    public function supports(string $className): bool
    {
        return $className === Quest::class;
    }

    /**
     * @param Quest $object
     * @return ResourceIdentifier
     * @throws \Exception
     *
     * @todo include user?
     */
    public function serializeResource($object): ResourceIdentifier
    {
        if (!$this->supports(get_class($object))) {
            $this->throwInvalidTypeException($object, Quest::class);
        }

        $this->getSerializer(Asset::class)->setThumbnails([
            'questCollapsable'
        ]);

        $resource = $this->getSingleResource($object->getId(), $object->getClassName());
        $resource->setAttributes([
            'name' => $object->getName(),
            'description' => $object->getDescription(),
            'public' => $object->getPublic() ? true: false
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

        if (count($object->getEvents()) > 0) {
            $eventSerializer = $this->getSerializer(Event::class);

            $resource->addRelationship(
                'events',
                $eventSerializer->serializeResourceIdentifierArray($object->getEvents())
            );

            if ($this->includeFullResource('events')) {
                $resource->addInclude(
                    'events',
                    $eventSerializer->serializeResourceArray($object->getEvents())
                );
            }
        }

        return $resource;
    }

    public function unserializeResource(array $data, SingleResource $resource) {
        $resource->setAttributes([
            'name' => $data['attributes']['name'],
            'description' => $data['attributes']['description'],
        ]);
    }
}
