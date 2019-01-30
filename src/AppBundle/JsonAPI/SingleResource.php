<?php
namespace AppBundle\JsonAPI;

class SingleResource extends ResourceIdentifier
{
    private $attributes;

    private $relationships;

    private $includes;

    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function addAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function setRelationships(array $relationships)
    {
        $this->relationships = $relationships;
    }

    public function setIncludes(array $includes)
    {
        $this->includes = $includes;
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
                    'included' => $this->includes
                ])
            )
        );
    }
}