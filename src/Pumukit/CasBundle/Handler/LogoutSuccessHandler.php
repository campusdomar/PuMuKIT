<?php

namespace Pumukit\CasBundle\Handler;

use Pumukit\CasBundle\Services\CASService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

/**
 * Class LogoutSuccessHandler.
 */
class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    protected $casService;
    private $router;

    /**
     * LogoutSuccessHandler constructor.
     *
     * @param UrlGeneratorInterface $router
     * @param CASService            $casService
     */
    public function __construct(UrlGeneratorInterface $router, CASService $casService)
    {
        $this->router = $router;
        $this->casService = $casService;
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response|void
     */
    public function onLogoutSuccess(Request $request)
    {
        $url = $this->router->generate('pumukit_webtv_index_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->casService->logoutWithRedirectService($url);
    }
}
