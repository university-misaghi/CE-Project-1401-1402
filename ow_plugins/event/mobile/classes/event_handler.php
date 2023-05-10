<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */

/**
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.event.mobile.classes
 * @since 1.6.0
 */
class EVENT_MCLASS_EventHandler
{
    /**
     * Class instance
     *
     * @var EVENT_MCLASS_EventHandler
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return EVENT_MCLASS_EventHandler
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function setInvitationData( OW_Event $event )
    {
        $params = $event->getParams();
        if ( $params['entityType'] == 'event-join' )
        {
            $data = $params['data'];
            $data['string']['vars']['event'] = strip_tags($data['string']['vars']['event']);
            $data['acceptCommand'] = 'events.accept';
            $data['declineCommand'] = 'events.ignore';
            $event->setData($data);
        }
    }

    public function onCommand( OW_Event $event )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            return;
        }

        $params = $event->getParams();

        if ( !in_array($params['command'], array('events.accept', 'events.ignore')) )
        {
            return;
        }

        $eventId = $params['data'];
        $eventDto = EVENT_BOL_EventService::getInstance()->findEvent($eventId);

        $userId = OW::getUser()->getId();
        $eventService = EVENT_BOL_EventService::getInstance();

        if ( empty($eventDto) )
        {
            BOL_InvitationService::getInstance()->deleteInvitation(
                EVENT_CLASS_InvitationHandler::INVITATION_JOIN, $eventId, $userId
            );

            return;
        }

        $lang = OW::getLanguage();
        $result = array('result' => false);

        if ( $params['command'] == 'events.accept' )
        {
            $exit = false;
            $attendedStatus = 1;

            if ( $eventService->canUserView($eventId, $userId) )
            {
                $eventDto = $eventService->findEvent($eventId);

                if ( $eventDto->getEndTimeStamp() < time() )
                {
                    $eventService->deleteUserEventInvites((int)$eventId, $userId);
                    $result['msg'] = $lang->text('event', 'user_status_updated');
                    $event->setData($result);

                    return;
                }

                $eventUser = $eventService->findEventUser($eventId, $userId);

                if ( $eventUser !== null && (int) $eventUser->getStatus() === (int) $attendedStatus )
                {
                    $result['msg'] = $lang->text('event', 'user_status_not_changed_error');
                    $exit = true;
                }

                if ( $eventDto->getUserId() == OW::getUser()->getId() && (int) $attendedStatus == EVENT_BOL_EventService::USER_STATUS_NO )
                {
                    $result['msg'] = $lang->text('event', 'user_status_author_cant_leave_error');
                    $exit = true;
                }

                if ( !$exit )
                {
                    $eventUserDto = EVENT_BOL_EventService::getInstance()->addEventUser($userId, $eventId, $attendedStatus);

                    if ( !empty( $eventUserDto ) )
                    {
                        $e = new OW_Event(
                            EVENT_BOL_EventService::EVENT_ON_CHANGE_USER_STATUS,
                            array('eventId' => $eventDto->id, 'userId' => $eventUserDto->userId)
                        );
                        OW::getEventManager()->trigger($e);

                        $result = array('result' => true, 'msg' => $lang->text('event', 'user_status_updated'));
                        BOL_InvitationService::getInstance()->deleteInvitation(
                            EVENT_CLASS_InvitationHandler::INVITATION_JOIN, $eventId, $userId
                        );
                    }
                    else
                    {
                        $result['msg'] = $lang->text('event', 'user_status_update_error');
                    }
                }
            }
            else
            {
                $result['msg'] = $lang->text('event', 'user_status_update_error');
            }
        }
        else if ( $params['command'] == 'events.ignore' )
        {
            $eventService->deleteUserEventInvites((int)$eventId, $userId);
            $result = array('result' => true, 'msg' => $lang->text('event', 'user_status_updated'));
            BOL_InvitationService::getInstance()->deleteInvitation(
                EVENT_CLASS_InvitationHandler::INVITATION_JOIN, $eventId, $userId
            );
        }

        $event->setData($result);
    }

    public function init()
    {
        $em = OW::getEventManager();
        $em->bind('mobile.invitations.on_item_render', array($this, 'setInvitationData'));
        $em->bind('invitations.on_command', array($this, 'onCommand'));
        $em->bind('feed.on_item_render', array($this, 'onFeedItemRenderDisableActions'));
    }

    public function onFeedItemRenderDisableActions( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params["action"]["entityType"] != 'event' )
        {
            return;
        }

        $data = $event->getData();

        $data["disabled"] = true;

        $event->setData($data);
    }
}