<?php
namespace AppBundle\Serializer;

use AppBundle\JsonAPI\ResourceIdentifier;
use AppBundle\JsonAPI\SingleResource;
use Pimcore\Model\DataObject\ContactMessage;

class ContactMessageSerializer extends AbstractPimcoreModelSerializer
{
    /**
     * @param string $className
     * @return bool
     */
    public function supports(string $className): bool
    {
        return $className === ContactMessage::class;
    }

    /**
     * @param ContactMessage $object
     * @return ResourceIdentifier
     * @throws \Exception
     */
    public function serializeResource($object): ResourceIdentifier
    {
        if (!$this->supports(get_class($object))) {
            $this->throwInvalidTypeException($object, ContactMessage::class);
        }

        $resource = $this->getSingleResource($object->getId(), $object->getClassName());
        $resource->setAttributes([
            'from' => $object->getFrom(),
            'subject' => $object->getSubject(),
            'message' => $object->getMessage()
        ]);

        return $resource;
    }

    /**
     * @param array $data
     * @param SingleResource $resource
     */
    public function unserializeResource(array $data, SingleResource $resource)
    {
        $resource->setAttributes([
            'subject' => $data['attributes']['subject'],
            'message' => $data['attributes']['message'],
            'from' => $data['attributes']['from'],
        ]);
    }
}