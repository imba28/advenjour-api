<?php
namespace AppBundle\Controller;

use AppBundle\Serializer\NotSerializableException;
use AppBundle\Serializer\SerializerFactory;
use AppBundle\Service\ResourceUpdateService;
use Pimcore\Model\DataObject\ContactMessage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

class ContactMessageController extends ApiController
{
    /**
     * @Route("/public/contact.json", methods={"POST"})
     *
     * @SWG\Tag(name="Contact")
     * @SWG\Response(
     *     response=201,
     *     description="Message created"
     * )
     */
    public function createAction(Request $request, ResourceUpdateService $updateService, SerializerFactory $factory)
    {
        $serializer = $factory->build(ContactMessage::class);

        try {
            if (($input = json_decode($request->getContent(), true)) === null) {
                throw new UnprocessableEntityHttpException('contact.error.invalid_payload');
            }

            // todo @lg this is ugly. should just require once method call!
            $resource = $serializer->unserializeEmptyResource($input);
            $serializer->unserializeResource($input, $resource);

            $message = new ContactMessage();
            $message->setKey($resource->getAttribute('from') . '-' . time());
            $message->setParentId(212);
            $message->setPublished(true);

            $updateService->update($message, $resource);

            return $this->success($serializer->serializeResource($message), Response::HTTP_CREATED);
        } catch (NotSerializableException $e) {
            throw new UnprocessableEntityHttpException($this->get('translator')->trans($e->getMessage()));
        }
    }
}