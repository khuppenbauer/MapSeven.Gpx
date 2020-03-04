<?php

namespace MapSeven\Gpx\Command;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Security\AccountFactory;
use Neos\Flow\Security\AccountRepository;

/**
 * Account Command controller for the MapSeven.Gpx package
 *
 * @Flow\Scope("singleton")
 */
class AccountCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var AccountFactory
     */
    protected $accountFactory;

    /**
     * @Flow\Inject
     * @var AccountRepository
     */
    protected $accountRepository;


    /**
     * creates an account
     *
     * @param string $user
     * @param string $password
     * @param string $role
     * @param string $providername
     */
    public function createAccountCommand($user, $password, $role, $providername = 'TokenProvider')
    {
        $account = $this->accountFactory->createAccountWithPassword($user, $password, [$role], $providername);
        $this->accountRepository->add($account);
    }
}
