<?php
namespace AppBundle\Serializer;

interface SerializerInterface
{
    public function serialize($object): ?array;
    public function serializeArray(array $array): array;
}