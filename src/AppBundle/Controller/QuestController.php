<?php
namespace AppBundle\Controller;

use AppBundle\Serializer\SerializerFactory;
use Pimcore\Model\DataObject\Quest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

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
     *     description="The field used to order quests. All returned resource parameters are valid (name, description, ...)."
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
     *     description="If you wish to include specific relationships you can list them here (include[]=items)",
     *     type="array",
     *     @SWG\Items(type="string", enum={"events", "user", "images"}),
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

        return $this->success($this->factory->build(Quest::class)->serializeResourceArray($list->load()));
    }
}