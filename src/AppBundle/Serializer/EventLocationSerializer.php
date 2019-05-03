<?php
namespace AppBundle\Serializer;

use AppBundle\JsonAPI\ResourceIdentifier;
use Pimcore\Model\DataObject\EventLocation;

/**
 * Class EventLocationSerializer
 * @package AppBundle\Serializer
 */
class EventLocationSerializer extends AbstractPimcoreModelSerializer
{
    /**
     * @param string $className
     * @return bool
     */
    public function supports(string $className): bool
    {
        return $className === EventLocation::class;
    }

    /**
     * @param EventLocation $object
     * @return ResourceIdentifier
     */
    public function serializeResource($object): ResourceIdentifier
    {
        $resource = $this->getSingleResource($object->getId(), $object->getClassName());
        $resource->setAttributes([
            'name' => $object->getName(),
            'address' => $object->getAddress(),
            'city' => $object->getCity(),
            'zip' => $object->getZip(),
            'country' => $object->getCountry(),
        ]);

        if ($geo = $object->getGeo()) {
            $resource->addAttribute('geo', [
                'lat' => $geo->getLatitude(),
                'long' => $geo->getLongitude()
            ]);
        }

        return $resource;
    }
}
