<?php
namespace AppBundle\Service;

use AppBundle\Model\DataObject\User;
use Pimcore\Model\DataObject\Quest;
use Pimcore\Model\Tool\Email\Log;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class GdprExporter
{
    /**
     * @var UserInterface
     */
    private $user;

    public function __construct(TokenStorageInterface $storage)
    {
        $this->user = $storage->getToken()->getUser();
    }

    public function getUser()
    {
        return User::getById($this->user->getId());
    }

    public function getEmails()
    {
        $list = new Log\Listing();

        if (!\Pimcore::inDebugMode()) {
            $list->addConditionParam('`to` = ?', $this->getUser()->getEmail());
        }

        return $list->load();
    }

    public function getQuests()
    {
        $list = new Quest\Listing();
        $list->setCondition('user__id = ?', $this->getUser()->getId());

        return $list->load();
    }
}