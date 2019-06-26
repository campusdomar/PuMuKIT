<?php

namespace Pumukit\OpencastBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\OpencastBundle\Event\ImportEvent;
use Pumukit\NotificationBundle\Services\SenderService;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class NotificationService
{
    protected $dm;
    protected $senderService;
    protected $router;
    protected $logger;
    protected $template;
    protected $subject;
    protected $accessUrl;

    /**
     * OpencastNotificationService constructor.
     *
     * @param DocumentManager $documentManager
     * @param SenderService   $senderService
     * @param Router          $router
     * @param LoggerInterface $logger
     * @param string          $template
     * @param string          $accessUrl
     */
    public function __construct(DocumentManager $documentManager, SenderService $senderService, Router $router, LoggerInterface $logger, $template, $accessUrl, $subject)
    {
        $this->dm = $documentManager;
        $this->senderService = $senderService;
        $this->router = $router;
        $this->logger = $logger;
        $this->template = $template;
        $this->accessUrl = $accessUrl;
        $this->subject = $subject;
    }

    /**
     * On Opencast import success.
     *
     * @param JobEvent $event
     *
     * @return bool
     */
    public function onImportSuccess(ImportEvent $event)
    {
        $multimediaObject = $event->getMultimediaObject();
        if (!$multimediaObject) {
            return;
        }
        $emailsList = [];
        foreach ($multimediaObject->getPeopleByRoleCod('owner', true) as $person) {
            $owner = $this->dm->getRepository('PumukitSchemaBundle:User')->findOneBy(['person' => $person->getId()]);
            if (!$owner) {
                $this->logger->error(__CLASS__.'['.__FUNCTION__.'] Person ('.$person->getId().') assigned as owner of multimediaObject ('.$multimediaObject->getId().') does NOT have an associated USER!');
                continue;
            }
            $emailsList[$owner->getEmail()] = $owner->getFullname();
        }
        $users = $this->dm->getRepository('PumukitSchemaBundle:User')->findUsersInAnyGroups($multimediaObject->getGroups()->toArray());
        foreach ($users as $owner) {
            $emailsList[$owner->getEmail()] = $owner->getFullname();
        }
        $emailsList = array_unique($emailsList);

        $backofficeUrl = $this->accessUrl.'?multimediaObject='.$multimediaObject->getId();
        try {
            $backofficeUrl = $this->router->generate($this->accessUrl, ['id' => $multimediaObject->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (RouteNotFoundException $e) {
            $this->logger->info(__CLASS__.'['.__FUNCTION__.'] Route name "'.$backofficeUrl.'" not found. Using as route literally.');
        }
        $parameters = [
            'url' => $backofficeUrl,
            'multimediaObject' => $multimediaObject,
        ];
        foreach ($emailsList as $email => $name) {
            $parameters['username'] = $name;
            $output = $this->senderService->sendEmails($email, $this->subject, $this->template, $parameters, false, true);
        }

        return;
    }
}
