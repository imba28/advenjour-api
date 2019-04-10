<?php
namespace AppBundle\Controller;

use AppBundle\Serializer\NotSerializableException;
use AppBundle\Serializer\SerializerFactory;
use AppBundle\Service\ResourceUpdateService;
use Pimcore\Model\DataObject\Event;
use Respect\Validation\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

/**
 * @SWG\Tag(name="Events")
 * Class EventController
 * @package AppBundle\Controller
 */
class EventController extends ApiController
{
    /**
     * @var SerializerFactory
     */
    private $factory;

    /**
     * EventController constructor.
     * @param SerializerFactory $factory
     */
    public function __construct(SerializerFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * List of event objects.
     *
     * @Route("/events.json", methods={"GET"})
     * @Route("/category/{id}/events.json", methods={"GET"})
     *
     * @SWG\Parameter(
     *     name="orderBy",
     *     in="query",
     *     type="string",
     *     description="The field used to order events"
     * )
     * @SWG\Parameter(
     *     name="order",
     *     in="query",
     *     type="string",
     *     description="Sort results ascending or descending order. Possible values are DESC and ASC."
     * )
     * @SWG\Parameter(
     *     name="include",
     *     in="query",
     *     description="If you wish to include specific relationships you can list them here (include[]=images).",
     *     type="array",
     *     @SWG\Items(type="string"),
     * )
     * @SWG\Parameter(
     *     name="limit",
     *     in="query",
     *     type="integer",
     *     description="Set result limit. Default value is 25."
     * )
     * @SWG\Parameter(
     *     name="offset",
     *     in="query",
     *     type="integer",
     *     description="Set result offset. Use this in combination with limit to create a pagination."
     * )
     * @SWG\Response(response=200, description="List of requested objects")
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function listAction(Request $request)
    {
        $list = new Event\Listing();

        if ($orderBy = $request->get('orderBy')) {
            $list->setOrderKey($this->escapePropertyString($orderBy));
        }
        if ($order = $request->get('order')) {
            $list->setOrder($this->escapePropertyString($order));
        }
        if ($categoryId = $request->get('id')) {
            $list->setCondition('FIND_IN_SET(?, categories)', [$categoryId]);
        }

        if ($filter = $request->get('filter')) {
            $this->filterCollectionByRequest($list, $filter);
        }

        $limit = intval($request->get('limit', 25));
        $offset = intval($request->get('offset', 0));
        $this->selectCollectionBoundsByRequest(
            $list,
            $limit,
            $offset
        );

        $resource = $this->factory->build(Event::class)->serializeResourceArray($list->load());

        return $this->success($resource, Response::HTTP_OK, [
            'limit' => $limit,
            'offset' => $offset,
            'countTotal' => $list->count(),
            'countResult' => count($list->load())
        ]);
    }

    /**
     * Event single resource object.
     *
     * @Route("/event/{id}.json", methods={"GET"})
     *
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="The unqiue id of an existing event object."
     * )
     * @SWG\Parameter(
     *     name="include",
     *     in="query",
     *     description="If you wish to include specific relationships you can list them here (include[]=images)",
     *     type="array",
     *     @SWG\Items(type="string"),
     * )
     *
     * @SWG\Response(response=200, description="The requested objects")
     * @SWG\Response(response=404, description="Event not found.")
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function detailAction(Request $request)
    {
        if ($event = Event::getById($request->get('id'))) {
            return $this->success($this->factory->build(Event::class)->serializeResource($event));
        }

        throw new NotFoundHttpException('Item not found!');
    }

    /**
     * Create an event object.
     *
     * @Route("/events.json", methods={"POST"})
     * @param Request $request
     *
     * @param ResourceUpdateService $updateService
     * @param SerializerFactory $factory
     * @return JsonResponse
     * @throws \Exception
     *
     * @SWG\Parameter(
     *     name="orderBy",
     *     in="query",
     *     type="string",
     *     description="The field used to order categories. All returned resource parameters are valid (name, parentCategory, ...)."
     * )
     * @SWG\Parameter(
     *     name="order",
     *     in="query",
     *     type="string",
     *     description="Sort results ascending or descending order. Possible values are DESC and ASC"
     * )
     * @SWG\Parameter(
     *     name="include",
     *     in="query",
     *     description="If you wish to include specific relationships you can list them here (include[]=images)",
     *     type="array",
     *     @SWG\Items(type="string"),
     * ),
     * @SWG\Parameter(
     *     name="filter",
     *     in="query",
     *     description="Add filters. (filter[name]=foobar or filter[price]=<::5&filter[name]=like::york)",
     *     type="array",
     *     @SWG\Items(
     *      type="string"
     *     ),
     * ),
     *
     * @SWG\Response(response=201, description="User successfuly created")
     * @SWG\Response(response=422, description="Validation error. Check submitted json for errors.")
     */
    public function createAction(Request $request, ResourceUpdateService $updateService, SerializerFactory $factory)
    {
        $input = json_decode($request->getContent(), true);

        $serializer = $factory->build(Event::class);

        try {
            // todo @lg this is ugly. should just require once method call!
            $resource = $serializer->unserializeEmptyResource($input);
            $serializer->unserializeResource($input, $resource);
        } catch (NotSerializableException $e) {
            throw new UnprocessableEntityHttpException($this->get('translator')->trans($e->getMessage()));
        }

        $event = new Event();
        $event->setKey($input['attributes']['name'] . '-' . time());
        $event->setParentId(2);
        $event->setPublished(true);
        $event->setUser($this->getUser());

        if (!$this->getUser()) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, $this->get('translator')->trans('event.errors.unauthorized'));
        }

        try {
            $updateService->update($event, $resource);

            return $this->success($this->factory->build(Event::class)->serializeResource($event), Response::HTTP_CREATED);

        } catch (ValidationException $e) {
            $errors = array_filter(
                $e->findMessages([
                    'name' => $this->get('translator')->trans('event.errors.name'),
                    'description' => $this->get('translator')->trans('event.errors.description'),
                    'categories' => $this->get('translator')->trans('event.errors.categories'),
                ])
            );

            return $this->error($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }


    /**
     * Delete an event object.
     *
     * @Route("/event/{id}.json", methods={"DELETE"}, requirements={"id"="\d+"})
     * @param Request $request
     *
     * @SWG\Response(response=200, description="Event successfully deleted.")
     * @SWG\Response(response=403, description="Authorization error. User must be owner of object.")
     * @SWG\Response(response=404, description="Event not found.")
     * @return JsonResponse
     * @throws \Exception
     */
    public function deleteAction(Request $request)
    {
        if ($event = Event::getById($request->get('id'))) {
            if ($this->getUser() !== $event->getUser()) {
                throw new HttpException(Response::HTTP_UNAUTHORIZED, $this->get('translator')->trans('event.errors.delete_unauthorized'));
            }

            $event->delete();

            return $this->success([]);
        }

        throw new NotFoundHttpException('Item not found!');
    }

    /**
     * Update an event object. Requires a complete set of properties.
     *
     * @Route("/event/{id}.json", methods={"PUT"}, requirements={"id"="\d+"})
     * @param Request $request
     *
     * @param ResourceUpdateService $updateService
     * @param SerializerFactory $factory
     * @return JsonResponse
     * @throws \Exception
     *
     * @SWG\Response(response=200, description="Event successfully updated.")
     * @SWG\Response(response=403, description="Authorization error. User must be owner of object.")
     * @SWG\Response(response=404, description="Event not found.")
     *
     */
    public function updateAction(Request $request, ResourceUpdateService $updateService, SerializerFactory $factory)
    {
        $input = json_decode($request->getContent(), true);

        $serializer = $factory->build(Event::class);

        try {
            // todo @lg this is ugly. should just require once method call!
            $resource = $serializer->unserializeEmptyResource($input);
            $serializer->unserializeResource($input, $resource);
        } catch (NotSerializableException $e) {
            throw new UnprocessableEntityHttpException($this->get('translator')->trans($e->getMessage()));
        }

        if ($event = Event::getById($request->get('id'))) {
            if ($this->getUser() !== $event->getUser()) {
                throw new HttpException(Response::HTTP_UNAUTHORIZED, $this->get('translator')->trans('event.errors.delete_unauthorized'));
            }

            try {
                $updateService->update($event, $resource);

                return $this->success($this->factory->build(Event::class)->serializeResource($event));

            } catch (ValidationException $e) {
                $errors = array_filter(
                    $e->findMessages([
                        'name' => $this->get('translator')->trans('event.errors.name'),
                        'description' => $this->get('translator')->trans('event.errors.description'),
                        'categories' => $this->get('translator')->trans('event.errors.categories'),
                    ])
                );

                return $this->error($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        throw new NotFoundHttpException('Item not found!');
    }
}
