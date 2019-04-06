<?php
namespace AppBundle\JsonAPI;

class SingleResource extends ResourceIdentifier
{
    private $attributes = [];

    private $relationships = [];

    private $includes = [];

    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function addAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setRelationships(array $relationships)
    {
        $this->relationships = $relationships;
    }

    public function addRelationship($name, $relationship)
    {
        $this->relationships[$name] = $relationship;
    }

    public function getRelationships(): array
    {
        return $this->relationships;
    }

    public function setIncludes(array $includes)
    {
        $this->includes = $includes;
    }

    public function addInclude($name, $include)
    {
        $this->includes[$name] = $include;
    }

    public function getIncludes(): array
    {
        $includes = [];

        foreach ($this->relationships as $relationship) {
            if (is_array($relationship)) {
                foreach ($relationship as $resource) {
                    if ($resource instanceof SingleResource) {
                        $includes[] = $resource->getIncludes();
                    }
                }
            } else {
                if ($relationship instanceof SingleResource) {
                    $includes[] = $relationship->getIncludes();
                }
            }
        }

        foreach ($this->includes as $include) {
            $includes = array_merge($includes, array_values($include));
        }

        return $includes;
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