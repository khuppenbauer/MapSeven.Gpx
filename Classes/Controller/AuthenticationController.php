<?php
namespace MapSeven\Gpx\Controller;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Annotations\SkipCsrfProtection;
use Neos\Flow\Mvc\Controller\RestController;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Flow\Security\Context as SecurityContext;

/**
 * Authentication controller for the MapSeven.Gpx package
 *
 * @Flow\Scope("singleton")
 */
class AuthenticationController extends RestController
{  

    const JSON_VIEW = 'Neos\\Flow\\Mvc\\View\JsonView';

    /**
     * @Flow\Inject
     * @var SecurityContext
     */
    protected $securityContext;


    /**
     * Return user if authentication is successful
     * 
     * @SkipCsrfProtection
     * @return void
     */
    public function authAction()
    {
        $this->view->assign('value', ['user' => $this->securityContext->getAccount()->getAccountIdentifier()]);
    }   

    /**
     * OAuth2 Callback Method
     *
     * @SkipCsrfProtection
     * @return void
     */
    public function oAuth2CallbackAction()
    {
        $this->view->assign('value', ['200' => 'success']);
    }
    
    /**
     * Overrides the standard resolveView method
     *
     * @return ViewInterface $view The view
     */
    protected function resolveView()
    {
        $viewObjectName = self::JSON_VIEW;
        $view = $this->objectManager->get($viewObjectName);
        return $view;
    }
}