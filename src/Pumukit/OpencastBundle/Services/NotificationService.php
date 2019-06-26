<?php

namespace Pumukit\OpencastBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\OpencastBundle\Event\ImportEvent;
use Pumukit\NotificationBundle\Services\SenderService;

class NotificationService
{
    protected $dm;
    protected $senderService;
    protected $template;
    protected $subject;
    protected $accessUrl;

    /**
     * OpencastNotificationService constructor.
     *
     * @param DocumentManager            $documentManager
     * @param SenderService              $senderService
     * @param string                     $template
     * @param string                     $accessUrl
     */
    public function __construct(DocumentManager $documentManager, SenderService $senderService, $template, $accessUrl, $subject)
    {
        $this->dm = $documentManager;
        $this->senderService = $senderService;
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
        if(!$multimediaObject){
            return;
        }
        $emailsList = [];
        foreach($multimediaObject->getPeopleByRoleCod('owner', true) as $person){
            $owner = $this->dm->getRepository('PumukitSchemaBundle:User')->findOneBy(['person' =>$person->getId()]);
            if(!$owner){
                //TODO: Log. This should never happen
                continue;
            }
            $emailsList[$owner->getEmail()] = $owner->getFullname();
        }
        $users = $this->dm->getRepository('PumukitSchemaBundle:User')->findUsersInAnyGroups($multimediaObject->getGroups()->toArray());
        foreach($users as $owner){
            $emailsList[$owner->getEmail()] = $owner->getFullname();
        }
        $emailsList = array_unique($emailsList);
        $backofficeUrl = $this->accessUrl.'?multimediaObject='.$multimediaObject->getId();
        $parameters = [
            'url' => $backofficeUrl,
            'multimediaObject' => $multimediaObject,
        ];
        foreach($emailsList as $email => $name){
            $parameters['username'] = $name;
            $output = $this->senderService->sendEmails($email, $this->subject, $this->template, $parameters, false, true);
        }
        return;
    }
}
