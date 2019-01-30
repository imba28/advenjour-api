<?php
namespace AppBundle\Serializer;

use Pimcore\Model\DataObject\User;

class UserSerializer implements SerializerInterface
{
    /**
     * Serialize event object. Extracts all properties that should be accessible via the api.
     *
     * @param User $user
     * @return array
     */
    public function serialize($user): ?array
    {
        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'isHost' => $user->getIsHost() === true,
        ];
    }

    /**
     * Serialize list of event objects.
     *
     * @param User[] $list
     * @return array
     */
    public function serializeArray(array $list): array
    {
        $json = [];

        foreach ($list as $user) {
            if ($user instanceof User) {
                $json[] = $this->serialize($user);
            }
        }

        return $json;
    }
}