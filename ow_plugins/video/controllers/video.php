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
 * Video action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow_plugins.video.controllers
 * @since 1.0
 */
class VIDEO_CTRL_Video extends OW_ActionController
{
    /**
     * @var OW_Plugin
     */
    private $plugin;
    /**
     * @var string
     */
    private $pluginJsUrl;
    /**
     * @var string
     */
    private $ajaxResponder;
    /**
     * @var VIDEO_BOL_ClipService
     */
    private $clipService;
    /**
     * @var BASE_CMP_ContentMenu
     */
    private $menu;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->plugin = OW::getPluginManager()->getPlugin('video');
        $this->pluginJsUrl = $this->plugin->getStaticJsUrl();
        $this->ajaxResponder = OW::getRouter()->urlFor('VIDEO_CTRL_Video', 'ajaxResponder');

        $this->clipService = VIDEO_BOL_ClipService::getInstance();

        $this->menu = $this->getMenu();

        if ( !OW::getRequest()->isAjax() )
        {
            OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'video', 'video');
        }
    }

    /**
     * Returns menu component
     *
     * @return BASE_CMP_ContentMenu
     */
    private function getMenu()
    {
        $validLists = array('featured', 'latest', 'toprated', 'tagged');
        $classes = array('ow_ic_push_pin', 'ow_ic_clock', 'ow_ic_star', 'ow_ic_tag');

        if ( !VIDEO_BOL_ClipService::getInstance()->findClipsCount('featured') )
        {
            array_shift($validLists);
            array_shift($classes);
        }

        $language = OW::getLanguage();

        $menuItems = array();

        $order = 0;
        foreach ( $validLists as $type )
        {
            $item = new BASE_MenuItem();
            $item->setLabel($language->text('video', 'menu_' . $type));
            $item->setUrl(OW::getRouter()->urlForRoute('view_list', array('listType' => $type)));
            $item->setKey($type);
            $item->setIconClass($classes[$order]);
            $item->setOrder($order);

            array_push($menuItems, $item);

            $order++;
        }

        $menu = new BASE_CMP_ContentMenu($menuItems);

        return $menu;
    }

    /**
     * Video view action
     *
     * @param array $params
     * @throws Redirect404Exception
     */
    public function view( array $params )
    {
        if ( !isset($params['id']) || !($id = (int) $params['id']) )
        {
            throw new Redirect404Exception();
        }

        $clip = $this->clipService->findClipById($id);

        if ( !$clip )
        {
            throw new Redirect404Exception();
        }
        
        
        $userId = OW::getUser()->getId();
        $contentOwner = (int) $this->clipService->findClipOwner($id);
        $ownerMode = $contentOwner == $userId;
        
        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('video');
        
        if ( $clip->status != VIDEO_BOL_ClipDao::STATUS_APPROVED && !( $modPermissions || $ownerMode ) )
        {
            throw new Redirect403Exception;
        }

        $language = OW_Language::getInstance();

        $description = $clip->description;
        $clip->description = UTIL_HtmlTag::autoLink($clip->description);
        $this->assign('clip', $clip);
        $is_featured = VIDEO_BOL_ClipFeaturedService::getInstance()->isFeatured($clip->id);
        $this->assign('featured', $is_featured);
        $this->assign('moderatorMode', $modPermissions);
        $this->assign('ownerMode', $ownerMode);

        if ( !$ownerMode && !OW::getUser()->isAuthorized('video', 'view') && !$modPermissions )
        {
            $error = BOL_AuthorizationService::getInstance()->getActionStatus('video', 'view');
            throw new AuthorizationException($error['msg']);
        }

        // permissions check
        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => 'video_view_video', 'ownerId' => $contentOwner, 'viewerId' => $userId);
            $event = new OW_Event('privacy_check_permission', $privacyParams);
            OW::getEventManager()->trigger($event);
        }

        $cmtParams = new BASE_CommentsParams('video', 'video_comments');
        $cmtParams->setEntityId($id);
        $cmtParams->setOwnerId($contentOwner);
        $cmtParams->setDisplayType(BASE_CommentsParams::DISPLAY_TYPE_BOTTOM_FORM_WITH_FULL_LIST);
        
        $cmtParams->setAddComment($clip->status == VIDEO_BOL_ClipDao::STATUS_APPROVED);

        $videoCmts = new BASE_CMP_Comments($cmtParams);
        $this->addComponent('comments', $videoCmts);

        if ( $clip->status == VIDEO_BOL_ClipDao::STATUS_APPROVED )
        {
            $videoRates = new BASE_CMP_Rate('video', 'video_rates', $id, $contentOwner);
            $this->addComponent('rate', $videoRates);
        }

        $videoTags = new BASE_CMP_EntityTagCloud('video');
        $videoTags->setEntityId($id);
        $videoTags->setRouteName('view_tagged_list');
        $this->addComponent('tags', $videoTags);

        $username = BOL_UserService::getInstance()->getUserName($clip->userId);
        $this->assign('username', $username);

        $displayName = BOL_UserService::getInstance()->getDisplayName($clip->userId);
        $this->assign('displayName', $displayName);

        OW::getDocument()->addScript($this->pluginJsUrl . 'video.js');

        $objParams = array(
            'ajaxResponder' => $this->ajaxResponder,
            'clipId' => $id,
            'txtDelConfirm' => OW::getLanguage()->text('video', 'confirm_delete'),
            'txtMarkFeatured' => OW::getLanguage()->text('video', 'mark_featured'),
            'txtRemoveFromFeatured' => OW::getLanguage()->text('video', 'remove_from_featured'),
            'txtApprove' => OW::getLanguage()->text('base', 'approve'),
            'txtDisapprove' => OW::getLanguage()->text('base', 'disapprove')
        );

        $script =
            "$(document).ready(function(){
                var clip = new videoClip( " . json_encode($objParams) . ");
            }); ";

        OW::getDocument()->addOnloadScript($script);

        $pendingApprovalString = "";
        if ( $clip->status != VIDEO_BOL_ClipDao::STATUS_APPROVED )
        {
            $pendingApprovalString = '<span class="ow_remark ow_small">(' 
                    . OW::getLanguage()->text("base", "pending_approval") . ')</span>';
        }
        
        OW::getDocument()->setHeading($clip->title . " " . $pendingApprovalString);
        OW::getDocument()->setHeadingIconClass('ow_ic_video');

        $toolbar = array();

        $toolbarEvent = new BASE_CLASS_EventCollector('video.collect_video_toolbar_items', array(
            'clipId' => $clip->id,
            'clipDto' => $clip
        ));

        OW::getEventManager()->trigger($toolbarEvent);

        foreach ( $toolbarEvent->getData() as $toolbarItem )
        {
            array_push($toolbar, $toolbarItem);
        }

        if ( $clip->status == VIDEO_BOL_ClipDao::STATUS_APPROVED && OW::getUser()->isAuthenticated() && !$ownerMode )
        {
            array_push($toolbar, array(
                'href' => 'javascript://',
                'id' => 'btn-video-flag',
                'label' => $language->text('base', 'flag')
            ));
        }

        if ( $ownerMode || $modPermissions )
        {
            array_push($toolbar, array(
                'href' => OW::getRouter()->urlForRoute('edit_clip', array('id' => $clip->id)),
                'label' => $language->text('base', 'edit')
            ));

            array_push($toolbar, array(
                'href' => 'javascript://',
                'id' => 'clip-delete',
                'label' => $language->text('base', 'delete')
            ));
        }

        if ( $modPermissions )
        {
            if ( $is_featured )
            {
                array_push($toolbar, array(
                    'href' => 'javascript://',
                    'id' => 'clip-mark-featured',
                    'rel' => 'remove_from_featured',
                    'label' => $language->text('video', 'remove_from_featured')
                ));
            }
            else
            {
                array_push($toolbar, array(
                    'href' => 'javascript://',
                    'id' => 'clip-mark-featured',
                    'rel' => 'mark_featured',
                    'label' => $language->text('video', 'mark_featured')
                ));
            }

            if ( $clip->status != VIDEO_BOL_ClipDao::STATUS_APPROVED )
            {
                array_push($toolbar, array(
                    'href' => OW::getRouter()->urlFor(__CLASS__, "approve", array(
                        "clipId" => $clip->id
                    )),
                    'label' => $language->text('base', 'approve'),
                    "class" => "ow_green"
                ));
            }
        }

        $this->assign('toolbar', $toolbar);

        $js = UTIL_JsGenerator::newInstance()
                ->jQueryEvent('#btn-video-flag', 'click', 'OW.flagContent(e.data.entity, e.data.id);', array('e'),
                    array('entity' => VIDEO_BOL_ClipService::ENTITY_TYPE, 'id' => $clip->id));

        OW::getDocument()->addOnloadScript($js, 1001);

//        OW::getDocument()->setTitle($language->text('video', 'meta_title_video_view', array('title' => $clip->title)));
        $tagsArr = BOL_TagService::getInstance()->findEntityTags($clip->id, 'video');

        $labels = array();
        foreach ( $tagsArr as $t )
        {
            $labels[] = $t->label;
        }
        $tagStr = $tagsArr ? implode(', ', $labels) : '';
//        OW::getDocument()->setDescription($language->text('video', 'meta_description_video_view', array('title' => $clip->title, 'tags' => $tagStr)));

        $clipThumbUrl = $this->clipService->getClipThumbUrl($id);
        $this->assign('clipThumbUrl', $clipThumbUrl);

        $params = array(
            "sectionKey" => "video",
            "entityKey" => "viewClip",
            "title" => "video+meta_title_view_clip",
            "description" => "video+meta_desc_view_clip",
            "keywords" => "video+meta_keywords_view_clip",
            "vars" => array("video_title" => $clip->title, "user_name" => $displayName),
            "image" => $clipThumbUrl
        );

        OW::getEventManager()->trigger(new OW_Event("base.provide_page_meta_info", $params));
    }

    public function edit( array $params )
    {
        if ( !isset($params['id']) || !($id = (int) $params['id']) )
        {
            throw new Redirect404Exception();
        }

        $clip = $this->clipService->findClipById($id);

        if ( !$clip )
        {
            throw new Redirect404Exception();
        }

        $language = OW_Language::getInstance();

        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('video');
        $this->assign('moderatorMode', $modPermissions);

        $contentOwner = (int) $this->clipService->findClipOwner($id);
        $userId = OW::getUser()->getId();
        $ownerMode = $contentOwner == $userId;
        $this->assign('ownerMode', $ownerMode);

        if ( !$ownerMode && !$modPermissions )
        {
            throw new AuthorizationException();
        }

        $videoEditForm = new videoEditForm($clip->id);
        $this->addForm($videoEditForm);

        $videoEditForm->getElement('id')->setValue($clip->id);
        $videoEditForm->getElement('title')->setValue($clip->title);
        $videoEditForm->getElement('description')->setValue($clip->description);
        $videoEditForm->getElement('code')->setValue($clip->code);

        if ( OW::getRequest()->isPost() && $videoEditForm->isValid($_POST) )
        {
            // validate the embed code
            $values = $videoEditForm->getValues();
            $code = $this->clipService->validateClipCode($values['code']);

            if ( !mb_strlen($code) )
            {
                OW::getFeedback()->warning($language->text('video', 'resource_not_allowed'));
                $this->redirect();
            }

            $res = $videoEditForm->process();
            OW::getFeedback()->info($language->text('video', 'clip_updated'));
            $this->redirect(OW::getRouter()->urlForRoute('view_clip', array('id' => $res['id'])));
        }

        OW::getDocument()->setHeading($language->text('video', 'tb_edit_clip'));
        OW::getDocument()->setHeadingIconClass('ow_ic_video');
        OW::getDocument()->setTitle($language->text('video', 'tb_edit_clip'));
    }

    /**
     * Video list view action
     *
     * @param array $params
     * @throws AuthorizationException
     */
    public function viewList( array $params )
    {
        $listType = isset($params['listType']) ? trim($params['listType']) : 'latest';

        $validLists = array('featured', 'latest', 'toprated', 'tagged');

        if ( !in_array($listType, $validLists) )
        {
            $this->redirect(OW::getRouter()->urlForRoute('view_list', array('listType' => 'latest')));
        }

        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('video');

        if ( !OW::getUser()->isAuthorized('video', 'view') && !$modPermissions )
        {
            $error = BOL_AuthorizationService::getInstance()->getActionStatus('video', 'view');
            throw new AuthorizationException($error['msg']);
        }

        $this->addComponent('videoMenu', $this->menu);

        $el = $this->menu->getElement($listType);
        if ( $el )
        {
            $el->setActive(true);
        }

        $this->assign('listType', $listType);

        // check auth
        $showAddButton = true;
        $status = BOL_AuthorizationService::getInstance()->getActionStatus('video', 'add');

        if ( $status['status'] == BOL_AuthorizationService::STATUS_AVAILABLE )
        {
            $script = '$("#btn-add-new-video").click(function(){
                document.location.href = ' . json_encode(OW::getRouter()->urlFor('VIDEO_CTRL_Add', 'index')) . ';
            });';

            OW::getDocument()->addOnloadScript($script);
        }
        else if ( $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
        {
            $script = '$("#btn-add-new-video").click(function(){
                OW.authorizationLimitedFloatbox('.json_encode($status['msg']).');
            });';

            OW::getDocument()->addOnloadScript($script);
        }
        else
        {
            $showAddButton = false;
        }

        $this->assign('showAddButton', $showAddButton);

        OW::getDocument()->setHeading(OW::getLanguage()->text('video', 'page_title_browse_video'));
        OW::getDocument()->setHeadingIconClass('ow_ic_video');
        //OW::getDocument()->setTitle(OW::getLanguage()->text('video', 'meta_title_video_'.$listType));
        //OW::getDocument()->setDescription(OW::getLanguage()->text('video', 'meta_description_video_'.$listType));

        $params = array(
            "sectionKey" => "video",
            "entityKey" => "viewList",
            "title" => "video+meta_title_view_list",
            "description" => "video+meta_desc_view_list",
            "keywords" => "video+meta_keywords_view_list",
            "vars" => array("video_list" => OW::getLanguage()->text("video", "{$listType}_list_label"))
        );

        OW::getEventManager()->trigger(new OW_Event("base.provide_page_meta_info", $params));
    }

    /**
     * User video list view action
     *
     * @param array $params
     * @throws AuthorizationException
     * @throws Redirect404Exception
     */
    public function viewUserVideoList( array $params )
    {
        if ( !isset($params['user']) || !strlen($userName = trim($params['user'])) )
        {
            throw new Redirect404Exception();
        }

        $user = BOL_UserService::getInstance()->findByUsername($userName);
        if ( !$user )
        {
            throw new Redirect404Exception();
        }

        $ownerMode = $user->id == OW::getUser()->getId();

        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('video');

        if ( !OW::getUser()->isAuthorized('video', 'view') && !$modPermissions && !$ownerMode )
        {
            $error = BOL_AuthorizationService::getInstance()->getActionStatus('video', 'view');
            throw new AuthorizationException($error['msg']);
        }

        // permissions check
        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => 'video_view_video', 'ownerId' => $user->id, 'viewerId' => OW::getUser()->getId());
            $event = new OW_Event('privacy_check_permission', $privacyParams);
            OW::getEventManager()->trigger($event);
        }

        $this->assign('permissionError', null);
        $this->assign('userId', $user->id);

        $clipCount = VIDEO_BOL_ClipService::getInstance()->findUserClipsCount($user->id);
        $this->assign('total', $clipCount);

        $displayName = BOL_UserService::getInstance()->getDisplayName($user->id);
        $this->assign('userName', $displayName);

        $lang = OW::getLanguage();
        $heading = $lang->text('video', 'page_title_video_by', array('user' => $displayName));

        OW::getDocument()->setHeading($heading);
        OW::getDocument()->setHeadingIconClass('ow_ic_video');
//        OW::getDocument()->setTitle($lang->text('video', 'meta_title_user_video', array('displayName' => $displayName)));
//        OW::getDocument()->setDescription($lang->text('video', 'meta_description_user_video', array('displayName' => $displayName)));

        $vars = BOL_SeoService::getInstance()->getUserMetaInfo($user);

        $params = array(
            "sectionKey" => "video",
            "entityKey" => "userVideoList",
            "title" => "video+meta_title_user_video_list",
            "description" => "video+meta_desc_user_video_list",
            "keywords" => "video+meta_keywords_user_video_list",
            "vars" => $vars,
            "image" => BOL_AvatarService::getInstance()->getAvatarUrl($user->getId(), 2)
        );

        OW::getEventManager()->trigger(new OW_Event("base.provide_page_meta_info", $params));
    }

    /**
     * Tagged video list view action
     *
     * @param array $params
     * @throws AuthorizationException
     */
    public function viewTaggedList( array $params = null )
    {
        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('video');

        if ( !OW::getUser()->isAuthorized('video', 'view') && !$modPermissions )
        {
            $error = BOL_AuthorizationService::getInstance()->getActionStatus('video', 'view');
            throw new AuthorizationException($error['msg']);
        }

        $tag = !empty($params['tag']) ? trim(htmlspecialchars(urldecode($params['tag']))) : '';

        $this->addComponent('videoMenu', $this->menu);

        $this->menu->getElement('tagged')->setActive(true);

        $this->setTemplate(OW::getPluginManager()->getPlugin('video')->getCtrlViewDir() . 'video_view_list-tagged.html');

        $listUrl = OW::getRouter()->urlForRoute('view_tagged_list_st');

        OW::getDocument()->addScript($this->pluginJsUrl . 'video_tag_search.js');

        $objParams = array(
            'listUrl' => $listUrl
        );

        $script =
            "$(document).ready(function(){
                var videoSearch = new videoTagSearch(" . json_encode($objParams) . ");
            }); ";

        OW::getDocument()->addOnloadScript($script);

        if ( strlen($tag) )
        {
            $this->assign('tag', $tag);

//            OW::getDocument()->setTitle(OW::getLanguage()->text('video', 'meta_title_video_tagged_as', array('tag' => $tag)));
//            OW::getDocument()->setDescription(OW::getLanguage()->text('video', 'meta_description_video_tagged_as', array('tag' => $tag)));

            $params = array(
                "sectionKey" => "video",
                "entityKey" => "tagList",
                "title" => "video+meta_title_tag_list",
                "description" => "video+meta_desc_tag_list",
                "keywords" => "video+meta_keywords_tag_list",
                "vars" => array( "video_tag_name" => $tag )
            );

            OW::getEventManager()->trigger(new OW_Event("base.provide_page_meta_info", $params));

        }
        else
        {
            $tags = new BASE_CMP_EntityTagCloud('video');
            $tags->setRouteName('view_tagged_list');
            $this->addComponent('tags', $tags);

            //OW::getDocument()->setTitle(OW::getLanguage()->text('video', 'meta_title_video_tagged'));
            $tagsArr = BOL_TagService::getInstance()->findMostPopularTags('video', 20);

            $labels = array();
            foreach ( $tagsArr as $t )
            {
                $labels[] = $t['label'];
            }
            $tagStr = $tagsArr ? implode(', ', $labels) : '';
            //OW::getDocument()->setDescription(OW::getLanguage()->text('video', 'meta_description_video_tagged', array('topTags' => $tagStr)));

            $params = array(
                "sectionKey" => "video",
                "entityKey" => "taggedList",
                "title" => "video+meta_title_tagged_list",
                "description" => "video+meta_desc_tagged_list",
                "keywords" => "video+meta_keywords_tagged_list"
            );

            OW::getEventManager()->trigger(new OW_Event("base.provide_page_meta_info", $params));
        }

        $this->assign('listType', 'tagged');

        // check auth
        $showAddButton = true;
        $status = BOL_AuthorizationService::getInstance()->getActionStatus('video', 'add');

        if ( $status['status'] == BOL_AuthorizationService::STATUS_AVAILABLE )
        {
            $script = '$("#btn-add-new-video").click(function(){
                document.location.href = ' . json_encode(OW::getRouter()->urlFor('VIDEO_CTRL_Add', 'index')) . ';
            });';

            OW::getDocument()->addOnloadScript($script);
        }
        else if ( $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
        {
            $script = '$("#btn-add-new-video").click(function(){
                OW.authorizationLimitedFloatbox('.json_encode($status['msg']).');
            });';

            OW::getDocument()->addOnloadScript($script);
        }
        else
        {
            $showAddButton = false;
        }

        $this->assign('showAddButton', $showAddButton);

        OW::getDocument()->setHeading(OW::getLanguage()->text('video', 'page_title_browse_video'));
        OW::getDocument()->setHeadingIconClass('ow_ic_video');
    }

    /**
     * Method acts as ajax responder. Calls methods using ajax
     *
     * @throws Redirect404Exception
     * @return string
     */
    public function ajaxResponder()
    {
        if ( isset($_POST['ajaxFunc']) && OW::getRequest()->isAjax() )
        {
            $callFunc = (string) $_POST['ajaxFunc'];

            $result = call_user_func(array($this, $callFunc), $_POST);
        }
        else
        {
            throw new Redirect404Exception();
        }

        exit(json_encode($result));
    }

    /**
     * Set video clip approval status (approved | blocked)
     *
     * @param array $params
     * @throws Redirect404Exception
     * @return array
     */
    public function ajaxSetApprovalStatus( $params )
    {
        $clipId = $params['clipId'];
        $status = $params['status'];

        $isModerator = OW::getUser()->isAuthorized('video');

        if ( !$isModerator )
        {
            throw new Redirect404Exception();
        }

        $setStatus = $this->clipService->updateClipStatus($clipId, $status);

        if ( $setStatus )
        {
            $return = array('result' => true, 'msg' => OW::getLanguage()->text('video', 'status_changed'));
        }
        else
        {
            $return = array('result' => false, 'error' => OW::getLanguage()->text('video', 'status_not_changed'));
        }

        return $return;
    }

    /**
     * Deletes video clip
     *
     * @param array $params
     * @throws Redirect404Exception
     * @return array
     */
    public function ajaxDeleteClip( $params )
    {
        $clipId = $params['clipId'];

        $ownerId = $this->clipService->findClipOwner($clipId);
        $isOwner = OW::getUser()->getId() == $ownerId;
        $isModerator = OW::getUser()->isAuthorized('video');

        if ( !$isOwner && !$isModerator )
        {
            throw new Redirect404Exception();
        }

        $delResult = $this->clipService->deleteClip($clipId);

        if ( $delResult )
        {
            OW::getFeedback()->info(OW::getLanguage()->text('video', 'clip_deleted'));

            $return = array(
                'result' => true,
                'url' => OW_Router::getInstance()->urlForRoute('video_view_list')
            );
        }
        else
        {
            $return = array(
                'result' => false,
                'error' => OW::getLanguage()->text('video', 'clip_not_deleted')
            );
        }

        return $return;
    }

    /**
     * Set 'is featured' status to video clip
     *
     * @param array $params
     * @throws Redirect404Exception
     * @return array
     */
    public function ajaxSetFeaturedStatus( $params )
    {
        $clipId = $params['clipId'];
        $status = $params['status'];

        $isModerator = OW::getUser()->isAuthorized('video');

        if ( !$isModerator )
        {
            throw new Redirect404Exception();
        }

        $setResult = $this->clipService->updateClipFeaturedStatus($clipId, $status);

        if ( $setResult )
        {
            $return = array('result' => true, 'msg' => OW::getLanguage()->text('video', 'status_changed'));
        }
        else
        {
            $return = array('result' => false, 'error' => OW::getLanguage()->text('video', 'status_not_changed'));
        }

        return $return;
    }
    
    public function approve( $params )
    {
        $entityId = $params["clipId"];
        $entityType = VIDEO_CLASS_ContentProvider::ENTITY_TYPE;
        
        $backUrl = OW::getRouter()->urlForRoute("view_clip", array(
            "id" => $entityId
        ));
        
        $event = new OW_Event("moderation.approve", array(
            "entityType" => $entityType,
            "entityId" => $entityId
        ));
        
        OW::getEventManager()->trigger($event);
        
        $data = $event->getData();
        if ( empty($data) )
        {
            $this->redirect($backUrl);
        }
        
        if ( $data["message"] )
        {
            OW::getFeedback()->info($data["message"]);
        }
        else
        {
            OW::getFeedback()->error($data["error"]);
        }
        
        $this->redirect($backUrl);
    }
}

/**
 * Video edit form class
 */
class videoEditForm extends Form
{

    /**
     * Class constructor
     *
     */
    public function __construct( $clipId )
    {
        parent::__construct('videoEditForm');

        $language = OW::getLanguage();

        // clip id field
        $clipIdField = new HiddenField('id');
        $clipIdField->setRequired(true);
        $this->addElement($clipIdField);

        // title Field
        $titleField = new TextField('title');
        $titleField->addValidator(new StringValidator(1, 128));
        $titleField->setRequired(true);
        $this->addElement($titleField->setLabel($language->text('video', 'title')));

        // description Field
        $descField = new WysiwygTextarea('description');
        $this->addElement($descField->setLabel($language->text('video', 'description')));

        $code = new Textarea('code');
        $code->setRequired(true);
        $this->addElement($code->setLabel($language->text('video', 'code')));

        $entityTags = BOL_TagService::getInstance()->findEntityTags($clipId, 'video');

        if ( $entityTags )
        {
            $tags = array();
            foreach ( $entityTags as $entityTag )
            {
                $tags[] = $entityTag->label;
            }

            $tagsField = new TagsInputField('tags');
            $tagsField->setValue($tags);
        }
        else
        {
            $tagsField = new TagsInputField('tags');
        }

        $this->addElement($tagsField->setLabel($language->text('video', 'tags')));

        $submit = new Submit('edit');
        $submit->setValue($language->text('video', 'btn_edit'));
        $this->addElement($submit);
    }

    /**
     * Updates video clip
     *
     * @return boolean
     */
    public function process()
    {
        $values = $this->getValues();
        $clipService = VIDEO_BOL_ClipService::getInstance();

        if ( $values['id'] )
        {
            $clip = $clipService->findClipById($values['id']);

            if ( $clip )
            {
                $clip->title = htmlspecialchars($values['title']);
                $description = UTIL_HtmlTag::stripJs($values['description']);
                $description = UTIL_HtmlTag::stripTags($description, array('frame', 'style'), array(), true);
                $clip->description = $description;
                if ( $clip->code != $values['code'] )
                {
                    $prov = new VideoProviders($values['code']);
                    $clip->provider = $prov->detectProvider();
                    $thumbUrl = $prov->getProviderThumbUrl($clip->provider);
                    if ( $thumbUrl != VideoProviders::PROVIDER_UNDEFINED )
                    {
                        $clip->thumbUrl = $thumbUrl;
                    }
                    $clip->thumbCheckStamp = time();
                }
                $clip->code = UTIL_HtmlTag::stripJs($values['code']);

                BOL_TagService::getInstance()->updateEntityTags(
                    $clip->id,
                    'video',
                    $values['tags']
                );

                $clipService->updateClip($clip);

                return array('result' => true, 'id' => $clip->id);
            }
        }
        else
        {
            return array('result' => false, 'id' => null);
        }

        return false;
    }
}