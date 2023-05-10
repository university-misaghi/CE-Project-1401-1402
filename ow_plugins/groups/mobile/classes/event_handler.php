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
class GROUPS_MCLASS_EventHandler
{
    /**
     * Class instance
     *
     * @var GROUPS_MCLASS_EventHandler
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return GROUPS_MCLASS_EventHandler
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function onInvitationCommand( OW_Event $event )
    {
        $params = $event->getParams();

        $result = array('result' => false);
        if ( !in_array($params['command'], array('groups.accept', 'groups.ignore')) )
        {
            return;
        }

        $groupId = $params['data'];
        $userId = OW::getUser()->getId();

        if ( $params['command'] == 'groups.accept' )
        {
            GROUPS_BOL_Service::getInstance()->addUser($groupId, $userId);
            $result = array('result' => true, 'msg' => OW::getLanguage()->text('groups', 'join_complete_message'));
        }
        else if ( $params['command'] == 'groups.ignore' )
        {
            GROUPS_BOL_Service::getInstance()->deleteInvite($groupId, $userId);
        }

        $event->setData($result);
    }

    public function onInvitationsItemRender( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['entityType'] == 'group-join' )
        {
            $data = $params['data'];
            $data['string']['vars']['group'] = strip_tags($data['string']['vars']['group']);
            $data['acceptCommand'] = 'groups.accept';
            $data['declineCommand'] = 'groups.ignore';
            $event->setData($data);
        }
    }
    
    public function onFeedItemRenderDisableActions( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( !in_array($params["action"]["entityType"], array( GROUPS_BOL_Service::FEED_ENTITY_TYPE, "groups-join", "groups-status" )) )
        {
            return;
        }
        
        $data = $event->getData();
        
        $data["disabled"] = true;
        
        $event->setData($data);
    }
}