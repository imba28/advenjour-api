<?php
namespace AppBundle\Controller;

use AppBundle\Model\DataObject\User;
use AppBundle\Serializer\UserSerializer;
use Respect\Validation\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Respect\Validation\Validator as v;
use Swagger\Annotations as SWG;

class AuthController extends ApiController
{
    private $serializer;

    public function __construct(UserSerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @Route("/auth/login", name="app_auth_login", methods={"POST"})
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @SWG\Parameter(
     *   name="body",
     *   in="body",
     *   required=true,
     *   @SWG\Schema(
     *     @SWG\Property(
     *       property="username",
     *       type="string",
     *       ),
     *       @SWG\Property(
     *          property="password",
     *          type="string"
     *       )
     *    )
     * )
     * @SWG\Response(response=200, description="Single response of user object containing id, username and email")
     * @SWG\Response(response=401, description="Invalid credentials response")
     */
    public function loginAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user && $this->isGranted('ROLE_USER')) {
            return $this->success($this->serializer->serializeResource($user));
        }
    }

    /**
     * @Route("/auth/register", methods={"PUT"})
     * @param Request $request
     *
     * @SWG\Parameter(
     *   name="body",
     *   in="body",
     *   required=true,
     *   @SWG\Schema(
     *       @SWG\Property(
     *          property="username",
     *          type="string",
     *          minimum=3,
     *          maximum=64
     *       ),
     *       @SWG\Property(
     *          property="password",
     *          minimum=6,
     *          type="string",
     *       ),
     *       @SWG\Property(
     *          property="email",
     *          type="string"
     *       ),
     *       @SWG\Property(
     *          property="countryCode",
     *          type="string",
     *          description="Country code (AT,DE,CH,..)",
     *          maximum=2
     *       ),
     *       @SWG\Property(
     *          property="isHost",
     *          type="boolean",
     *          description="Set host status"
     *       ),
     *       @SWG\Property(
     *          property="firstname",
     *          type="string",
     *       ),
     *       @SWG\Property(
     *          property="lastname",
     *          type="string",
     *       )
     *    )
     * )
     * @SWG\Response(response=201, description="User successfuly created")
     * * @SWG\Response(response=422, description="Validation error. Check submitted json for errors.")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function registerAction(Request $request)
    {
        $input = json_decode($request->getContent(), true);
        $validators = v::key('username', v::stringType()->length(3,64))
            ->key('password', v::stringType()->length(6, null))
            ->key('email', v::email());

        try {
            $validators->assert($input);

            if (User::getByEmail($input['email'], 1)) {
                throw new DuplicateUserException("User with {$input['email']} already exists");
            }

            $user = User::create($input);
            $user->setKey($input['email']);
            $user->setParentId(8);
            $user->setPublished(true);

            $user->save();

            return $this->success($this->serializer->serializeResource($user), Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            $errors = array_filter(
                $e->findMessages([
                    'username' => $this->get('translator')->trans('auth.register.errors.username'),
                    'email' => $this->get('translator')->trans('auth.register.errors.email'),
                    'password' => $this->get('translator')->trans('auth.register.errors.password'),
                ])
            );

            return $this->error($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DuplicateUserException $e) {
            return $this->error($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return $this->error('Model validation failed.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}