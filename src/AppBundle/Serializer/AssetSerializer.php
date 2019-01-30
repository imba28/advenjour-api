<?php
namespace AppBundle\Serializer;

use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Tool;

class AssetSerializer implements SerializerInterface
{
    private $thumbnails = [];

    public function setThumbnails(array $array) {
        $this->thumbnails = $array;
    }

    public function serialize($object): ?array
    {
        if ($object === null) {
            return null;
        }

        if (!$object instanceof Asset) {
            throw new \Exception('$object must be of type ' . Asset::class . ' but was ' . is_object($object) ? get_class($object) : gettype($object));
        }

        if ($object instanceof Image) {
            $thumbnails = array_map(
                function ($thumbnail) use ($object) {
                    return Tool::getHostUrl() . $object->getThumbnail($thumbnail)->getPath(true);
                },
                $this->thumbnails
            );
        } else {
            $thumbnails = null;
        }

        return [
            'id' => $object->getId(),
            'fullPath' => Tool::getHostUrl() . $object->getFullPath(),
            'title' => $object->getMetadata('title'),
            'description' => $object->getMetadata('description'),
            'thumbnails' => $thumbnails
        ];
    }

    public function serializeArray(array $array): array
    {
        $json = [];

        foreach ($array as $object) {
            if ($object instanceof Asset) {
                $json[] = $this->serialize($object);
            }
        }

        return $json;
    }
}