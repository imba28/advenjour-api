<?php
namespace AppBundle\Serializer;

use Pimcore\Model\DataObject\Event;

class EventSerializer
{
    /**
     * Serialize event object. Extracts all properties that should be accessible via the api.
     *
     * @param Event $event
     * @return array
     */
    public function serialize(Event $event)
    {
        return [
            'name' => $event->getName(),
            'description' => $event->getDescription(),
            'price' => $event->getPrice() ? [
                'value' => $event->getPrice()->getValue(),
                'unit' => $event->getPrice()->getUnit()->getAbbreviation(),
            ] : null,
            'date' => [
                'from' => $event->getFrom(),
                'to' => $event->getTo()
            ],
            'locations' => []
        ];
    }

    /**
     * Serialize list of event objects.
     *
     * @param array $list
     * @return array
     */
    public function serializeArray(array $list)
    {
        $json = [];

        foreach ($list as $event) {
            if ($event instanceof Event) {
                $json[] = $this->serialize($event);
            }
        }

        return $json;
    }
}