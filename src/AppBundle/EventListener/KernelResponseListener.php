<?php
namespace AppBundle\EventListener;

use AppBundle\Model\DataObject\User;
use AppBundle\Security\JwtAuthenticator;
use AppBundle\Service\JwtTokenGenerator;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class KernelResponseListener
{
    const TOKEN_REFRESH_BEFORE_INVALIDATION = 600; // 10 minutes before

    /**
     * @var JwtTokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var User|null
     */
    private $user;

    /**
     * KernelResponseListener constructor.
     * @param JwtTokenGenerator $tokenGenerator
     */
    public function __construct(JwtTokenGenerator $tokenGenerator, TokenStorage $storage)
    {
        $this->tokenGenerator = $tokenGenerator;

        if ($storage->getToken() === null) {
            return;
        }
        $this->user = $storage->getToken()->getUser();
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!($token = $request->headers->get('X-AUTH-TOKEN')) || !$this->user) {
            return;
        }

        try {
            $payload = $this->tokenGenerator->decodePayload($token);

            if (\Pimcore::inDebugMode()) {
                $tokenAge = time() - $payload->iat;

                if (JwtAuthenticator::JWT_TOKEN_TTL - $tokenAge < self::TOKEN_REFRESH_BEFORE_INVALIDATION) {
                    $response = $event->getResponse();
                    $refreshedToken = $this->tokenGenerator->getPayload($this->user);

                    $response->headers->set('X-SET-AUTH-TOKEN', $refreshedToken);
                }
            }

        } catch (\Exception $e) {

        }



    }
}