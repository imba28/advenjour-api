<?php
namespace AppBundle\Controller;

use AppBundle\Serializer\NotSerializableException;
use AppBundle\Serializer\SerializerFactory;
use AppBundle\Service\ResourceUpdateService;
use Pimcore\Model\DataObject\Quest;
use Respect\Validation\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;

class QuestController extends ApiController
{
    /**
     * @var SerializerFactory
     */
    private $factory;

    public function __construct(SerializerFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Get list of quests.
     *
     * @Route("/quests.json", methods={"GET"})
     * @Route("/users/{id}/quests.json", methods={"GET"}, requirements={"id"="\d+"})
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
     * )
     * @SWG\Parameter(
     *     name="filter",
     *     in="query",
     *     description="Add filters. (filter[name]=foobar or filter[price]=<::5&filter[name]=like::york)",
     *     type="array",
     *     @SWG\Items(
     *      type="string"
     *     ),
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
     *
     * @SWG\Tag(name="Quest")
     * @SWG\Response(
     *     response=200,
     *     description="Returns list of quest objects."
     * )
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function listAction(Request $request)
    {
        $list = new Quest\Listing();

        if ($orderBy = $request->get('orderBy')) {
            $list->setOrderKey($this->escapePropertyString($orderBy));
        }
        if ($order = $request->get('order')) {
            $list->setOrder($this->escapePropertyString($order));
        }

        if ($id = $request->get('id')) {
            $list->addConditionParam('user__id = ?', $id);
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

        return $this->success(
            $this->factory->build(Quest::class)->serializeResourceArray($list->load()),
            Response::HTTP_OK, [
                'limit' => $limit,
                'offset' => $offset,
                'countTotal' => $list->count(),
                'countResult' => count($list->load())
            ]
        );
    }

    /**
     * Event single resource object.
     *
     * @Route("/quest/{id}.json", methods={"GET"})
     *
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="The unqiue id of an existing quest object."
     * )
     * @SWG\Parameter(
     *     name="include",
     *     in="query",
     *     description="If you wish to include specific relationships you can list them here (include[]=images)",
     *     type="array",
     *     @SWG\Items(type="string"),
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="The requested objects",
     *     @Model(type=AppBundle\JsonAPI\Schemas\Quest::class)
     * )
     * @SWG\Response(response=404, description="Quest not found.")
     *
     * @SWG\Tag(name="Quest")
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function detailAction(Request $request)
    {
        if ($event = Quest::getById($request->get('id'))) {
            return $this->success($this->factory->build(Quest::class)->serializeResource($event));
        }

        throw new NotFoundHttpException('Item not found!');
    }

    /**
     * Create a new quest resource object.
     *
     * @Route("/quests.json", methods={"POST"})
     *
     * @SWG\Response(
     *     response=201,
     *     description="The created object",
     *     @Model(type=AppBundle\JsonAPI\Schemas\Quest::class)
     * )
     * @SWG\Response(response=421, description="Invalid request")
     * @SWG\Response(response=401, description="Authentication required")
     *
     * @SWG\Tag(name="Quest")
     *
     * @param Request $request
     * @param ResourceUpdateService $updateService
     * @param SerializerFactory $factory
     * @return JsonResponse
     * @throws \Exception
     */
    public function createAction(Request $request, ResourceUpdateService $updateService)
    {
        if (!$this->getUser()) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, $this->get('translator')->trans('quest.errors.unauthorized'));
        }

        $input = json_decode($request->getContent(), true);

        $serializer = $this->factory->build(Quest::class);

        try {
            // todo @lg this is ugly. should just require once method call!
            $resource = $serializer->unserializeEmptyResource($input);
            $serializer->unserializeResource($input, $resource);
        } catch (NotSerializableException $e) {
            throw new UnprocessableEntityHttpException($this->get('translator')->trans($e->getMessage()));
        }

        $quest = new Quest();
        $quest->setKey($input['attributes']['name'] . '-' . time());
        $quest->setParentId($this->getUser()->getId());
        $quest->setPublished(true);
        $quest->setUser($this->getUser());

        try {
            $updateService->update($quest, $resource);
            return $this->success($this->factory->build(Quest::class)->serializeResource($quest), Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            $errors = array_filter(
                $e->findMessages([
                    'name' => $this->get('translator')->trans('quest.errors.name'),
                    'description' => $this->get('translator')->trans('quest.errors.description'),
                    'categories' => $this->get('translator')->trans('quest.errors.categories'),
                ])
            );

            return $this->error($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Delete a quest object.
     *
     * @Route("/quest/{id}.json", methods={"DELETE"}, requirements={"id"="\d+"})
     * @param Request $request
     *
     * @SWG\Response(response=200, description="Quest successfully deleted.")
     * @SWG\Response(response=403, description="Authorization error. User must be owner of object.")
     * @SWG\Response(response=404, description="Quest not found.")
     *
     * @SWG\Tag(name="Quest")
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function deleteAction(Request $request)
    {
        if ($quest = Quest::getById($request->get('id'))) {
            if ($this->getUser() !== $quest->getUser()) {
                throw new HttpException(Response::HTTP_UNAUTHORIZED, $this->get('translator')->trans('event.errors.delete_unauthorized'));
            }

            $quest->delete();

            return $this->success([]);
        }

        throw new NotFoundHttpException('Item not found!');
    }

    /**
     * Update single resource quest object.
     *
     * @Route("/quest/{id}.json", methods={"PUT"})
     *
     * @SWG\Response(
     *     response=201,
     *     description="The updated objects",
     *     @Model(type=AppBundle\JsonAPI\Schemas\Quest::class)
     * )
     * @SWG\Response(response=404, description="Quest not found.")
     *
     * @SWG\Tag(name="Quest")
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function updateAction(Request $request, ResourceUpdateService $updateService)
    {
        if (!$this->getUser()) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, $this->get('translator')->trans('quest.errors.unauthorized'));
        }

        $input = json_decode($request->getContent(), true);
        $serializer = $this->factory->build(Quest::class);

        if (!($quest = Quest::getById($request->get('id')))) {
            throw new NotFoundHttpException('Quest not found!');
        }

        if ($quest->getUser() !== $this->getUser()) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, $this->get('translator')->trans('quest.errors.unauthorized'));
        }

        try {
            // todo @lg this is ugly. should just require once method call!
            $resource = $serializer->unserializeEmptyResource($input);
            $serializer->unserializeResource($input, $resource);

            $updateService->update($quest, $resource);
            return $this->success($this->factory->build(Quest::class)->serializeResource($quest), Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            $errors = array_filter(
                $e->findMessages([
                    'name' => $this->get('translator')->trans('quest.errors.name'),
                    'description' => $this->get('translator')->trans('quest.errors.description'),
                    'categories' => $this->get('translator')->trans('quest.errors.categories'),
                ])
            );

            return $this->error($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (NotSerializableException $e) {
            throw new UnprocessableEntityHttpException($this->get('translator')->trans($e->getMessage()));
        }
    }

}