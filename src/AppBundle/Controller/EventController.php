<?php
namespace AppBundle\Controller;

use AppBundle\Serializer\SerializerFactory;
use Pimcore\Model\DataObject\Event;
use Pimcore\Model\DataObject\EventCategory;
use Respect\Validation\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;
use Respect\Validation\Validator as v;

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
     *     description="Sort results ascending or descending order. Possible values are DESC and ASC"
     * )
     * @SWG\Parameter(
     *     name="include",
     *     in="query",
     *     description="If you wish to include specific relationships you can list them here (include[]=images)",
     *     type="array",
     *     @SWG\Items(type="string"),
     * )
     * @SWG\Response(response=200, description="List of requested objects")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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

        return $this->success($this->factory->build(Event::class)->serializeResourceArray($list->load()));
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
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
     * @Route("/events.json", methods={"PUT"})
     * @param Request $request
     *
     * @SWG\Parameter(
     *   name="body",
     *   in="body",
     *   required=true,
     *   @SWG\Schema(
     *       @SWG\Property(
     *          property="name",
     *          minimum="3",
     *          type="string",
     *       ),
     *       @SWG\Property(
     *          property="description",
     *          type="string",
     *       ),
     *       @SWG\Property(
     *          property="categories",
     *          type="array",
     *          @SWG\Items(type="integer")
     *       )
     *    )
     * )
     * @SWG\Response(response=201, description="User successfuly created")
     * @SWG\Response(response=422, description="Validation error. Check submitted json for errors.")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createAction(Request $request)
    {
        $input = json_decode($request->getContent(), true);
        $validators = v::key('name', v::stringType()->length(3))
            ->key('description', v::stringType())
            ->key('categories', v::arrayVal())
            ->key('categories', v::each(v::callback(function($categoryId) {
                return EventCategory::getById($categoryId) !== null;
            })));

        try {
            $validators->assert($input);

            $event = Event::create($input);
            $event->setKey($input['name'] . '-' . time());
            $event->setParentId(2);
            $event->setPublished(true);
            $event->setUser($this->getUser());

            $categories = array_map(function($categoryId) {
                return EventCategory::getById($categoryId);
            }, $input['categories']);

            $event->setCategories($categories);

            $event->save();

            return $this->success($this->factory->build(Event::class)->serializeResource($event), Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            $errors = array_filter(
                $e->findMessages([
                    'name' => $this->get('translator')->trans('auth.register.errors.name'),
                    'description' => $this->get('translator')->trans('auth.register.errors.description'),
                    'categories' => $this->get('translator')->trans('auth.register.errors.categories'),
                ])
            );

            return $this->error($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return $this->error('Model validation failed.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
