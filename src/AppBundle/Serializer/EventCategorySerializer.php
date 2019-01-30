<?php
namespace AppBundle\Serializer;

use Pimcore\Model\DataObject\EventCategory;

class EventCategorySerializer implements SerializerInterface
{
    private $assetSerializer;

    public function __construct(AssetSerializer $assetSerializer)
    {
        $this->assetSerializer = $assetSerializer;
    }

    /**
     * Serialize event category object. Extracts all properties that should be accessible via the api.
     *
     * @param EventCategory $category
     * @return array
     * @throws \Exception
     */
    public function serialize($category): ?array
    {
        $this->assetSerializer->setThumbnails([
            'eventCategoryOverview'
        ]);

        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'image' => $this->assetSerializer->serialize($category->getImage()),
            'parentCategory' => $category->getParentCategory() ? $category->getParentCategory()->getId() : null
        ];
    }

    /**
     * Serialize list of event objects.
     *
     * @param EventCategory[] $list
     * @return array
     * @throws \Exception
     */
    public function serializeArray(array $list): array
    {
        $json = [];

        foreach ($list as $event) {
            if ($event instanceof EventCategory) {
                $json[] = $this->serialize($event);
            }
        }

        return $json;
    }
}