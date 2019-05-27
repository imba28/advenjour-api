<?php
namespace AppBundle\Serializer;

use AppBundle\JsonAPI\ResourceGalleryWrapper;
use AppBundle\JsonAPI\ResourceIdentifier;
use AppBundle\JsonAPI\SingleResource;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
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
            'questCollapsable',
            "carouselImg"
        ]);

        $resource = $this->getSingleResource($object->getId(), $object->getClassName());
        $resource->setAttributes([
            'name' => $object->getName(),
            'description' => $object->getDescription(),
            'public' => $object->getPublic() ? true : false,
            'steps' => $object->getSteps()
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
            $eventSerializer = $this->getSerializer(ObjectMetadata::class);

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

        if (($categories = $object->getCategories()) && count($categories) > 0) {
            $categorySerializer = $this->getSerializer($categories);

            $resource->addRelationship(
                'categories',
                $categorySerializer->serializeResourceIdentifierArray($categories)
            );

            if ($this->includeFullResource('categories')) {
                $resource->addInclude(
                    'categories',
                    $eventSerializer->serializeResourceArray($categories)
                );
            }
        }

        return $resource;
    }

    public function unserializeResource(array $data, SingleResource $resource) {
        $resource->setAttributes([
            'name' => $data['attributes']['name'],
            'description' => $data['attributes']['description'],
            'public' => $data['attributes']['description'],
            'steps' => $data['attributes']['steps']
        ]);

        // manually add gallery wrapper class
        if (isset($resource->getRelationships()['images'])) {
            $images = $resource->getRelationship('images');
            $resource->addRelationship('images', new ResourceGalleryWrapper($images));
        }
    }
}
