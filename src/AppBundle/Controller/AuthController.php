<?php
namespace AppBundle\Controller;

use AppBundle\Model\DataObject\User;
use AppBundle\Serializer\UserSerializer;
use AppBundle\Service\JwtTokenGenerator;
use AppBundle\Service\PasswordPwnedChecker;
use Exception;
use Pimcore\Log\ApplicationLogger;
use Pimcore\Mail;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Document\Email;
use Respect\Validation\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
     * @param Request $request
     * @param JwtTokenGenerator $tokenGenerator
     * @param ApplicationLogger $logger
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
    public function loginAction(Request $request, JwtTokenGenerator $tokenGenerator, ApplicationLogger $logger)
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user && $this->isGranted('ROLE_USER')) {
            $logger->info("Login with user {$user->getEmail()} from IP {$request->getClientIp()}.", [
                'component' => self::class
            ]);

            return $this->success($this->serializer->serializeResource($user), 200, [
                'jwtToken' => $tokenGenerator->getPayload($user)
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
     *          property="isPublic",
     *          type="boolean",
     *          description="is public profile"
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
            if (User::getByEmail($input['email'], 1)) {
                throw new DuplicateUserException("User with {$input['email']} already exists");
            }

            $input['password_pwned'] = $input['password'];
            $validators->assert($input);

            $user = User::create($input);
            $user->setKey($input['email']);
            $user->setParent(Folder::getByPath('/users/not-confirmed'));
            $user->setPublished(true);
            $user->setActivationHash(hash('sha256', $user->getEmail() . time()));

            $user->save();

            if (\Pimcore::inDebugMode() && ($document = Email::getByPath('/auth/registrationConfirmation'))) {
                $email = new Mail();
                $email->setDocument($document);
                $email->setTo($user->getEmail(), "{$user->getFirstname()} {$user->getLastname()}");
                $email->setParams([
                    'activationHash' => $user->getActivationHash(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname()
                ]);

                $email->send();
            }

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
            return $this->error($this->get('translator')->trans('auth.register.errors.email'), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            return $this->error('Model validation failed.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @Route("/auth/confirm/{activationHash}", methods={"GET"})
     */
    public function confirmationAction(Request $request)
    {
        /** @var User $user */
        if (!($user = User::getByActivationHash($request->get('activationHash'), 1))) {
            throw new NotFoundHttpException('auth.confirmation.user_not_found');
        }

        $user->setActive(true);
        $user->setEmailConfirmed(true);
        $user->setParent(Folder::getByPath('/users'));
        $user->save();

        return $this->success([], Response::HTTP_CREATED);
    }
}