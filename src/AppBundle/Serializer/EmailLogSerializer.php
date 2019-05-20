<?php
namespace AppBundle\Serializer;

use AppBundle\JsonAPI\ResourceIdentifier;
use Carbon\Carbon;
use Pimcore\Model\Tool\Email\Log;

class EmailLogSerializer extends AbstractPimcoreModelSerializer
{
    public function supports(string $className): bool
    {
        return $className === Log::class;
    }

    /**
     * @param $object Log
     * @return ResourceIdentifier
     * @throws \Exception
     */
    public function serializeResource($object): ResourceIdentifier
    {
        if (!$this->supports(get_class($object))) {
            $this->throwInvalidTypeException($object, Log::class);
        }

        $resource = $this->getSingleResource($object->getId(), 'Email');
        $resource->setAttributes([
            'dateSent' => Carbon::createFromTimestamp($object->getSentDate())->toDate(),
            'from' => $object->getFrom(),
            'to' => $object->getTo(),
            'cc' => $object->getCcAsArray(),
            'subject' => $object->getSubject()
        ]);

        return $resource;
    }
}