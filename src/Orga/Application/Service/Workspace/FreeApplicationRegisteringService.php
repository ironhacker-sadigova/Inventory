<?php

namespace Orga\Application\Service\Workspace;

use Core_Locale;
use DateTime;
use Core\ContainerSingleton;
use Doctrine\ORM\EntityManager;
use Exception;
use MyCLabs\ACL\ACL;
use User\Domain\UserService;
use User\Domain\User;
use Orga\Domain\Workspace;
use Orga\Domain\Member;
use Orga\Domain\ACL\CellManagerRole;

/**
 * Service gérant l'application publique et gratuite.
 */
class FreeApplicationRegisteringService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var ACL
     */
    private $acl;

    /**
     * @var string
     */
    private $applicationUrl;


    /**
     * @param EntityManager $entityManager
     * @param UserService $userService
     * @param ACL $acl
     * @param string $applicationUrl
     */
    public function __construct(
        EntityManager $entityManager,
        UserService $userService,
        ACL $acl,
        $applicationUrl
    ) {
        $this->entityManager = $entityManager;
        $this->userService = $userService;
        $this->acl = $acl;
        $this->applicationUrl = $applicationUrl;
    }

    /**
     * @param $email
     * @param $password
     */
    public function createOrAddUserToIndividualDemo($email)
    {
        return $this->createOrAddUserToDemo('individual', $email);
    }

    /**
     * @param $email
     * @param $password
     */
    public function createOrAddUserToCollectivityDemo($email)
    {
        return $this->createOrAddUserToDemo('collectivity', $email);
    }

    /**
     * @param $email
     * @param $password
     */
    public function createOrAddUserToSMEsDemo($email)
    {
        return $this->createOrAddUserToDemo('smes', $email);
    }

    /**
     * @param string $demo
     * @param string $email
     * @param string $password
     */
    protected function createOrAddUserToDemo($demo, $email)
    {
        $container = ContainerSingleton::getContainer();

        try {
            $this->entityManager->beginTransaction();

            /** @var Workspace $workspace */
            $workspace = Workspace::load($container->get('feature.workspace.' . $demo . '.register'));
            $userAxis = $workspace->getAxisByRef($container->get('feature.workspace.' . $demo . '.userAxis.ref'));
            //Création du membre de l'utilisateur.
            $userMember = new Member($userAxis, sha1($email . ' ' . time()));
            $userMember->getLabel()->set(
                $container->get('feature.workspace.' . $demo . '.label.fr') . ' ' .
                '(' . Core_Locale::load('fr')->formatDateTime(new DateTime()) . ')',
                'fr'
            );
            $userMember->getLabel()->set(
                $container->get('feature.workspace.' . $demo . '.label.en') . ' ' .
                '(' . Core_Locale::load('en')->formatDateTime(new DateTime()) . ')',
                'en'
            );
            $userMember->save();
            $userCell = $workspace->getGranularityByRef($userAxis->getRef())->getCellByMembers([$userMember]);

            $this->entityManager->flush();

            $isCreation = false;
            if (User::isEmailUsed($email)) {
                // Récupération de l'utilisateur.
                $user = User::loadByEmail($email);
                $password = null;
            } else {
                $isCreation = true;
                // Création de l'utilisateur.
                $user = $this->userService->createUser($email, null);
                $user->initTutorials();
                $password = $user->setRandomPassword();
                $this->entityManager->flush();
            }

            $role = new CellManagerRole($user, $userCell);
            $this->acl->grant($user, $role);

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        if ($isCreation) {
            // Envoi d'un mail à la fin de la création.
            $this->userService->sendEmail(
                $user,
                __('User', 'email', 'subjectAccountCreated'), // variable $demo pour le nom du worspace
                __('User', 'emailSignUp', 'bodyAccountCreated', [
                    'EMAIL' => $email,
                    'PASSWORD' => $password,
                    'URL_APPLICATION' => $this->applicationUrl . '/',
                ])
            );
        } else {
            // Envoi d'un mail notifiant la création du nouvel accès.
            $this->userService->sendEmail(
                $user,
                __('User', 'emailSignup', 'subjectAccessAdded' . $demo),
                __('User', 'emailSignup', 'bodyAccess_' . $demo )
            );
        }

        return $password;
    }
}
