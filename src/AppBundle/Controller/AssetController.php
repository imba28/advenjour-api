<?php
namespace AppBundle\Controller;

use AppBundle\Serializer\AssetSerializer;
use AppBundle\Serializer\SerializerFactory;
use Nelmio\ApiDocBundle\Annotation\Model;
use Pimcore\Log\ApplicationLogger;
use Pimcore\Model\Asset;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;

class AssetController extends ApiController
{
    const UPLOAD_PATH = '/uploads';

    /**
     * @var SerializerFactory
     */
    private $factory;

    /**
     * @var Asset\Folder|null
     */
    private $uploadFolder;

    /**
     * AssetController constructor.
     * @param SerializerFactory $factory
     */
    public function __construct(SerializerFactory $factory)
    {
        $this->factory = $factory;
        $this->uploadFolder = Asset\Folder::getByPath(self::UPLOAD_PATH);
    }

    /**
     * Asset single resource object.
     *
     * @Route("/public/asset/{id}.json", methods={"GET"}, requirements={"id"="\d+"})
     *
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="The unqiue id of an existing asset object."
     * ),
     * @SWG\Tag(name="Asset")
     * @SWG\Response(
     *     response=200,
     *     description="The requested objects",
     *     @Model(type=AppBundle\JsonAPI\Schemas\Asset::class)
     * )
     * @SWG\Response(response=404, description="Asset was not found")
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function detailAction(Request $request)
    {
        if ($asset = Asset::getById($request->get('id'))) {
            $serializer = $this->factory->build($asset);
            return $this->success($serializer->serializeResource($asset));
        }

        throw new NotFoundHttpException('Item not found!');
    }

    /**
     * Create a new asset resource.
     *
     * @Route("/public/assets.json", methods={"POST"})
     *
     * @SWG\Tag(name="Asset")
     * @SWG\Response(
     *     response=200,
     *     description="The create asset resource",
     *     @Model(type=AppBundle\JsonAPI\Schemas\Asset::class)
     * )
     * @SWG\Response(response=403, description="Authentication required")
     * @SWG\Response(response=422, description="Invalid data")
     *
     * @param Request $request
     *
     * @param ApplicationLogger $logger
     * @return JsonResponse
     * @throws \Exception
     */
    public function createAction(Request $request, ApplicationLogger $logger, AssetSerializer $serializer)
    {
        if ($this->uploadFolder === null) {
            $logger->critical(self::class . '::$uploadFolder is null!');
            return $this->error($this->get('translator')->trans('dev.fucked.up'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$request->files->get('file')) {
            return $this->error($this->get('translator')->trans('asset.errors.no_file_sent'), Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        $assets = [];

        /** @var UploadedFile $file */
        foreach ($request->files->get('file', []) as $file) {
            if ($file->getError() !== UPLOAD_ERR_OK) {
                $logger->error('Uploaded damaged file!');
                return $this->error($this->get('translator')->trans('asset.errors.upload_error'), Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $asset = Asset::create($this->uploadFolder->getId(), [
                'filename' => time() . "~{$file->getClientOriginalName()}.{$file->guessExtension()}"
            ]);

            $asset->setData(file_get_contents($file->getRealPath()));
            $asset->save([
               'versionNote' => "Uploaded by {$user}"
            ]);

            if ($asset->getId() === null) {
                return $this->error($this->get('translator')->trans('asset.errors.save_error'), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $logger->error(self::class . ' created a new asset object!', [
                'fileObject' => $asset
            ]);

            $assets[] = $asset;
        }

        // we reload all assets on order to get the concrete object needed by the serializer
        if (count($assets) === 1) {
            $resource = $serializer->serializeResource(Asset::getById($assets[0]->getId()));
        } else {
            $resource = $serializer->serializeResourceArray(
                array_map(function ($asset) {
                    return Asset::getById($asset->getId());
                }, $assets)
            );
        }

        return $this->success($resource, Response::HTTP_CREATED);
    }
}