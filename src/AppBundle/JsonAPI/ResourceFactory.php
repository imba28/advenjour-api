<?php
namespace AppBundle\JsonAPI;

class ResourceFactory
{
    /**
     * Creates empty singleResource from json array. This does NOT set set attributes though, because this should be handled by the corresponding serializer.
     *
     * @param array $data
     * @return SingleResource
     */
    public function build(array $data): SingleResource
    {
        $resource = new SingleResource($data['id'], $data['type']);

        if (isset($data['relationships']) && is_array($data['relationships'])) {
            foreach ($data['relationships'] as $name => $relationship) {
                if (is_array($relationship)) {
                    $relation = [];
                    foreach ($relationship as $resourceIdentifier) {
                        $relation[] = new ResourceIdentifier($resourceIdentifier['id'], $resourceIdentifier['type']);
                    }

                    $resource->addRelationship($name, $relation);
                } else {
                    $resource->addRelationship($name, new ResourceIdentifier($relationship['id'], $relationship['type']));
                }
            }
        }

        return $resource;
    }
}