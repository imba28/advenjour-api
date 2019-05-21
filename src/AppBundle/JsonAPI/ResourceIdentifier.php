<?php
namespace AppBundle\JsonAPI;

use Pimcore\Model\Element\ElementInterface;

/**
 * Resource identifier class. Every resource can be described by its resource identifier.
 * Class ResourceIdentifier
 * @package AppBundle\JsonAPI
 */
class ResourceIdentifier implements \JsonSerializable
{
    /**
     * @var int|null
     */
    private $identifier;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $meta;

    /**
     * ResourceIdentifier constructor.
     * @param int|null $identifier
     * @param string $type
     */
    public function __construct(?int $identifier, string $type)
    {
        $this->identifier = $identifier;
        $this->type = ucfirst($type);
        $this->meta = [];
    }

    /**
     * Get identifier of resource.
     * @return int
     */
    public function getIdentifier(): int
    {
        return $this->identifier;
    }

    /**
     * Get type of resource.
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Add metadata to resource identifier object.
     * @param string $key
     * @param $value
     */
    public function addMeta(string $key, $value) {
        $this->meta[$key] = $value;
    }

    /**
     * Get the corresponding resource
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


    public function jsonSerialize()
    {
        $json = [
            'id' => $this->identifier,
            'type' => $this->type,
        ];

        if (!empty($this->meta)) {
            $json['meta'] = $this->meta;
        }

        return $json;
    }
}