<?php
namespace AppBundle\JsonAPI;

use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\ImageGallery;

/**
 * Relation type Gallery requires the value to be of type ImageGallery. This class wrap multiple images.
 *
 * @package AppBundle\JsonAPI
 */
class ResourceGalleryWrapper
{
    /**
     * @var ResourceIdentifier[]|array
     */
    private $items;

    /**
     * ResourceGalleryWrapper constructor.
     * @param ResourceIdentifier[] $images
     */
    public function __construct(array $images)
    {
        $this->items = $images;
    }

    /**
     * @return ImageGallery
     */
    public function getDataObject()
    {
        $gallery = new ImageGallery(
            array_map(function (ResourceIdentifier $item) {
                $advancedImage = new Hotspotimage();
                $advancedImage->setImage($item->getDataObject());
                return $advancedImage;
            }, $this->items)
        );

        return $gallery;
    }
}