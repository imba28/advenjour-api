<?php
namespace AppBundle\JsonAPI;

class ResourceIdentifier implements \JsonSerializable
{
    private $identifier;

    private $type;

    public function __construct(int $identifier, string $type)
    {
        $this->identifier = $identifier;
        $this->type = ucfirst($type);
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->identifier,
            'type' => $this->type
        ];
    }

    public function getIdentifier(): int
    {
        return $this->identifier;
    }

    public function getType(): string
    {
        return $this->type;
    }
}