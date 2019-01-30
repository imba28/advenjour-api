<?php
namespace AppBundle\Serializer;

use Pimcore\Model\DataObject\User;
use AppBundle\JsonAPI\ResourceIdentifier;

class UserSerializer extends AbstractSerializer
{
    /**
     * @param $object
     * @return ResourceIdentifier
     * @throws \Exception
     */
    public function serializeResourceIdentifier($object): ResourceIdentifier
    {
        if (!$object instanceof User) {
            $this->throwInvalidTypeException($object, User::class);
        }

        return $this->getResourceIdentifier($object->getId(), $object->getClassName());
    }

    /**
     * Serialize event object. Extracts all properties that should be accessible via the api.
     *
     * @param User $object
     * @return array
     */
    public function serializeResource($object): ResourceIdentifier
    {
        $resource = $this->getSingleResource($object->getId(), $object->getClassName());
        $resource->setAttributes([
            'username' => $object->getUsername(),
            'email' => $object->getEmail(),
            'isHost' => $object->getIsHost() === true,
        ]);

        return $resource;
    }
}