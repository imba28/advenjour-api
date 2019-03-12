<?php
namespace AppBundle\Serializer;

use AppBundle\JsonAPI\ResourceIdentifier;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Tool;

class AssetSerializer extends AbstractPimcoreModelSerializer
{
    private $thumbnails = [];

    public function setThumbnails(array $array) {
        $this->thumbnails = $array;
    }

    public function supports(string $className): bool
    {
        return $className === Asset::class || $className === Hotspotimage::class;
    }

    public function serializeResource($object): ResourceIdentifier
    {
        if ($object === null) {
            return null;
        }

        if (!$object instanceof Asset && !$object instanceof Hotspotimage) {
            $this->throwInvalidTypeException($object, Asset::class);
        }

        $resource = $this->getSingleResource($object->getId(), $object->getType());
        $resource->setAttributes([
            'fullPath' => Tool::getHostUrl() . $object->getFullPath(),
            'title' => $object->getMetadata('title'),
            'description' => $object->getMetadata('description'),
        ]);

        if ($object instanceof Image || $object instanceof Asset\Video) {
            $thumbnails = [];
            foreach ($this->thumbnails as $thumbnail) {
                try {
                    if ($object instanceof Asset\Video) {
                        $path = $object->getImageThumbnail($thumbnail);
                    } else {
                        $path =  $object->getThumbnail($thumbnail)->getPath(true);
                    }

                    $thumbnails[$thumbnail] = Tool::getHostUrl() . $path;
                } catch (\Exception $e) {}
            }

            $resource->addAttribute('thumbnails', $thumbnails);
        }

        return $resource;
    }

    public function serializeResourceArray(array $array): array
    {
        return parent::serializeResourceArray($this->resolveAssets($array));
    }

    public function serializeResourceIdentifierArray(array $array): array
    {
        return parent::serializeResourceIdentifierArray($this->resolveAssets($array));
    }

    /**
     * Resolve assets. For example we need to unwrap hotspotimages.
     *
     * @param array $array
     * @return array
     */
    private function resolveAssets(array $array): array
    {
        $images = [];

        foreach ($array as $object) {
            if ($object instanceof Asset) {
                $images[] = $object;
            } else if ($object instanceof Hotspotimage) {
                $images[] = $object->getImage();
            }
        }
        return $images;
    }
}