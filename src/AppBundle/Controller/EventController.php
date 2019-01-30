<?php
namespace AppBundle\Controller;

use AppBundle\Serializer\SerializerFactory;
use Pimcore\Model\DataObject\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

class EventController extends ApiController
{
    private $factory;

    public function __construct(SerializerFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * List of event objects.
     *
     * @Route("/events.json", methods={"GET"})
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
     * @SWG\Tag(name="Events")
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
            $list->setOrderKey($orderBy);
        }
        if ($order = $request->get('order')) {
            $list->setOrder($order);
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
     * @SWG\Tag(name="Events")
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
}
