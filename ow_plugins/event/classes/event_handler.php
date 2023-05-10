<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
class EVENT_CLASS_EventHandler
{

    public function __construct()
    {
        
    }

    public function onNotifyActions( BASE_CLASS_EventCollector $e )
    {
        $e->add(array(
            'section' => 'event',
            'action' => 'event-invitation',
            'sectionIcon' => 'ow_ic_calendar',
            'sectionLabel' => OW::getLanguage()->text('event', 'notifications_section_label'),
            'description' => OW::getLanguage()->text('event', 'notifications_new_message'),
            'selected' => true
        ));

        $e->add(array(
            'section' => 'event',
            'sectionIcon' => 'ow_ic_files',
            'sectionLabel' => OW::getLanguage()->text('event', 'notifications_section_label'),
            'action' => 'event-add_comment',
            'description' => OW::getLanguage()->text('event', 'email_notification_comment_setting'),
            'selected' => true
        ));
    }

    public function onUserInvite( OW_Event $e )
    {
        $params = $e->getParams();

        OW::getCacheManager()->clean(array(EVENT_BOL_EventUserDao::CACHE_TAG_EVENT_USER_LIST . $params['eventId']));
    }

    /**
     * Add event entity to the newsfeed
     *
     * @param OW_Event $e
     */
    public function feedEntityAdd( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        if ( $params['entityType'] != 'event' )
        {
            return;
        }

        $eventService = EVENT_BOL_EventService::getInstance();
        $event = $eventService->findEvent($params['entityId']);

        //    if ( $event->getWhoCanView() == EVENT_BOL_EventService::CAN_VIEW_INVITATION_ONLY )
        //    {
        //        return;
        //    }
        //$url = OW::getRouter()->urlForRoute('event.view', array('eventId' => $event->getId()));
        //$thumb = $eventService->generateImageUrl($event->image, true);

        $title = UTIL_String::truncate(strip_tags($event->getTitle()), 100, "...");


        /* $data = array(
          'time' => $event->getCreateTimeStamp(),
          'ownerId' => $event->getUserId(),
          'string' => OW::getLanguage()->text('event', 'feed_add_item_label'),
          'content' => '<div class="clearfix"><div class="ow_newsfeed_item_picture">
          <a href="' . $url . '"><img src="' . ( $event->getImage() ? $eventService->generateImageUrl($event->getImage(), true) : $eventService->generateDefaultImageUrl() ) . '" /></a>
          </div><div class="ow_newsfeed_item_content">
          <a class="ow_newsfeed_item_title" href="' . $url . '">' . $title . '</a><div class="ow_remark ow_smallmargin">' . UTIL_String::truncate(strip_tags($event->getDescription()), 200, '...') . '</div><div class="ow_newsfeed_action_activity event_newsfeed_activity">[ph:activity]</div></div></div>',
          'view' => array(
          'iconClass' => 'ow_ic_calendar'
          )
          ); */

        $data = array(
            'time' => $event->getCreateTimeStamp(),
            'ownerId' => $event->getUserId(),
            'string' => array("key" => "event+feed_add_item_label"),
            'content' => array(
                "format" => "image_content",
                "vars" => array(
                    "image" => ( $event->getImage() ? $eventService->generateImageUrl($event->getImage(), false) : $eventService->generateDefaultImageUrl() ),
                    "thumbnail" => ( $event->getImage() ? $eventService->generateImageUrl($event->getImage(), true) : $eventService->generateDefaultImageUrl() ),
                    "title" => $title,
                    "description" => UTIL_String::truncate(strip_tags($event->getDescription()), 200, '...'),
                    "url" => array(
                        "routeName" => 'event.view',
                        "vars" => array('eventId' => $event->getId())
                    ),
                    'iconClass' => 'ow_ic_event'
                )
            ),
            'view' => array(
                'iconClass' => 'ow_ic_calendar'
            ),
        );

        if ( $event->getWhoCanView() == EVENT_BOL_EventService::CAN_VIEW_INVITATION_ONLY )
        {
            $data['params']['visibility'] = 14; // VISIBILITY_FOLLOW + VISIBILITY_AUTHOR + VISIBILITY_FEED
        }

        $e->setData($data);
    }

    public function afterEventEdit( OW_Event $e )
    {
        $params = $e->getParams();
        $eventId = (int) $params['eventId'];

        $eventService = EVENT_BOL_EventService::getInstance();
        $event = $eventService->findEvent($eventId);

        //$url = OW::getRouter()->urlForRoute('event.view', array('eventId' => $event->getId()));
        // $thumb = $eventService->generateImageUrl($event->image, true);

        /* $data = array(
          'time' => $event->getCreateTimeStamp(),
          'ownerId' => $event->getUserId(),
          'string' => OW::getLanguage()->text('event', 'feed_add_item_label'),
          'content' => '<div class="clearfix"><div class="ow_newsfeed_item_picture">
          <a href="' . $url . '"><img src="' . $thumb . '" /></a>
          </div><div class="ow_newsfeed_item_content">
          <a class="ow_newsfeed_item_title" href="' . $url . '">' . $event->getTitle() . '</a><div class="ow_remark ow_smallmargin">' . UTIL_String::truncate(strip_tags($event->getDescription()), 200, '...') . '</div><div class="ow_newsfeed_action_activity event_newsfeed_activity">[ph:activity]</div></div></div>',
          'view' => array(
          'iconClass' => 'ow_ic_calendar'
          )
          ); */
        $title = UTIL_String::truncate(strip_tags($event->getTitle()), 100, "...");

        $data = array(
            'time' => $event->getCreateTimeStamp(),
            'ownerId' => $event->getUserId(),
            'string' => array("key" => "event+feed_add_item_label"), //OW::getLanguage()->text('event', 'feed_add_item_label'),
            'content' => array(
                "format" => "image_content",
                "vars" => array(
                    "image" => ( $event->getImage() ? $eventService->generateImageUrl($event->getImage(), false) : $eventService->generateDefaultImageUrl() ),
                    "thumbnail" => ( $event->getImage() ? $eventService->generateImageUrl($event->getImage(), true) : $eventService->generateDefaultImageUrl() ),
                    "title" => $title,
                    "description" => UTIL_String::truncate(strip_tags($event->getDescription()), 200, '...'),
                    "url" => array(
                        "routeName" => 'event.view',
                        "vars" => array('eventId' => $event->getId())
                    ),
                    'iconClass' => 'ow_ic_event'
                )
            ),
            'view' => array(
                'iconClass' => 'ow_ic_calendar'
            ),
        );

        if ( $event->getWhoCanView() == EVENT_BOL_EventService::CAN_VIEW_INVITATION_ONLY )
        {
            $data['params']['visibility'] = 14; // VISIBILITY_FOLLOW + VISIBILITY_AUTHOR + VISIBILITY_FEED
        }

        $event = new OW_Event('feed.action', array(
                'entityType' => 'event',
                'entityId' => $eventId,
                'pluginKey' => 'event',
                'postOnUserFeed' => 1
                ), $data);
        
        OW::getEventManager()->trigger($event);
    }

    public function addNewContentItem( BASE_CLASS_EventCollector $event )
    {
        if ( !OW::getUser()->isAuthorized('event', 'add_event') )
        {
            return;
        }

        $resultArray = array(
            BASE_CMP_AddNewContent::DATA_KEY_ICON_CLASS => 'ow_ic_calendar',
            BASE_CMP_AddNewContent::DATA_KEY_URL => OW::getRouter()->urlForRoute('event.add'),
            BASE_CMP_AddNewContent::DATA_KEY_LABEL => OW::getLanguage()->text('event', 'add_new_link_label')
        );

        $event->add($resultArray);
    }

    public function adsEnabled( BASE_CLASS_EventCollector $event )
    {
        $event->add('event');
    }

    public function isPluginActive( OW_Event $event )
    {
        $event->setData(true);
    }

    public function addAuthLabels( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $event->add(
            array(
                'event' => array(
                    'label' => $language->text('event', 'auth_group_label'),
                    'actions' => array(
                        'add_event' => $language->text('event', 'auth_action_label_add_event'),
                        'view_event' => $language->text('event', 'auth_action_label_view_event'),
                        'add_comment' => $language->text('event', 'auth_action_label_add_comment')
                    )
                )
            )
        );
    }

    public function onUserDelete( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['deleteContent']) )
        {
            return;
        }

        $userId = $params['userId'];

        EVENT_BOL_EventService::getInstance()->deleteUserEvents($userId);
    }

    public function privacyAddAction( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();

        $action = array(
            'key' => 'event_view_attend_events',
            'pluginKey' => 'event',
            'label' => $language->text('event', 'privacy_action_view_attend_events'),
            'description' => '',
            'defaultValue' => 'everybody'
        );

        $event->add($action);
    }

    public function feedOnItemRenderActivity( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();

        if ( $params['action']['entityType'] != 'event' )
        {
            return;
        }

        $eventId = $params['action']['entityId'];
        $usersCount = EVENT_BOL_EventService::getInstance()->findEventUsersCount($eventId, EVENT_BOL_EventService::USER_STATUS_YES);

        if ( $usersCount == 1 )
        {
            return;
        }

        $users = EVENT_BOL_EventService::getInstance()->findEventUsers($eventId, EVENT_BOL_EventService::USER_STATUS_YES, null, 6);

        $userIds = array();

        foreach ( $users as $user )
        {
            $userIds[] = $user->getUserId();
        }

        $activityUserIds = array();

        foreach ( $params['activity'] as $activity )
        {
            if ( $activity['activityType'] == 'event-join' )
            {
                $activityUserIds[] = $activity['data']['userId'];
            }
        }

        $lastUserId = reset($activityUserIds);
        $follows = array_intersect($activityUserIds, $userIds);
        $notFollows = array_diff($userIds, $activityUserIds);
        $idlist = array_merge($follows, $notFollows);
        $idlist = array_slice($idlist, 0, 5);
        //$avatarList = new BASE_CMP_MiniAvatarUserList();
        //$avatarList->setEmptyListNoRender(true);

        /* if ( count($idlist) > 5 )
          {
          $avatarList->setViewMoreUrl(OW::getRouter()->urlForRoute('event.main_user_list', array('eventId' => $eventId)));
          } */

        $language = OW::getLanguage();

        //$avatarList = new BASE_CMP_MiniAvatarUserList($idlist);
        //$content = $avatarList->render();

        /* if ( $lastUserId )
        {
            $userName = BOL_UserService::getInstance()->getDisplayName($lastUserId);
            $userUrl = BOL_UserService::getInstance()->getUserUrl($lastUserId);
            $content = $language->text('event', 'feed_activity_joined', array('user' => '<a href="' . $userUrl . '">' . $userName . '</a>'));
        } */

        /* $data['assign']['activity'] = array('template' => 'activity',
          'vars' => array(
          'title' => $language->text('event', 'feed_activity_users', array('usersCount' => $usersCount)),
          'content' => $content
          )); */

        $userListData = array( 'userList' => array(
            'label' => array( 'key' => 'event+feed_activity_users', 'vars' => array('usersCount' => $usersCount) ),
            'ids' => $idlist
            ) );

        $data['content']['vars'] = array_merge($data['content']['vars'], $userListData);
        $event->setData($data);
    }

    public function feedOnCollectPrivacy( BASE_CLASS_EventCollector $event )
    {
        $event->add(array('event-join', 'event_view_attend_events'));
    }

    public function feedOnCollectConfigurableActivity( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $event->add(array(
            'label' => $language->text('event', 'feed_content_label'),
            'activity' => '*:event'
        ));
    }

    public function onAddComment( OW_Event $e )
    {
        $params = $e->getParams();

        if ( empty($params['entityType']) || $params['entityType'] != 'event' )
        {
            return;
        }

        $entityId = $params['entityId'];
        $userId = $params['userId'];
        $commentId = $params['commentId'];
        $event = EVENT_BOL_EventService::getInstance()->findEvent($entityId);

        $eventUrl = OW::getRouter()->urlForRoute('event.view', array('eventId' => $event->id));

        $eventImage = null;
        if ( !empty($event->image) )
        {
            $eventImage = EVENT_BOL_EventService::getInstance()->generateImageUrl($event->image, true);
        }

        $eventDto = EVENT_BOL_EventService::getInstance()->findEvent($entityId);
        $ownerId = $eventDto->userId;
        $userName = BOL_UserService::getInstance()->getDisplayName($ownerId);
        $userUrl = BOL_UserService::getInstance()->getUserUrl($ownerId);
        $userEmbed = '<a href="' . $userUrl . '">' . $userName . '</a>';

        $string = array( "key" => 'event+feed_activity_comment_string', 'vars' => array('user' => $userEmbed) ); // OW::getLanguage()->text('event', 'feed_activity_comment_string', array('user' => $userEmbed));

        if ( !empty($eventDto) && $userId == $eventDto->userId )
        {
            $string = array( "key" => 'event+feed_activity_own_comment_string' ); //OW::getLanguage()->text('event', 'feed_activity_own_comment_string');
        }

        OW::getEventManager()->trigger(new OW_Event('feed.activity', array(
                'activityType' => 'comment',
                'activityId' => $commentId,
                'entityId' => $entityId,
                'entityType' => $params['entityType'],
                'userId' => $userId,
                'pluginKey' => 'event'
                ), array(
                'string' => $string,
                'line' => null
            )));


//    $string = OW::getLanguage()->text('event', 'email_notification_comment', array(
//            'userName' => BOL_UserService::getInstance()->getDisplayName($userId),
//            'userUrl' => BOL_UserService::getInstance()->getUserUrl($userId),
//            'url' => $eventUrl,
//            'title' => strip_tags($event->title)
//        ));
//
//    $params = array(
//        'plugin' => 'event',
//        'action' => 'event-add_comment',
//        'string' => $string,
//        'avatar' => $eventImage,
//        'content' => $comment->getMessage(),
//        'url' => $eventUrl,
//        'time' => time(),
//        'userId' => $userId
//    );

        if ( $userId != $event->userId )
        {
            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId), true, true, false, false);
            $avatar = $avatars[$userId];

            $contentImage = array();

            if ( !empty($eventImage) )
            {
                $contentImage = array('src' => $eventImage);
            }

            $event = new OW_Event('notifications.add', array(
                    'pluginKey' => 'event',
                    'entityType' => $params['entityType'],
                    'entityId' => $params['entityId'],
                    'action' => 'event-add_comment',
                    'userId' => $event->userId,
                    'time' => time()
                    ), array(
                    'avatar' => $avatar,
                    'string' => array(
                        'key' => 'event+email_notification_comment',
                        'vars' => array(
                            'userName' => BOL_UserService::getInstance()->getDisplayName($userId),
                            'userUrl' => BOL_UserService::getInstance()->getUserUrl($userId),
                            'url' => $eventUrl,
                            'title' => strip_tags($event->title)
                        )
                    ),
                    'url' => $eventUrl,
                    'contentImage' => $contentImage
                ));

            OW::getEventManager()->trigger($event);
        }
    }

    public function feedOnLike( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['entityType'] != 'event' )
        {
            return;
        }

        $userId = (int) $params['userId'];
        $entityId = $params['entityId'];

        $eventDto = EVENT_BOL_EventService::getInstance()->findEvent($entityId);
        $ownerId = $eventDto->userId;
        $userName = BOL_UserService::getInstance()->getDisplayName($ownerId);
        $userUrl = BOL_UserService::getInstance()->getUserUrl($ownerId);
        $userEmbed = '<a href="' . $userUrl . '">' . $userName . '</a>';

        $string = array( 'key' => 'event+feed_activity_event_string_like', 'vars' => array('user' => $userEmbed) ); //OW::getLanguage()->text('event', 'feed_activity_event_string_like', array('user' => $userEmbed));

        if ( !empty($eventDto) && $userId == $eventDto->userId )
        {
            //$string = OW::getLanguage()->text('event', 'feed_activity_event_string_like_own');
            $string = array( 'key' => 'event+feed_activity_event_string_like_own' );
        }

        OW::getEventManager()->trigger(new OW_Event('feed.activity', array(
                'activityType' => 'like',
                'activityId' => $params['userId'],
                'entityId' => $params['entityId'],
                'entityType' => $params['entityType'],
                'userId' => $params['userId'],
                'pluginKey' => 'event'
                ), array(
                'string' => $string,
                'line' => null
            )));
    }

    public function quickLinks( BASE_CLASS_EventCollector $event )
    {
        $service = EVENT_BOL_EventService::getInstance();
        $userId = OW::getUser()->getId();

        $eventsCount = $service->findUserParticipatedEventsCount($userId);
        $invitesCount = $service->findUserInvitedEventsCount($userId);

        if ( $eventsCount > 0 || $invitesCount > 0 )
        {
            $event->add(array(
                BASE_CMP_QuickLinksWidget::DATA_KEY_LABEL => OW::getLanguage()->text('event', 'common_list_type_joined_label'),
                BASE_CMP_QuickLinksWidget::DATA_KEY_URL => OW::getRouter()->urlForRoute('event.view_event_list', array('list' => 'joined')),
                BASE_CMP_QuickLinksWidget::DATA_KEY_COUNT => $eventsCount,
                BASE_CMP_QuickLinksWidget::DATA_KEY_COUNT_URL => OW::getRouter()->urlForRoute('event.view_event_list', array('list' => 'joined')),
                BASE_CMP_QuickLinksWidget::DATA_KEY_ACTIVE_COUNT => $invitesCount,
                BASE_CMP_QuickLinksWidget::DATA_KEY_ACTIVE_COUNT_URL => OW::getRouter()->urlForRoute('event.view_event_list', array('list' => 'invited'))
            ));
        }
    }

    public function onAddEvent( OW_Event $event )
    {
        OW::getCacheManager()->clean(array(EVENT_BOL_EventDao::CACHE_TAG_EVENT_LIST));
    }

    public function onDeleteEvent( OW_Event $event )
    {
        $params = $event->getParams();
        $eventId = !empty($params['eventId']) ? $params['eventId'] : null;

        OW::getCacheManager()->clean(array(EVENT_BOL_EventDao::CACHE_TAG_EVENT_LIST));

        if ( isset($eventId) )
        {
            OW::getCacheManager()->clean(array(EVENT_BOL_EventUserDao::CACHE_TAG_EVENT_USER_LIST . $eventId));
        }

        $event = new OW_Event('feed.delete_item', array('entityType' => 'event', 'entityId' => $eventId));
        OW::getEventManager()->trigger($event);
    }

    public function onEditEvent( OW_Event $event )
    {
        OW::getCacheManager()->clean(array(EVENT_BOL_EventDao::CACHE_TAG_EVENT_LIST));
    }

    public function onChangeUserStatus( OW_Event $event )
    {
        $params = $event->getParams();
        $eventId = !empty($params['eventId']) ? $params['eventId'] : null;
        $userId = !empty($params['userId']) ? $params['userId'] : null;

        if ( !isset($eventId) )
        {
            return;
        }

        OW::getCacheManager()->clean(array(EVENT_BOL_EventUserDao::CACHE_TAG_EVENT_USER_LIST . $eventId));

        if ( !isset($userId) )
        {
            return;
        }

        $eventDto = EVENT_BOL_EventService::getInstance()->findEvent($eventId);

        $eventUser = EVENT_BOL_EventService::getInstance()->findEventUser($eventId, $userId);

        if ( empty($eventDto) || empty($eventUser) )
        {
            return;
        }

        if ( $eventUser->getStatus() == EVENT_BOL_EventService::USER_STATUS_YES )
        {
            $userName = BOL_UserService::getInstance()->getDisplayName($eventDto->getUserId());
            $userUrl = BOL_UserService::getInstance()->getUserUrl($eventDto->getUserId());
            $userEmbed = '<a href="' . $userUrl . '">' . $userName . '</a>';

            OW::getEventManager()->trigger(new OW_Event('feed.activity', array(
                    'activityType' => 'event-join',
                    'activityId' => $eventUser->getId(),
                    'entityId' => $eventDto->getId(),
                    'entityType' => 'event',
                    'userId' => $eventUser->getUserId(),
                    'pluginKey' => 'event'
                    ), array(
                    'eventId' => $eventDto->getId(),
                    'userId' => $eventUser->getUserId(),
                    'eventUserId' => $eventUser->getId(),
                    'string' => OW::getLanguage()->text('event', 'feed_actiovity_attend_string', array('user' => $userEmbed)),
                    'feature' => array()
                )));
            
            OW::getEventManager()->trigger(new OW_Event('feed.activity', array(
                'activityType' => 'subscribe',
                'activityId' => $eventUser->getId(),
                'entityId' => $eventDto->getId(),
                'entityType' => 'event',
                'userId' => $eventUser->getUserId(),
                'pluginKey' => 'event'
                ), array()));
        }
    }

    public function sosialSharingGetEventInfo( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();
        $data['display'] = false;

        if ( empty($params['entityId']) )
        {
            return;
        }

        if ( $params['entityType'] == 'event' )
        {
            if ( !BOL_AuthorizationService::getInstance()->isActionAuthorizedForGuest('event', 'view_event') )
            {
                $event->setData($data);
                return;
            }

            $eventDto = EVENT_BOL_EventService::getInstance()->findEvent($params['entityId']);
            
            if ( !empty($eventDto) )
            {
                $data['display'] = $eventDto->getWhoCanView() == EVENT_BOL_EventService::CAN_VIEW_ANYBODY;
            }

            $event->setData($data);
        }
    }

    public function getContentMenu( OW_Event $event )
    {
        //$event->setData(EVENT_BOL_EventService::getInstance()->getContentMenu());
    }

    public function collectToolbar( BASE_CLASS_EventCollector $event )
    {
        $params = $event->getParams();
        
        if ( !empty($params['event']) && OW::getUser()->isAuthenticated() && OW::getUser()->getId() != $params['event']->getUserId() )
        {
            $eventDto = $params['event'];
            
            if ( $params['event']->status == 1 )
            {
                $item = array(
                    'label' => OW::getLanguage()->text('base', 'flag'),
                    'href' => 'javascript://',
                    'id' => 'event_toolbar_flag'
                );

                $js = UTIL_JsGenerator::newInstance();
                $js->addScript(' $(\'#event_toolbar_flag\').click(function() { OW.flagContent(\'event\', {$eventId}) } ) ', 
                        array('eventId' => $eventDto->id));
                OW::getDocument()->addOnloadScript($js->generateJs());
                
                $event->add($item);
            }

            
            // approve button
            if ( $eventDto->status == EVENT_BOL_EventService::MODERATION_STATUS_APPROVAL && OW::getUser()->isAuthorized('event') )
            {
                $item = array(
                    'label' => OW::getLanguage()->text('base', 'approve'),
                    'href' => OW::getRouter()->urlForRoute('event.approve', array( 'eventId' => $eventDto->id ) ),
                    'id' => 'event_toolbar_flag',
                    'class' => 'ow_green'
                );
                
                $event->add($item);
            }
        }
    }

    public function afterContentApprove( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params["entityType"] != EVENT_CLASS_ContentProvider::ENTITY_TYPE )
        {
            return;
        }

        if ( !$params["isNew"] )
        {
            return;
        }

        $eventDto = EVENT_BOL_EventService::getInstance()->findEvent($params['entityId']);

        if ( $eventDto === null )
        {
            return;
        }

        BOL_AuthorizationService::getInstance()->trackActionForUser($eventDto->userId, 'event', 'add_event');
    }


    /**
     * Get sitemap urls
     *
     * @param OW_Event $event
     * @return void
     */
    public function onSitemapGetUrls( OW_Event $event )
    {
        $params = $event->getParams();

        if ( BOL_AuthorizationService::getInstance()->isActionAuthorizedForGuest('event', 'view_event') )
        {
            $offset = (int) $params['offset'];
            $limit  = (int) $params['limit'];
            $urls   = array();

            switch ( $params['entity'] )
            {
                case 'event_participants' :
                    $eventList = EVENT_BOL_EventService::getInstance()->findAllLatestPublicEventsIds($offset, $limit);

                    foreach ( $eventList as $eventId )
                    {
                        $urls[] = OW::getRouter()->urlForRoute('event.main_user_list', array(
                            'eventId' => $eventId
                        ));
                    }
                    break;

                case 'event' :
                    $eventList = EVENT_BOL_EventService::getInstance()->findAllLatestPublicEventsIds($offset, $limit);

                    foreach ( $eventList as $eventId )
                    {
                        $urls[] = OW::getRouter()->urlForRoute('event.view', array(
                            'eventId' => $eventId
                        ));
                    }
                    break;

                case 'event_list' :
                    $urls[] = OW::getRouter()->urlForRoute('event.main_menu_route');

                    $urls[] = OW::getRouter()->urlForRoute('event.view_event_list', array(
                        'list' =>  'past'
                    ));

                    $urls[] = OW::getRouter()->urlForRoute('event.view_event_list', array(
                        'list' =>  'latest'
                    ));
                    break;
            }

            if ( $urls )
            {
                $event->setData($urls);
            }
        }
    }

    public function onCollectMetaData( BASE_CLASS_EventCollector $e )
    {
        $language = OW::getLanguage();

        $items = array(
            array(
                "entityKey" => "eventsList",
                "entityLabel" => $language->text("event", "seo_meta_events_list_label"),
                "iconClass" => "ow_ic_calendar",
                "langs" => array(
                    "title" => "event+meta_title_events_list",
                    "description" => "event+meta_desc_events_list",
                    "keywords" => "event+meta_keywords_events_list"
                ),
                "vars" => array("site_name", "event_list")
            ),
            array(
                "entityKey" => "eventView",
                "entityLabel" => $language->text("event", "seo_meta_event_view_label"),
                "iconClass" => "ow_ic_calendar",
                "langs" => array(
                    "title" => "event+meta_title_event_view",
                    "description" => "event+meta_desc_event_view",
                    "keywords" => "event+meta_keywords_event_view"
                ),
                "vars" => array("event_title", "event_description", "site_name")
            ),
            array(
                "entityKey" => "eventUsers",
                "entityLabel" => $language->text("event", "seo_meta_event_users_label"),
                "iconClass" => "ow_ic_groups",
                "langs" => array(
                    "title" => "event+meta_title_event_users",
                    "description" => "event+meta_desc_event_users",
                    "keywords" => "event+meta_keywords_event_users"
                ),
                "vars" => array("event_title", "event_description", "site_name")
            ),
        );

        foreach ($items as &$item)
        {
            $item["sectionLabel"] = $language->text("event", "seo_meta_section");
            $item["sectionKey"] = "event";
            $e->add($item);
        }
    }

    public function genericInit()
    {
        OW::getEventManager()->bind('notifications.collect_actions', array($this, 'onNotifyActions'));
        OW::getEventManager()->bind('event.invite_user', array($this, 'onUserInvite'));
        OW::getEventManager()->bind('feed.on_entity_add', array($this, 'feedEntityAdd'));
        OW::getEventManager()->bind(EVENT_BOL_EventService::EVENT_AFTER_EVENT_EDIT, array($this, 'afterEventEdit'));
        OW::getEventManager()->bind(EVENT_BOL_EventService::EVENT_AFTER_CREATE_EVENT, array($this, 'afterEventEdit'));
        OW::getEventManager()->bind(BASE_CMP_AddNewContent::EVENT_NAME, array($this, 'addNewContentItem'));
        OW::getEventManager()->bind('ads.enabled_plugins', array($this, 'adsEnabled'));
        OW::getEventManager()->bind('event.is_plugin_active', array($this, 'isPluginActive'));

        $credits = new EVENT_CLASS_Credits();
        OW::getEventManager()->bind('usercredits.on_action_collect', array($credits, 'bindCreditActionsCollect'));

        OW::getEventManager()->bind('admin.add_auth_labels', array($this, 'addAuthLabels'));
        OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER, array($this, 'onUserDelete'));
        OW::getEventManager()->bind('plugin.privacy.get_action_list', array($this, 'privacyAddAction'));

        OW::getEventManager()->bind('feed.on_item_render', array($this, 'feedOnItemRenderActivity'));

        OW::getEventManager()->bind('feed.collect_privacy', array($this, 'feedOnCollectPrivacy'));
        OW::getEventManager()->bind('feed.collect_configurable_activity', array($this, 'feedOnCollectConfigurableActivity'));
        OW::getEventManager()->bind('base_add_comment', array($this, 'onAddComment'));
        OW::getEventManager()->bind('feed.after_like_added', array($this, 'feedOnLike'));
        OW::getEventManager()->bind(BASE_CMP_QuickLinksWidget::EVENT_NAME, array($this, 'quickLinks'));
        OW::getEventManager()->bind(EVENT_BOL_EventService::EVENT_ON_CREATE_EVENT, array($this, 'onAddEvent'));
        OW::getEventManager()->bind(EVENT_BOL_EventService::EVENT_ON_DELETE_EVENT, array($this, 'onDeleteEvent'));
        OW::getEventManager()->bind(EVENT_BOL_EventService::EVENT_AFTER_EVENT_EDIT, array($this, 'onEditEvent'));
        
        OW::getEventManager()->bind(EVENT_BOL_EventService::EVENT_COLLECT_TOOLBAR, array($this, 'collectToolbar'));
        
        

        OW::getEventManager()->bind(EVENT_BOL_EventService::EVENT_ON_CHANGE_USER_STATUS, array($this, 'onChangeUserStatus'));

        OW::getEventManager()->bind('socialsharing.get_entity_info', array($this, 'sosialSharingGetEventInfo'));

        OW::getEventManager()->bind("moderation.after_content_approve", array($this, "afterContentApprove"));
        OW::getEventManager()->bind("base.sitemap.get_urls", array($this, "onSitemapGetUrls"));
    }

    public function init()
    {
        EVENT_CLASS_InvitationHandler::getInstance()->init();
        OW::getEventManager()->bind("base.collect_seo_meta_data", array($this, 'onCollectMetaData'));
        //OW::getEventManager()->bind('event.get_content_menu', 'getContentMenu');
    }
}