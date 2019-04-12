<?php
namespace AppBundle\Controller;

use AppBundle\Model\DataObject\User;
use AppBundle\Serializer\UserSerializer;
use AppBundle\Service\PasswordPwnedChecker;
use Exception;
use Firebase\JWT\JWT;
use Pimcore\Tool;
use Respect\Validation\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Respect\Validation\Validator as v;
use Swagger\Annotations as SWG;

class AuthController extends ApiController
{
    /**
     * @var UserSerializer
     */
    private $serializer;

    /**
     * AuthController constructor.
     * @param UserSerializer $serializer
     */
    public function __construct(UserSerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @Route("/auth/login", name="app_auth_login", methods={"POST"})
     * @return Response
     *
     * @throws Exception
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
            $payload = [
                'iss' => Tool::getHostUrl(),
                'aud' => Tool::getHostUrl(),
                'iat' => time(),
                'sub' => 'user',
                'uid' => $user->getId(),
                'email' => $user->getEmail()
            ];

            return $this->success($this->serializer->serializeResource($user), 200, [
                'jwtToken' => JWT::encode($payload, $this->getParameter('secret'))
            ]);
        }
    }

    /**
     * @Route("/auth/register", methods={"PUT"})
     * @param Request $request
     *
     * @param PasswordPwnedChecker $passwordChecker
     * @return JsonResponse
     * @SWG\Parameter(
     *   name="body",
     *   in="body",
     *   required=true,
     *   @SWG\Schema(
     *       @SWG\Property(
     *          property="username",
     *          type="string",
     *          minimum=3,
     *          maximum=64,
     *       ),
     *       @SWG\Property(
     *          property="password",
     *          minimum=4,
     *          type="string",
     *       ),
     *       @SWG\Property(
     *          property="email",
     *          type="string",
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
     *       ),
     *       @SWG\Property(
     *          property="forcePassword",
     *          type="boolean",
     *          description="By default api checks for pwned password. Use this parameter to disable password lookup."
     *       )
     *    )
     * )
     * @SWG\Response(response=201, description="User successfuly created")
     * @SWG\Response(response=422, description="Validation error. Check submitted json for errors.")
     * @SWG\Response(response=400, description="Invalid request. Check if request body is valid json.")
     */
    public function registerAction(Request $request, PasswordPwnedChecker $passwordChecker)
    {
        $input = $this->getRequestBodyJson($request);

        $validators = v::key('username', v::stringType()->length(3,64))
            ->key('password', v::stringType()->length(6, null))
            ->key('email', v::email())
            ->key('password_pwned', v::callback(function($password) use ($input, $passwordChecker) {
                if (isset($input['forcePassword'])) {
                    return true;
                }

                return !$passwordChecker->isPwned($password);
            }));

        try {
            $input['password_pwned'] = $input['password'];
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
                    'password' => $this->get('translator')->trans('auth.register.errors.password_too_short'),
                    'password_pwned' => $this->get('translator')->trans('auth.register.errors.password_pwned'),
                ])
            );

            return $this->error($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DuplicateUserException $e) {
            return $this->error($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            return $this->error('Model validation failed.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}