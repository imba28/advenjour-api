<?php
namespace AppBundle\Serializer;

use AppBundle\Model\DataObject\User;
use AppBundle\JsonAPI\ResourceIdentifier;

class UserSerializer extends AbstractPimcoreModelSerializer
{
    /**
     * Serialize event object. Extracts all properties that should be accessible via the api.
     *
     * @param User $object
     * @return ResourceIdentifier
     * @throws \Exception
     */
    public function serializeResource($object): ResourceIdentifier
    {
        if (!$this->supports(get_class($object))) {
            $this->throwInvalidTypeException($object, User::class);
        }

        $resource = $this->getSingleResource($object->getId(), $object->getClassName());
        $resource->setAttributes([
            'username' => $object->getUsername(),
            'email' => $object->getEmail(),
            'isHost' => $object->getIsHost() === true,
        ]);

        return $resource;
    }

    public function supports(string $className): bool
    {
        return $className === User::class;
    }
}