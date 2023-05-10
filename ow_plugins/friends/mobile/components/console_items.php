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
 * Console friends section items component
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.friends.mobile.components
 * @since 1.6.0
 */
class FRIENDS_MCMP_ConsoleItems extends OW_MobileComponent
{
    /**
     * Constructor.
     */
    public function __construct(  $limit, $exclude = null )
    {
        parent::__construct();

        $service = FRIENDS_BOL_Service::getInstance();

        $userId = OW::getUser()->getId();
        $requests = $service->findRequestList($userId, time(), 0, $limit, $exclude);
        $items = self::prepareData($requests);

        $this->assign('items', $items);

        // Mark as viewed
        $service->markAllViewedByUserId($userId);

        $requestIdList = array();
        foreach ( $requests as $id => $request )
        {
            $requestIdList[] = $id;
        }

        $exclude = is_array($exclude) ? array_merge($exclude, $requestIdList) : $requestIdList;
        $loadMore = (bool) $service->count(null, $userId, FRIENDS_BOL_Service::STATUS_PENDING, null, null, $exclude);

        if ( !$loadMore )
        {
            $script = "OWM.trigger('mobile.console_hide_friends_load_more', {});";
            OW::getDocument()->addOnloadScript($script);
        }
    }

    public static function prepareData( $requests )
    {
        $userIdList = array();
        foreach ( $requests as $request )
        {
            if ( !in_array($request->userId, $userIdList) )
            {
                array_push($userIdList, $request->userId);
            }
        }

        $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars($userIdList, true, true, true, false);

        $lang = OW::getLanguage();
        $items = array();
        foreach ( $requests as $request )
        {
            $items[$request->id] = array(
                'userId' => $request->userId,
                'avatar' => $avatars[$request->userId],
                'viewed' => false,
                'string' => $lang->text(
                    'friends',
                    'console_request_item',
                    array('userUrl' => $avatars[$request->userId]['url'], 'displayName' => $avatars[$request->userId]['title'])
                )
            );
        }

        return $items;
    }
}