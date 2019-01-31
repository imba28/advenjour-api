<?php
namespace AppBundle\Security;

use AppBundle\JsonAPI\Document;
use AppBundle\JsonAPI\ErrorObject;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class JwtAuthenticator
 * @package AppBundle\Security
 */
class JwtAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var string
     */
    private $secret;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * JwtUserProvider constructor.
     * @param string $secret
     * @param TranslatorInterface $translator
     */
    public function __construct(string $secret, TranslatorInterface $translator)
    {
        $this->secret = $secret;
        $this->translator = $translator;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     *
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request)
    {
        return $request->headers->has('X-AUTH-TOKEN');
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     *
     * @param Request $request
     * @return array
     */
    public function getCredentials(Request $request)
    {
        return [
            'token' => $request->headers->get('X-AUTH-TOKEN'),
        ];
    }

    /**
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     * @return UserInterface|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $errorMessage = $this->translator->trans('auth.authentication_required');

        $token = $credentials['token'];
        if ($token === null) {
            throw new HttpException(403, $errorMessage);
        }

        $payload = $this->getPayload($token);
        if (!$payload || !$payload->uid || !$payload->email) {
            throw new HttpException(403, $errorMessage);
        }

        $user = $userProvider->loadUserByUsername($payload->email);
        if (!$user instanceof UserInterface) {
            throw new HttpException(403, $errorMessage);
        }

        return $user;
    }

    /**
     * @todo increase security checks
     * @param mixed $credentials
     * @param UserInterface $user
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        $payload = $this->getPayload($credentials['token']);

        if ($payload->email !== $user->getEmail()) {
            return false;
        }

        return true;
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return JsonResponse|Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $document = new Document();
        $document->addError(new ErrorObject($exception));

        return new JsonResponse($document, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent
     *
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return JsonResponse
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $document = new Document();
        $document->addError(new ErrorObject(new HttpException(403, $this->translator->trans('auth.authentication_required'))));

        return new JsonResponse($document, Response::HTTP_FORBIDDEN);
    }

    /**
     * Authentication is stateless, so we don not support this feature.
     *
     * @return bool
     */
    public function supportsRememberMe()
    {
        return false;
    }

    /**
     * @param $token
     * @return object
     */
    protected function getPayload($token)
    {
        return JWT::decode($token, $this->secret, ['HS256']);
    }
}