<?php
namespace AppBundle\JsonAPI;

class CompoundResource implements \JsonSerializable
{
    private $resources = [];

    public function __construct(array $resources)
    {
        foreach ($resources as $resource) {
            if ($resources instanceof ResourceIdentifier) {
                $this->resources[] = $resource;
            }
        }
    }

    public function jsonSerialize()
    {
        return [
            'data'
        ];
    }
}