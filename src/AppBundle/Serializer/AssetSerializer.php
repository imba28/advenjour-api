<?php
namespace AppBundle\Serializer;

use AppBundle\JsonAPI\ResourceIdentifier;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Tool;

class AssetSerializer extends AbstractSerializer
{
    private $thumbnails = [];

    public function setThumbnails(array $array) {
        $this->thumbnails = $array;
    }

    public function serializeResourceIdentifier($object): ResourceIdentifier
    {
        if (!$object instanceof Asset) {
            $this->throwInvalidTypeException($object, Asset::class);
        }

        return $this->getResourceIdentifier($object->getId(), ucfirst($object->getType()));
    }

    public function serializeResourceIdentifierArray(array $array): array
    {
        $images = [];

        foreach ($array as $item) {
            if ($item instanceof Asset) {
                $images[] = $item;
            } else if ($item instanceof Hotspotimage) {
                $images[] = $item->getImage();
            }
        }

        return parent::serializeResourceIdentifierArray($images);
    }

    public function serializeResource($object): ResourceIdentifier
    {
        if ($object === null) {
            return null;
        }

        if (!$object instanceof Asset) {
            $this->throwInvalidTypeException($object, Asset::class);
        }

        $resource = $this->getSingleResource($object->getId(), $object->getType());
        $resource->setAttributes([
            'fullPath' => Tool::getHostUrl() . $object->getFullPath(),
            'title' => $object->getMetadata('title'),
            'description' => $object->getMetadata('description'),
        ]);

        if ($object instanceof Image) {
            $thumbnails = array_map(
                function ($thumbnail) use ($object) {
                    return Tool::getHostUrl() . $object->getThumbnail($thumbnail)->getPath(true);
                },
                $this->thumbnails
            );

            $resource->addAttribute('thumbnails', $thumbnails);
        }

        return $resource;
    }

    public function serializeResourceArray(array $array): array
    {
        $images = [];

        foreach ($array as $object) {
            if ($object instanceof Asset) {
                $images[] = $object;
            } else if ($object instanceof Hotspotimage) {
                $images[] = $object->getImage();
            }
        }

        return parent::serializeResourceArray($images);
    }
}