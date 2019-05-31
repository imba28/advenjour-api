<?php
namespace AppBundle\JsonAPI;

/**
 * Class SingleResource
 * @package AppBundle\JsonAPI
 */
class SingleResource extends ResourceIdentifier
{
    /**
     * List of attributes
     * @var array
     */
    private $attributes = [];

    /**
     * List of relationships
     * @var ResourceIdentifier[]
     */
    private $relationships = [];

    /**
     * List of included resources. May contain sub-resources.
     * @var SingleResource[]
     */
    private $includes = [];

    /**
     * Override all attributes
     * @param array $attributes
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Add an attributes with name
     * @param string $key
     * @param $value
     */
    public function addAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Gett all attributes
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get attribute by name
     * @param string $key
     * @return mixed
     */
    public function getAttribute(string $key)
    {
        return $this->attributes[$key];
    }

    /**
     * Override all relationships
     * @param ResourceIdentifier[] $relationships
     */
    public function setRelationships(array $relationships)
    {
        $this->relationships = $relationships;
    }

    /**
     * Add a named relationship
     * @param string $name
     * @param ResourceIdentifier|ResourceIdentifier[]
     */
    public function addRelationship($name, $relationship)
    {
        $this->relationships[$name] = $relationship;
    }

    /**
     * Get all relationships
     * @return ResourceIdentifier[]
     */
    public function getRelationships(): array
    {
        return $this->relationships;
    }

    /**
     * Get relationship by name
     * @param string $name
     * @return mixed
     */
    public function getRelationship(string $name)
    {
        return $this->relationships[$name];
    }

    /**
     * Override all included resources.
     * @param SingleResource[] $includes
     */
    public function setIncludes(array $includes)
    {
        $this->includes = $includes;
    }

    /**
     * @param string $name
     * @param SingleResource|SingleResource[] $include
     */
    public function addInclude($name, $include)
    {
        $this->includes[$name] = $include;
    }

    /**
     * Get all included resources recursively.
     * @return array
     */
    public function getIncludes(): array
    {
        $includesRoot = [];

        foreach ($this->includes as $key => $include) {
            if (is_array($include)) {
                // include all includes
                $includesRoot = array_merge($includesRoot, array_values($include));

                foreach ($include as $includedItem) { // include all includes of includes
                    $includesRoot = array_merge($includesRoot, $includedItem->getIncludes());
                }
            } else {
                $includesRoot[] = $include;
            }
        }

        return $includesRoot;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        return array_filter(
            array_merge(
                $json,
                array_filter([
                    'attributes' => $this->attributes,
                    'relationships' => $this->relationships,
                ])
            )
        );
    }
}