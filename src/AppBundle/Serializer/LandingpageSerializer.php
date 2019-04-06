<?php
namespace AppBundle\Serializer;

use AppBundle\JsonAPI\ResourceIdentifier;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\ConfigLandingpage;

class LandingpageSerializer extends AbstractPimcoreModelSerializer
{
    /**
     * @param ConfigLandingpage $object
     * @return ResourceIdentifier
     * @throws \Exception
     */
    public function serializeResource($object): ResourceIdentifier
    {
        if (!$this->supports(get_class($object))) {
            $this->throwInvalidTypeException($object, ConfigLandingpage::class);
        }

        /** @var AssetSerializer $assetSerializer */
        $assetSerializer = $this->getSerializer(Asset::class);
        $assetSerializer->setThumbnails([ // todo lg: serializer should not know about concrete class implementations
            "heroSlide",
            "lazyLoad"
        ]);

        $items = $object->getSliderItems();

        $resource = $this->getSingleResource($object->getId(), $object->getClassName());
        $resource->addRelationship(
            'items',
            $assetSerializer->serializeResourceIdentifierArray($items)
        );

        if ($this->includeFullResource('items')) {
            $resource->addInclude('items', $assetSerializer->serializeResourceArray($items));
        }

        return $resource;
    }

    /**
     * @param string $className
     * @return bool
     */
    public function supports(string $className): bool
    {
        return $className === ConfigLandingpage::class;
    }
}
