<?php
namespace AppBundle\JsonAPI;

use Pimcore\Model\Element\ElementInterface;

class ResourceIdentifier implements \JsonSerializable
{
    private $identifier;

    private $type;

    public function __construct(?int $identifier, string $type)
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

    /**
     * @return ElementInterface|null
     * @throws \Exception
     */
    public function getDataObject(): ?ElementInterface
    {
        $class = "\\Pimcore\\Model\\DataObject\\{$this->getType()}";

        if ($this->getIdentifier() === null) {
            return new $class();
        }

        if (in_array($this->getType(), ['Video', 'Image'])) {
            $class = "\\Pimcore\\Model\\Asset\\{$this->getType()}";
        }

        if (!class_exists($class)) {
            throw new \Exception("{$class} is not a valid data object!");
        }

        return $class::getById($this->getIdentifier());
    }
}