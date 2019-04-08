<?php
namespace AppBundle\Serializer;

use AppBundle\JsonAPI\ResourceIdentifier;
use AppBundle\JsonAPI\SingleResource;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\Single;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractSerializer implements SerializerInterface
{
    const REQUEST_PARAMATER_INCLUDE = 'include';

    /**
     * @var SerializerFactory
     */
    private $factory;

    /**
     * @var \Symfony\Component\HttpFoundation\Request|null
     */
    private $masterRequest;

    public function __construct(SerializerFactory $factory, RequestStack $requestStack)
    {
        $this->factory = $factory;
        $this->masterRequest = $requestStack->getMasterRequest();
    }

    /**
     * Decide based on request parameter 'include' if a relation should be included as a full resource or as a resource identifier
     *
     * @param string $name
     * @return bool
     */
    protected function includeFullResource(string $name): bool
    {
        $includes = $this->masterRequest->get(self::REQUEST_PARAMATER_INCLUDE, []);
        if (is_string($includes)) {
            $includes = explode(',', $includes);
        }
        return in_array($name, $includes);
    }

    /**
     * Returns a serializer class for a given class name or object.
     *
     * @param $class
     * @return SerializerInterface
     * @throws \Exception
     */
    protected function getSerializer($class): SerializerInterface
    {
        return $this->factory->build($class);
    }

    /**
     * Throw invalid type exception.
     *
     * @param $givenObject
     * @param string $expectedType
     * @throws \Exception
     */
    protected function throwInvalidTypeException($givenObject, string $expectedType = null)
    {
        $givenType = is_object($givenObject) ? get_class($givenObject) : gettype($givenObject);

        $message = "Serializer does not support type {$givenType}";
        if ($expectedType) {
            $expectedType = ucfirst($expectedType);
            $message = "\$object must be of type {$expectedType} but is {$givenType} ";
        }

        throw new \Exception($message);
    }

    /**
     * Returns SingleResource object with a given identifier and type.
     *
     * @param int $identifier
     * @param string $type
     * @return SingleResource
     */
    protected function getSingleResource(int $identifier, string $type): SingleResource
    {
        return new SingleResource($identifier, $type);
    }

    /**
     * Returns ResourceIdentifier object with a given identifier and type.
     *
     * @param int $identifier
     * @param string $type
     * @return ResourceIdentifier
     */
    protected function getResourceIdentifier(int $identifier, string $type): ResourceIdentifier
    {
        return new ResourceIdentifier($identifier, $type);
    }

    /**
     * Serialize array of supported objects to array of resource identifiers.
     *
     * @param array $array
     * @return array
     */
    public function serializeResourceIdentifierArray(array $array): array
    {
        $json = [];

        foreach ($array as $item) {
            $json[] = $this->serializeResourceIdentifier($item);
        }

        return $json;
    }

    /**
     * Serialize array of supported objects to array of single resources.
     *
     * @param array $array
     * @return array
     */
    public function serializeResourceArray(array $array): array
    {
        $json = [];

        foreach ($array as $item) {
            $json[] = $this->serializeResource($item);
        }

        return $json;
    }

    /**
     * Creates empty singleResource from json array. This does NOT set set attributes though, because this should be handled by the concrete serializer class.
     * @param array $data
     * @return SingleResource
     */
    public function unserializeEmptyResource(array $data): SingleResource
    {
        $resource = new SingleResource($data['id'] ?? null, $data['type']);

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