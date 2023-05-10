<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Event_Cron extends OW_Cron
{
    public function __construct()
    {
        parent::__construct();

        $this->addJob('clearInvitations', 20);
    }

    public function run()
    {
        //ignore
    }

    public function clearInvitations()
    {        
        $list = EVENT_BOL_EventService::getInstance()->findCronExpiredEvents(0, 1500);

        if ( !empty($list) )
        {
            /* @var $event EVENT_BOL_Event */
            foreach ( $list as $event )
            {
                EVENT_BOL_EventService::getInstance()->clearEventInvitations($event->id);

                OW::getEventManager()->call('invitations.remove', array(
                    'entityType' => 'event',
                    'entityId' => $event->id
                ));

                OW::getEventManager()->call('invitations.remove', array(
                    'entityType' => EVENT_CLASS_InvitationHandler::INVITATION_JOIN,
                    'entityId' => $event->id
                ));

                OW::getEventManager()->call('notifications.remove', array(
                    'entityType' => 'event',
                    'entityId' => $event->id
                ));
            }
        }


    }
}