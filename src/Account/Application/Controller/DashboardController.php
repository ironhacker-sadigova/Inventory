<?php

use Account\Application\Service\AccountViewFactory;
use Account\Domain\Account;
use Account\Domain\AccountRepository;
use Core\Annotation\Secure;
use MyCLabs\ACL\ACLManager;
use User\Domain\ACL\Actions;
use MyCLabs\ACL\Model\ClassResource;
use User\Domain\User;

/**
 * @author matthieu.napoli
 */
class Account_DashboardController extends Core_Controller
{
    /**
     * @Inject
     * @var AccountRepository
     */
    private $accountRepository;

    /**
     * @Inject
     * @var ACLManager
     */
    private $aclManager;

    /**
     * @Inject
     * @var AccountViewFactory
     */
    private $accountViewFactory;

    public function init()
    {
        $this->_helper->layout->setLayout('layout2');
    }

    /**
     * @Secure("loggedIn")
     */
    public function indexAction()
    {
        $session = new Zend_Session_Namespace(get_class());

        // Un compte spécifique est demandé
        if ($this->getParam('id') !== null) {
            $session->accountId = $this->getParam('id');
            $this->redirect('account/dashboard');
            return;
        }

        /** @var User $user */
        $user = $this->_helper->auth();

        // Tous les comptes que l'utilisateur peut voir
        $accounts = $this->accountRepository->getTraversableAccounts($user);

        /** @var Account $account */
        if (isset($session->accountId)) {
            $account = $this->accountRepository->get($session->accountId);
            // Teste si l'utilisateur peut voir le compte demandé
            if (! $this->aclManager->isAllowed($user, Actions::TRAVERSE, $account)) {
                $account = current($accounts);
            }
        } else {
            $account = current($accounts);
        }

        // Account view
        $accountView = $this->accountViewFactory->createAccountView($account, $user);

        $this->view->assign('accountList', $accounts);
        $this->view->assign('account', $accountView);
        $this->view->assign('canCreateOrganization', $this->aclManager->isAllowed(
            $user,
            Actions::CREATE,
            new ClassResource(Orga_Model_Organization::class)
        ));
    }
}
