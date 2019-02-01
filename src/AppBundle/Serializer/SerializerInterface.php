<?php
namespace AppBundle\Serializer;

use AppBundle\JsonAPI\ResourceIdentifier;

interface SerializerInterface
{
    public function supports(string $className): bool;

    public function serializeResource($object): ResourceIdentifier;
    public function serializeResourceArray(array $array): array;

    public function serializeResourceIdentifier($object): ResourceIdentifier;
    public function serializeResourceIdentifierArray(array $array): array;
}