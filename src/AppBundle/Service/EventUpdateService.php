<?php
namespace AppBundle\Service;

use Carbon\Carbon;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Event;
use Pimcore\Model\DataObject\EventCategory;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class EventUpdateService
{
    /**
     * Update event objects from data array
     *
     * @todo Service needs to know about structure of the data array and is therefore tightly coupled to controller. This is bad, because when changing the api body signature we have to edit two files.
     * @param Event $event
     * @param array $data
     * @throws \Exception
     */
    public function update(Event $event, array $data)
    {
        $price = null;
        if (isset($data['price']) && $data['price']['currency'] && $data['price']['value']) {
            try {
                $currency = DataObject\QuantityValue\Unit::getByAbbreviation($data['price']['currency']);
                $price = new DataObject\Data\QuantityValue($data['price']['value'], $currency->getId());
            } catch (\Exception $e) {
                throw new UnprocessableEntityHttpException($this->get('translator')->trans('event.errors.update_unknown_currency'));
            }
        }

        $data = [
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $price,
            'categories' => array_filter(array_map(function ($categoryId) {
                return EventCategory::getById($categoryId);
            }, $data['categories'])),
            'from' => Carbon::createFromTimestamp($data['from']),
            'to' => Carbon::createFromTimestamp($data['to'])
        ];

        foreach ($data as $key => $value) {
            $setter = "set{$key}";
            if (!method_exists($event, $setter)) {
                throw new UnprocessableEntityHttpException($this->get('translator')->trans('event.errors.update_invalid_property'));
            }

            $event->$setter($value);
        }

        $event->save();
    }
}