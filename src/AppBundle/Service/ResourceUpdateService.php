<?php
namespace AppBundle\Service;

use AppBundle\JsonAPI\ResourceIdentifier;
use AppBundle\JsonAPI\SingleResource;
use Pimcore\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class ResourceUpdateService
{
    private $translator;
    
    
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Update event objects from data array
     *
     * @param DataObject $object
     * @param SingleResource $resource
     * @throws \Exception
     */
    public function update(DataObject $object, SingleResource $resource)
    {
        foreach ($resource->getAttributes() as $key => $value) {
            $setter = "set{$key}";
            if (!method_exists($object, $setter)) {
                throw new UnprocessableEntityHttpException($this->translator->trans('event.errors.update_invalid_property'));
            }

            $object->$setter($value);
        }

        foreach ($resource->getRelationships() as $relationshipName => $relationship) {
            $setter = "set{$relationshipName}";

            if (!method_exists($object, $setter)) {
                throw new UnprocessableEntityHttpException($this->translator->trans('event.errors.update_invalid_relationship'));
            }

            if (is_array($relationship)) {
                $list = [];

                /** @var  $relationshipDefinition */
                try {
                    $relationshipDefinition = $object->getClass()->getFieldDefinition($relationshipName);
                    if ($relationshipDefinition instanceof DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation) {
                        /** @var ResourceIdentifier $resourceIdentifier */
                        foreach ($relationship as $resourceIdentifier) {
                            $meta = $resourceIdentifier->getMeta();
                            $objetMetadata = new DataObject\Data\ObjectMetadata($relationshipName, array_keys($meta), $resourceIdentifier->getDataObject());

                            if (!empty($meta)) {
                                $objetMetadata->setData($meta);
                            }

                            $objetMetadata->setElement($resourceIdentifier->getDataObject());
                            $list[] = $objetMetadata;
                        }
                    } else {
                        /** @var ResourceIdentifier $resourceIdentifier */
                        foreach ($relationship as $resourceIdentifier) {
                            $list[] = $resourceIdentifier->getDataObject();
                        }
                    }
                } catch (\Exception $e) {

                }

                $object->$setter($list);
            } else {
                $object->$setter($relationship->getDataObject());
            }
        }

        $object->save();
    }

    /**
     * @param ResourceIdentifier[] $relationship
     * @return bool
     */
    private function hasMetadata($relationship): bool {
        foreach ($relationship as $relation) {
            if ($relation->hasMeta()) {
                return true;
            }
        }

        return false;
    }
}