<?php
namespace AppBundle\EventListener;

use AppBundle\JsonAPI\Document;
use AppBundle\JsonAPI\ErrorObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Translation\TranslatorInterface;

class AuthenticationFailureHandler extends DefaultAuthenticationFailureHandler
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $document = new Document();
        $document->addError(new ErrorObject(new HttpException(403, $this->translator->trans('auth.login.errors.invalid_credentials'))));

        return new JsonResponse($document);
    }
}
