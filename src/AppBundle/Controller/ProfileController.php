<?php
namespace AppBundle\Controller;

use AppBundle\Serializer\SerializerFactory;
use AppBundle\Service\GdprExporter;
use Pimcore\Model\DataObject\Quest;
use Pimcore\Model\Tool\Email\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

/**
 * Class ProfileController
 * @package AppBundle\Controller
 */
class ProfileController extends ApiController
{
    /**
     * Export all data related to the current user.
     * @Route("/profile/data/export", methods={"GET"});
     *
     * @SWG\Tag(name="Profile")
     * @SWG\Response(response=200, description="The data")
     * @SWG\Response(response=403, description="Authentication required")
     */
    public function exportDataAction(GdprExporter $gdprExporter, SerializerFactory $factory)
    {
        $user = $gdprExporter->getUser();
        $emails = $gdprExporter->getEmails();
        $quests = $gdprExporter->getQuests();

        $userResource = $factory->build($user)->serializeResource($user);
        $emailResource = $factory->build(Log::class)->serializeResourceArray($emails);
        $questResource = $factory->build(Quest::class)->serializeResourceArray($quests);

        $data = [
            'user' => $userResource,
            'emails' => $emailResource,
            'quests' => $questResource,
            'createDate' => date(DATE_ISO8601, time())
        ];

        $json = \GuzzleHttp\json_encode($data, JSON_PRETTY_PRINT);

        $response = new Response();
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', 'application/json' );
        $response->headers->set('Content-Disposition', 'attachment; filename="data.json";');
        $response->headers->set('Content-length',  strlen($json));

        $response->sendHeaders();
        $response->setContent($json);

        return $response;
    }
}