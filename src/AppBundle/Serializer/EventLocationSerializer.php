<?php
namespace AppBundle\Serializer;

use AppBundle\JsonAPI\ResourceIdentifier;
use AppBundle\JsonAPI\SingleResource;
use Pimcore\Model\DataObject\Data\Geopoint;
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

    public function unserializeResource(array $data, SingleResource $resource) {
        $resource->setAttributes([
            'name' => $data['attributes']['name'],
            'address' => $data['attributes']['address'],
            'city' => $data['attributes']['city'],
            'zip' => $data['attributes']['zip'],
            'country' => $data['attributes']['country']
        ]);

        if (isset($data['attributes']['geo'])) {
            $resource->addAttribute('geo', new Geopoint(
                $data['attributes']['geo']['long'],
                $data['attributes']['geo']['lat']
            ));
        }
    }
}
