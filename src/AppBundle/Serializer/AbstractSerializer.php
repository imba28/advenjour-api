<?php
namespace AppBundle\Serializer;

use AppBundle\JsonAPI\ResourceIdentifier;
use AppBundle\JsonAPI\SingleResource;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractSerializer implements SerializerInterface
{
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
        return in_array($name, $this->masterRequest->get('include', []));
    }

    /**
     * Returns a serializer class for a given pimcore object or a simple class name.
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
    protected function throwInvalidTypeException($givenObject, string $expectedType)
    {
        $givenType = is_object($givenObject) ? get_class($givenObject) : gettype($givenObject);
        $expectedType = ucfirst($expectedType);

        throw new \Exception("\$object must be of type {$expectedType} but is {$givenType} ");
    }

    protected function getSingleResource(int $identifier, string $type): SingleResource
    {
        return new SingleResource($identifier, $type);
    }

    protected function getResourceIdentifier(int $identifier, string $type): ResourceIdentifier
    {
        return new ResourceIdentifier($identifier, $type);
    }

    public function serializeResourceIdentifierArray(array $array): array
    {
        $json = [];

        foreach ($array as $item) {
            $json[] = $this->serializeResourceIdentifier($item);
        }

        return $json;
    }

    public function serializeResourceArray(array $array): array
    {
        $json = [];

        foreach ($array as $item) {
            $json[] = $this->serializeResource($item);
        }

        return $json;
    }
}