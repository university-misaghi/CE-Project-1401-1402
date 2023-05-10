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

/**
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow_plugins.video.classes
 * @since 1.6.0
 */
class VIDEO_CLASS_EventHandler
{
    /**
     * @var VIDEO_CLASS_EventHandler
     */
    private static $classInstance;

    const EVENT_VIDEO_ADD = 'video.add_clip';

    /**
     * @return VIDEO_CLASS_EventHandler
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct() { }

    public function addNewContentItem( BASE_CLASS_EventCollector $event )
    {
        $resultArray = array(
            BASE_CMP_AddNewContent::DATA_KEY_ICON_CLASS => 'ow_ic_video',
            BASE_CMP_AddNewContent::DATA_KEY_URL => OW::getRouter()->urlFor('VIDEO_CTRL_Add', 'index'),
            BASE_CMP_AddNewContent::DATA_KEY_LABEL => OW::getLanguage()->text('video', 'video')
        );

        if ( !OW::getUser()->isAuthorized('video', 'add') )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('video', 'add');

            if ( $status['status'] != BOL_AuthorizationService::STATUS_PROMOTED )
            {
                return;
            }

            $id = uniqid('add-new-video-');
            $resultArray[BASE_CMP_AddNewContent::DATA_KEY_ID] = $id;

            $script = '$("#'.$id.'").click(function(){
                OW.authorizationLimitedFloatbox('.json_encode($status['msg']).');
                return false;
            });';
            OW::getDocument()->addOnloadScript($script);
        }

        $event->add($resultArray);
    }

    public function quickLinks( BASE_CLASS_EventCollector $event )
    {
        $service = VIDEO_BOL_ClipService::getInstance();
        $userId = OW::getUser()->getId();
        $username = OW::getUser()->getUserObject()->getUsername();

        $clipCount = (int) $service->findUserClipsCount($userId);

        if ( $clipCount > 0 )
        {
            $event->add(array(
                BASE_CMP_QuickLinksWidget::DATA_KEY_LABEL => OW::getLanguage()->text('video', 'my_video'),
                BASE_CMP_QuickLinksWidget::DATA_KEY_URL => OW::getRouter()->urlForRoute('video_user_video_list', array('user' => $username)),
                BASE_CMP_QuickLinksWidget::DATA_KEY_COUNT => $clipCount,
                BASE_CMP_QuickLinksWidget::DATA_KEY_COUNT_URL => OW::getRouter()->urlForRoute('video_user_video_list', array('user' => $username))
            ));
        }
    }

    public function deleteUserContent( OW_Event $event )
    {
        $params = $event->getParams();

        if ( !isset($params['deleteContent']) || !(bool) $params['deleteContent'] )
        {
            return;
        }

        $userId = (int) $params['userId'];

        if ( $userId > 0 )
        {
            VIDEO_BOL_ClipService::getInstance()->deleteUserClips($userId);
        }
    }

    public function onNotifyActions( BASE_CLASS_EventCollector $e )
    {
        $e->add(array(
            'section' => 'video',
            'action' => 'video-add_comment',
            'description' => OW::getLanguage()->text('video', 'email_notifications_setting_comment'),
            'sectionIcon' => 'ow_ic_video',
            'sectionLabel' => OW::getLanguage()->text('video', 'email_notifications_section_label'),
            'selected' => true
        ));
    }

    public function addCommentNotification( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['entityType']) || $params['entityType'] !== 'video_comments' )
        {
            return;
        }

        $entityId = $params['entityId'];
        $userId = $params['userId'];
        $commentId = $params['commentId'];

        $clipService = VIDEO_BOL_ClipService::getInstance();
        $userService = BOL_UserService::getInstance();

        $clip = $clipService->findClipById($entityId);

        if ( $clip->userId != $userId )
        {
            $params = array(
                'pluginKey' => 'video',
                'entityType' => 'video_add_comment',
                'entityId' => $commentId,
                'action' => 'video-add_comment',
                'userId' => $clip->userId,
                'time' => time()
            );

            $comment = BOL_CommentService::getInstance()->findComment($commentId);
            $url = OW::getRouter()->urlForRoute('view_clip', array('id' => $entityId));
            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId));

            $data = array(
                'avatar' => $avatars[$userId],
                'string' => array(
                    'key' => 'video+email_notifications_comment',
                    'vars' => array(
                        'userName' => $userService->getDisplayName($userId),
                        'userUrl' => $userService->getUserUrl($userId),
                        'videoUrl' => $url,
                        'videoTitle' => strip_tags($clip->title)
                    )
                ),
                'content' => $comment->getMessage(),
                'url' => $url
            );

            $event = new OW_Event('notifications.add', $params, $data);
            OW::getEventManager()->trigger($event);
        }
    }

    public function feedEntityAdd( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        if ( $params['entityType'] != VIDEO_BOL_ClipService::ENTITY_TYPE )
        {
            return;
        }

        $videoService = VIDEO_BOL_ClipService::getInstance();
        $clip = $videoService->findClipById($params['entityId']);
        $thumb = $videoService->getClipThumbUrl($clip->id, $clip->code, $clip->thumbUrl);
        if ( $thumb == "undefined" )
        {
            $thumb = $videoService->getClipDefaultThumbUrl();
        }
        
        $vars = array();
        $format = "video";
        
        if ( isset($data["content"]) && is_array($data["content"]) )
        {
            $vars = empty($data["content"]["vars"]) ? array() : $data["content"]["vars"];
            
            if ( !empty($data["content"]["format"]) )
            {
                $format = $data["content"]["format"];
            }
        }
        
        $content = array(
            "format" => $format,
            "vars" => array_merge(array(
                "image" => $thumb,
                "title" => $title = UTIL_String::truncate(strip_tags($clip->title), 100, '...'),
                "description" => $description = UTIL_String::truncate(strip_tags($clip->description), 150, '...'),
                "url" => array("routeName" => "view_clip", "vars" => array('id' => $clip->id)),
                "embed" => $clip->code
            ), $vars)
        );

        $data = array_merge($data, array(
            'time' => (int) $clip->addDatetime,
            'ownerId' => $clip->userId,
            'content' => $content,
            'view' => array(
                'iconClass' => 'ow_ic_video'
            )
        ));

        $e->setData($data);
    }

    public function adsEnabled( BASE_CLASS_EventCollector $event )
    {
        $event->add('video');
    }

    public function addAuthLabels( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $event->add(
            array(
                'video' => array(
                    'label' => $language->text('video', 'auth_group_label'),
                    'actions' => array(
                        'add' => $language->text('video', 'auth_action_label_add'),
                        'view' => $language->text('video', 'auth_action_label_view'),
                        'add_comment' => $language->text('video', 'auth_action_label_add_comment')
                    )
                )
            )
        );
    }

    public function privacyAddAction( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();

        $action = array(
            'key' => 'video_view_video',
            'pluginKey' => 'video',
            'label' => $language->text('video', 'privacy_action_view_video'),
            'description' => '',
            'defaultValue' => 'everybody'
        );

        $event->add($action);
    }

    public function onChangePrivacy( OW_Event $e )
    {
        $params = $e->getParams();
        $userId = (int) $params['userId'];

        $actionList = $params['actionList'];

        if ( empty($actionList['video_view_video']) )
        {
            return;
        }

        VIDEO_BOL_ClipService::getInstance()->updateUserClipsPrivacy($userId, $actionList['video_view_video']);
    }

    public function feedCollectConfigurableActivity( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $event->add(array(
            'label' => $language->text('video', 'feed_content_label'),
            'activity' => '*:video_comments'
        ));
    }

    public function feedCollectPrivacy( BASE_CLASS_EventCollector $event )
    {
        $event->add(array('create:video_comments', 'video_view_video'));
    }

    public function feedVideoComment( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['entityType'] != 'video_comments' )
        {
            return;
        }

        $service = VIDEO_BOL_ClipService::getInstance();
        $userId = $service->findClipOwner($params['entityId']);

        if ( $userId == $params['userId'] )
        {
            $string = array('key' => 'video+feed_activity_owner_video_string');
        }
        else
        {
            $userName = BOL_UserService::getInstance()->getDisplayName($userId);
            $userUrl = BOL_UserService::getInstance()->getUserUrl($userId);
            $userEmbed = '<a href="' . $userUrl . '">' . $userName . '</a>';
            $string = array(
                'key' => 'video+feed_activity_video_string',
                'vars' => array('user' => $userEmbed)
            );
        }

        OW::getEventManager()->trigger(new OW_Event('feed.activity', array(
            'activityType' => 'comment',
            'activityId' => $params['commentId'],
            'entityId' => $params['entityId'],
            'entityType' => $params['entityType'],
            'userId' => $params['userId'],
            'pluginKey' => 'video'
        ), array(
            'string' => $string
        )));
    }

    public function feedVideoLike( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['entityType'] != 'video_comments' )
        {
            return;
        }

        $service = VIDEO_BOL_ClipService::getInstance();
        $userId = $service->findClipOwner($params['entityId']);

        $userName = BOL_UserService::getInstance()->getDisplayName($userId);
        $userUrl = BOL_UserService::getInstance()->getUserUrl($userId);
        $userEmbed = '<a href="' . $userUrl . '">' . $userName . '</a>';

        if ( $params['userId'] == $userId )
        {
            $string = array('key' => 'video+feed_activity_owner_video_like');
        }
        else
        {
            $string = array(
                'key' => 'video+feed_activity_video_string_like',
                'vars' => array('user' => $userEmbed)
            );
        }

        OW::getEventManager()->trigger(new OW_Event('feed.activity', array(
            'activityType' => 'like',
            'activityId' => $params['userId'],
            'entityId' => $params['entityId'],
            'entityType' => $params['entityType'],
            'userId' => $params['userId'],
            'pluginKey' => 'video'
        ), array(
            'string' => $string
        )));
    }

    public function init()
    {
        $this->genericInit();
        $em = OW::getEventManager();

        $em->bind(BASE_CMP_AddNewContent::EVENT_NAME, array($this, 'addNewContentItem'));
        $em->bind(BASE_CMP_QuickLinksWidget::EVENT_NAME, array($this, 'quickLinks'));
        $em->bind("base.collect_seo_meta_data", array($this, 'onCollectMetaData'));
    }

    public function sosialSharingGetVideoInfo( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();
        $data['display'] = false;

        if ( empty($params['entityId']) )
        {
            return;
        }

        if ( $params['entityType'] == 'video' )
        {
            $clipDto = VIDEO_BOL_ClipService::getInstance()->findClipById($params['entityId']);

            if ( !empty($clipDto) )
            {
                $data['display'] = BOL_AuthorizationService::getInstance()->isActionAuthorizedForGuest('video', 'view') && $clipDto->privacy == 'everybody';
            }

            $event->setData($data);
        }
    }

    public function addClip( OW_Event $e )
    {
        $params = $e->getParams();

        if ( empty($params['userId']) || empty($params['title']) || empty($params['code']) )
        {
            $e->setData(array('result' => false));
        }
        else
        {
            $clipService = VIDEO_BOL_ClipService::getInstance();

            $clip = new VIDEO_BOL_Clip();
            $clip->title = htmlspecialchars($params['title']);
            if ( !empty($params['description']) )
            {
                $clip->description = UTIL_HtmlTag::stripJs($params['description']);
                $clip->description = UTIL_HtmlTag::stripTags($clip->description, array('frame', 'style'), array(), true);
            }

            $clip->userId = $params['userId'];

            $privacy = OW::getEventManager()->call(
                'plugin.privacy.get_privacy',
                array('ownerId' => $clip->userId, 'action' => 'video_view_video')
            );
            $clip->privacy = mb_strlen($privacy) ? $privacy : 'everybody';

            $prov = new VideoProviders($params['code']);

            $clip->provider = $prov->detectProvider();
            $clip->addDatetime = time();
            $clip->status = VIDEO_BOL_ClipDao::STATUS_APPROVED;

            $thumbUrl = $prov->getProviderThumbUrl($clip->provider);
            if ( $thumbUrl != VideoProviders::PROVIDER_UNDEFINED )
            {
                $clip->thumbUrl = $thumbUrl;
            }
            $clip->thumbCheckStamp = time();

            $clip->code = $clipService->validateClipCode($params['code'], $clip->provider);

            if ( $clipService->addClip($clip) )
            {
                if ( !empty($params['tags']) )
                {
                    BOL_TagService::getInstance()->updateEntityTags($clip->id, 'video', $params['tags']);
                }

                // Newsfeed
                $content = array(
                    "vars" => array()
                );
                
                $content["vars"]["status"] = empty($params["status"]) 
                        ? null
                        : $params["status"];
                
                $event = new OW_Event('feed.action', array(
                    'pluginKey' => 'video',
                    'entityType' => VIDEO_BOL_ClipService::ENTITY_TYPE,
                    'entityId' => $clip->id,
                    'userId' => $clip->userId
                ), array(
                    "content" => $content
                ));

                OW::getEventManager()->trigger($event);
                
                OW::getEventManager()->trigger(new OW_Event(VIDEO_BOL_ClipService::EVENT_AFTER_ADD, array(
                    'clipId' => $clip->id
                )));

                $status = $clipService->findClipById($clip->id)->status;

                if ($status == 'approved')
                {
                    BOL_AuthorizationService::getInstance()->trackAction('video', 'add');
                }

                $e->setData(array(
                    'result' => true,
                    'id' => $clip->id,
                    "status" => $status
                ));
            }
        }
    }

    public function feedBeforeStatusUpdate( OW_Event $e )
    {
        $params = $e->getParams();
        
        if ( $params['type'] != 'video' )
        {
            return;
        }
        
        $auth = BOL_AuthorizationService::getInstance()->getActionStatus('video', 'add');

        if ( $auth['status'] != BOL_AuthorizationService::STATUS_AVAILABLE)
        {
            return;
        }

        $data = $params['data'];

        $addClipParams = array(
            'userId' => $params['userId'],
            'title' => isset($data['title']) ? $data['title'] : $params['status'],
            'description' => isset($data['description']) ? $data['description'] : null,
            'code' => UTIL_HtmlTag::stripJs($data['html']),
            "status" => $params["status"]
        );

        $event = new OW_Event(self::EVENT_VIDEO_ADD, $addClipParams);
        OW::getEventManager()->trigger($event);

        $addClipData = $event->getData();

        if ( $addClipData["status"] == VIDEO_BOL_ClipDao::STATUS_APPROVAL )
        {
            $e->setData(array(
                "message" => OW::getLanguage()->text("video", "pending_approval_feedback")
            ));
            
            return;
        }
        
        if ( !empty($addClipData['id']) )
        {
            $e->setData(array('entityType' => 'video_comments', 'entityId' => $addClipData['id']));
        }
    }

    public function afterContentApprove( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params["entityType"] != VIDEO_BOL_ClipService::ENTITY_TYPE )
        {
            return;
        }

        if ( !$params["isNew"] )
        {
            return;
        }

        $clipService = VIDEO_BOL_ClipService::getInstance();
        $videoClip = $clipService->findClipById($params["entityId"]);

        if ( $videoClip === null )
        {
            return;
        }

        BOL_AuthorizationService::getInstance()->trackActionForUser($videoClip->userId, 'video', 'add');
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

        if ( BOL_AuthorizationService::getInstance()->isActionAuthorizedForGuest('video', 'view') )
        {
            $offset = (int) $params['offset'];
            $limit  = (int) $params['limit'];
            $urls   = array();

            switch ( $params['entity'] )
            {
                case 'video_authors' :
                    $usersIds  = VIDEO_BOL_ClipService::getInstance()->findLatestPublicClipsAuthorsIds($offset, $limit);
                    $userNames = BOL_UserService::getInstance()->getUserNamesForList($usersIds);

                    // skip deleted users
                    foreach ( array_filter($userNames) as $userName )
                    {
                        $urls[] = OW::getRouter()->urlForRoute('video_user_video_list', array(
                            'user' => $userName
                        ));
                    }
                    break;

                case 'video' :
                    $page  = ceil($offset / $limit) + 1; // paging emulation
                    $clips = VIDEO_BOL_ClipService::getInstance()->findClipsList('latest', $page, $limit);

                    foreach ( $clips as $clip )
                    {
                        $urls[] = OW::getRouter()->urlForRoute('view_clip', array(
                            'id' => $clip['id']
                        ));
                    }
                    break;

                case 'video_tags' :
                    $tags = BOL_TagService::getInstance()->findMostPopularTags('video', $limit, $offset);

                    foreach ( $tags as $tag )
                    {
                        $urls[] = OW::getRouter()->urlForRoute('view_tagged_list', array(
                            'tag' => $tag['label']
                        ));
                    }
                    break;

                case 'video_list' :
                    $urls[] = OW::getRouter()->urlForRoute('video_list_index');

                    $urls[] = OW::getRouter()->urlForRoute('view_list', array(
                        'listType' => 'latest'
                    ));

                    $urls[] = OW::getRouter()->urlForRoute('view_list', array(
                        'listType' => 'toprated'
                    ));

                    $urls[] = OW::getRouter()->urlForRoute('view_list', array(
                        'listType' => 'tagged'
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
                "entityKey" => "taggedList",
                "entityLabel" => $language->text("video", "seo_meta_tagged_list_label"),
                "iconClass" => "ow_ic_tag",
                "langs" => array(
                    "title" => "video+meta_title_tagged_list",
                    "description" => "video+meta_desc_tagged_list",
                    "keywords" => "video+meta_keywords_tagged_list"
                ),
                "vars" => array("site_name")
            ),
            array(
                "entityKey" => "viewList",
                "entityLabel" => $language->text("video", "seo_meta_view_list_label"),
                "iconClass" => "ow_ic_newsfeed",
                "langs" => array(
                    "title" => "video+meta_title_view_list",
                    "description" => "video+meta_desc_view_list",
                    "keywords" => "video+meta_keywords_view_list"
                ),
                "vars" => array("site_name", "video_list")
            ),
            array(
                "entityKey" => "viewClip",
                "entityLabel" => $language->text("video", "seo_meta_view_clip_label"),
                "iconClass" => "ow_ic_video",
                "langs" => array(
                    "title" => "video+meta_title_view_clip",
                    "description" => "video+meta_desc_view_clip",
                    "keywords" => "video+meta_keywords_view_clip"
                ),
                "vars" => array("site_name", "video_title", "user_name")
            ),
            array(
                "entityKey" => "tagList",
                "entityLabel" => $language->text("video", "seo_meta_tag_list_label"),
                "iconClass" => "ow_ic_tag",
                "langs" => array(
                    "title" => "video+meta_title_tag_list",
                    "description" => "video+meta_desc_tag_list",
                    "keywords" => "video+meta_keywords_tag_list"
                ),
                "vars" => array("site_name", "video_tag_name")
            ),
            array(
                "entityKey" => "userVideoList",
                "entityLabel" => $language->text("video", "seo_meta_user_video_list_label"),
                "iconClass" => "ow_ic_user",
                "langs" => array(
                    "title" => "video+meta_title_user_video_list",
                    "description" => "video+meta_desc_user_video_list",
                    "keywords" => "video+meta_keywords_user_video_list",
                ),
                "vars" => array("user_name", "user_gender", "user_age", "user_location", "site_name")
            )
        );
        
        foreach ($items as &$item)
        {
            $item["sectionLabel"] = $language->text("video", "seo_meta_section");
            $item["sectionKey"] = "video";
            $e->add($item);
        }
    }

    public function genericInit()
    {
        $em = OW::getEventManager();

        $em->bind(self::EVENT_VIDEO_ADD, array($this, 'addCLip'));
        $em->bind(OW_EventManager::ON_USER_UNREGISTER, array($this, 'deleteUserContent'));
        $em->bind('notifications.collect_actions', array($this, 'onNotifyActions'));
        $em->bind('base_add_comment', array($this, 'addCommentNotification'));
        $em->bind('feed.on_entity_add', array($this, 'feedEntityAdd'));
        $em->bind('feed.on_entity_update', array($this, 'feedEntityAdd'));
        $em->bind('ads.enabled_plugins', array($this, 'adsEnabled'));
        $em->bind('admin.add_auth_labels', array($this, 'addAuthLabels'));
        $em->bind('plugin.privacy.get_action_list', array($this, 'privacyAddAction'));
        $em->bind('plugin.privacy.on_change_action_privacy', array($this, 'onChangePrivacy'));
        $em->bind('feed.collect_configurable_activity', array($this, 'feedCollectConfigurableActivity'));
        $em->bind('feed.collect_privacy', array($this, 'feedCollectPrivacy'));
        $em->bind('feed.after_comment_add', array($this, 'feedVideoComment'));
        $em->bind('feed.after_like_added', array($this, 'feedVideoLike'));
        $em->bind('feed.before_content_add', array($this, 'feedBeforeStatusUpdate'));
        $em->bind('socialsharing.get_entity_info', array($this, 'sosialSharingGetVideoInfo'));
        $em->bind('moderation.after_content_approve', array($this, 'afterContentApprove'));

        $credits = new VIDEO_CLASS_Credits();
        $em->bind('usercredits.on_action_collect', array($credits, 'bindCreditActionsCollect'));
        $em->bind('usercredits.get_action_key', array($credits, 'getActionKey'));
        $em->bind("base.sitemap.get_urls", array($this, "onSitemapGetUrls"));
    }
}