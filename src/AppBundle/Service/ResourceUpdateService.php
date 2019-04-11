<?php
namespace AppBundle\Service;

use AppBundle\JsonAPI\ResourceIdentifier;
use AppBundle\JsonAPI\SingleResource;
use Pimcore\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ResourceUpdateService
{
    /**
     * Update event objects from data array
     *
     * @param DataObject $object
     * @param SingleResource $resource
     * @throws \Exception
     */
    public function update(DataObject $object, SingleResource $resource)
    {
        foreach ($resource->getAttributes() as $key => $value) {
            $setter = "set{$key}";
            if (!method_exists($object, $setter)) {
                throw new UnprocessableEntityHttpException($this->get('translator')->trans('event.errors.update_invalid_property'));
            }

            $object->$setter($value);
        }

        foreach ($resource->getRelationships() as $relationship => $resource) {
            $setter = "set{$relationship}";
            if (!method_exists($object, $setter)) {
                throw new UnprocessableEntityHttpException($this->get('translator')->trans('event.errors.update_invalid_relationship'));
            }

            if (is_array($resource)) {
                $list = [];

                /** @var ResourceIdentifier $resourceIdentifier */
                foreach ($resource as $resourceIdentifier) {
                    $list[] = $resourceIdentifier->getDataObject();
                }

                $object->$setter($list);
            } else {
                $object->$setter($resource->getDataObject());
            }
        }

         $object->save();
    }
}