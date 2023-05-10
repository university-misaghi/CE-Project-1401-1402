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
 * Groups
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.groups.controllers
 * @since 1.0
 */
class GROUPS_CTRL_Groups extends OW_ActionController
{
    /**
     *
     * @var GROUPS_BOL_Service
     */
    private $service;

    public function __construct()
    {
        $this->service = GROUPS_BOL_Service::getInstance();

        if ( !OW::getRequest()->isAjax() )
        {
            $mainMenuItem = OW::getDocument()->getMasterPage()->getMenu(OW_Navigation::MAIN)->getElement('main_menu_list', 'groups');
            if ( $mainMenuItem !== null )
            {
                $mainMenuItem->setActive(true);
            }
        }
    }

    public function index()
    {
        $this->mostPopularList();
    }

    public function customize( $params )
    {
        $params['mode'] = 'customize';

        $this->view($params);
    }

    public function view( $params )
    {
        $groupId = (int) $params['groupId'];

        if ( empty($groupId) )
        {
            throw new Redirect404Exception();
        }

        $groupDto = $this->service->findGroupById($groupId);

        if ( $groupDto === null )
        {
            throw new Redirect404Exception();
        }
        
//        OW::getDocument()->addMetaInfo('og:title', strip_tags($groupDto->title), 'property');
//        OW::getDocument()->addMetaInfo('og:description', strip_tags($groupDto->description), 'property');
//        OW::getDocument()->addMetaInfo('og:url', OW_URL_HOME . OW::getRequest()->getRequestUri(), 'property');
//        OW::getDocument()->addMetaInfo('og:site_name', OW::getConfig()->getValue('base', 'site_name'), 'property');

        $language = OW::getLanguage();

        if ( !$this->service->isCurrentUserCanView($groupDto) )
        {
            if ( $groupDto->status != GROUPS_BOL_Group::STATUS_ACTIVE )
            {
                throw new Redirect403Exception();
            }
            
            $this->assign('permissionMessage', $language->text('groups', 'view_no_permission'));
            
            return;
        }

        $invite = $this->service->findInvite($groupDto->id, OW::getUser()->getId());

        if ( $invite !== null )
        {
            OW::getRegistry()->set('groups.hide_console_invite_item', true);

            $this->service->markInviteAsViewed($groupDto->id, OW::getUser()->getId());
        }

        if ( $groupDto->whoCanView == GROUPS_BOL_Service::WCV_INVITE && !OW::getUser()->isAuthorized('groups') )
        {
            if ( !OW::getUser()->isAuthenticated() )
            {
                $this->redirect(OW::getRouter()->urlForRoute('groups-private-group', array(
                    'groupId' => $groupDto->id
                )));
            }

            $user = $this->service->findUser($groupDto->id, OW::getUser()->getId());

            if ( $groupDto->whoCanView == GROUPS_BOL_Service::WCV_INVITE && $invite === null && $user === null )
            {
                $this->redirect(OW::getRouter()->urlForRoute('groups-private-group', array(
                    'groupId' => $groupDto->id
                )));
            }
        }

        OW::getDocument()->setTitle($language->text('groups', 'view_page_title', array(
            'group_name' => strip_tags($groupDto->title)
        )));

        OW::getDocument()->setDescription($language->text('groups', 'view_page_description', array(
            'description' => UTIL_String::truncate(strip_tags($groupDto->description), 200)
        )));

        $place = 'group';

        $customizeUrls = array(
            'customize' => OW::getRouter()->urlForRoute('groups-customize', array('mode' => 'customize', 'groupId' => $groupId)),
            'normal' => OW::getRouter()->urlForRoute('groups-view', array('groupId' => $groupId))
        );

        $componentAdminService = BOL_ComponentAdminService::getInstance();
        $componentEntityService = BOL_ComponentEntityService::getInstance();

        $userCustomizeAllowed = $componentAdminService->findPlace($place)->editableByUser;
        $ownerMode = $groupDto->userId == OW::getUser()->getId();
        $allowCustomize = $ownerMode || OW::getUser()->isAuthorized("groups");

        $customize = !empty($params['mode']) && $params['mode'] == 'customize';

        if ( !( $userCustomizeAllowed && $allowCustomize ) && $customize )
        {
            $this->redirect($customizeUrls['normal']);
        }

        $template = $customize ? 'drag_and_drop_entity_panel_customize' : 'drag_and_drop_entity_panel';

        $schemeList = $componentAdminService->findSchemeList();
        $defaultScheme = $componentAdminService->findSchemeByPlace($place);
        if ( empty($defaultScheme) && !empty($schemeList) )
        {
            $defaultScheme = reset($schemeList);
        }

        if ( !$componentAdminService->isCacheExists($place) )
        {
            $state = array();
            $state['defaultComponents'] = $componentAdminService->findPlaceComponentList($place);
            $state['defaultPositions'] = $componentAdminService->findAllPositionList($place);
            $state['defaultSettings'] = $componentAdminService->findAllSettingList();
            $state['defaultScheme'] = $defaultScheme;

            $componentAdminService->saveCache($place, $state);
        }

        $state = $componentAdminService->findCache($place);

        $defaultComponents = $state['defaultComponents'];
        $defaultPositions = $state['defaultPositions'];
        $defaultSettings = $state['defaultSettings'];
        $defaultScheme = $state['defaultScheme'];

        if ( $userCustomizeAllowed )
        {
            if ( !$componentEntityService->isEntityCacheExists($place, $groupId) )
            {
                $entityCache = array();
                $entityCache['entityComponents'] = $componentEntityService->findPlaceComponentList($place, $groupId);
                $entityCache['entitySettings'] = $componentEntityService->findAllSettingList($groupId);
                $entityCache['entityPositions'] = $componentEntityService->findAllPositionList($place, $groupId);

                $componentEntityService->saveEntityCache($place, $groupId, $entityCache);
            }

            $entityCache = $componentEntityService->findEntityCache($place, $groupId);
            $entityComponents = $entityCache['entityComponents'];
            $entitySettings = $entityCache['entitySettings'];
            $entityPositions = $entityCache['entityPositions'];
        }
        else
        {
            $entityComponents = array();
            $entitySettings = array();
            $entityPositions = array();
        }

        $componentPanel = new BASE_CMP_DragAndDropEntityPanel($place, $groupId, $defaultComponents, $customize, $template);
        $componentPanel->setAdditionalSettingList(array(
            'entityId' => $groupId,
            'entity' => 'groups'
        ));

        if ( $allowCustomize )
        {
            $componentPanel->allowCustomize($userCustomizeAllowed);
            $componentPanel->customizeControlCunfigure($customizeUrls['customize'], $customizeUrls['normal']);
        }

        $componentPanel->setSchemeList($schemeList);
        $componentPanel->setPositionList($defaultPositions);
        $componentPanel->setSettingList($defaultSettings);
        $componentPanel->setScheme($defaultScheme);

        /*
         * This feature was disabled for users
         * if ( !empty($userScheme) )
          {
          $componentPanel->setUserScheme($userScheme);
          } */

        if ( !empty($entityComponents) )
        {
            $componentPanel->setEntityComponentList($entityComponents);
        }

        if ( !empty($entityPositions) )
        {
            $componentPanel->setEntityPositionList($entityPositions);
        }

        if ( !empty($entitySettings) )
        {
            $componentPanel->setEntitySettingList($entitySettings);
        }

        $this->assign('componentPanel', $componentPanel->render());

        $params = array(
            "sectionKey" => "groups",
            "entityKey" => "groupPage",
            "title" => "groups+meta_title_groups_page",
            "description" => "groups+meta_desc_groups_page",
            "keywords" => "groups+meta_keywords_groups_page",
            "vars" => array( "group_title" => $groupDto->title, "group_description" => $groupDto->description )
        );

        OW::getEventManager()->trigger(new OW_Event("base.provide_page_meta_info", $params));
    }

    public function create()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        if ( !$this->service->isCurrentUserCanCreate() )
        {
            $permissionStatus = BOL_AuthorizationService::getInstance()->getActionStatus('groups', 'create');
            
            throw new AuthorizationException($permissionStatus['msg']);
        }
        
        $language = OW::getLanguage();

        OW::getDocument()->setHeading($language->text('groups', 'create_heading'));
        OW::getDocument()->setHeadingIconClass('ow_ic_new');
        OW::getDocument()->setTitle($language->text('groups', 'create_page_title'));
        OW::getDocument()->setDescription($language->text('groups', 'create_page_description'));

        $form = new GROUPS_CreateGroupForm();

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $groupDto = $form->process();

            if ( empty($groupDto) )
            {
                $this->redirect();
            }

            $this->service->addUser($groupDto->id, OW::getUser()->getId());

            OW::getFeedback()->info($language->text('groups', 'create_success_msg'));
            $this->redirect($this->service->getGroupUrl($groupDto));
        }

        $this->addForm($form);
    }

    public function delete( $params )
    {
        if ( empty($params['groupId']) )
        {
            throw new Redirect404Exception();
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $groupDto = $this->service->findGroupById($params['groupId']);

        if ( empty($groupDto) )
        {
            throw new Redirect404Exception();
        }

        $isOwner = OW::getUser()->getId() == $groupDto->userId;
        $isModerator = OW::getUser()->isAuthorized('groups');

        if ( !$isOwner && !$isModerator )
        {
            throw new Redirect403Exception();
        }

        $this->service->deleteGroup($groupDto->id);
        OW::getFeedback()->info(OW::getLanguage()->text('groups', 'delete_complete_msg'));

        $this->redirect(OW::getRouter()->urlForRoute('groups-index'));
    }

    public function edit( $params )
    {
        $groupId = (int) $params['groupId'];

        if ( empty($groupId) )
        {
            throw new Redirect404Exception();
        }

        $groupDto = $this->service->findGroupById($groupId);

        if ( !$this->service->isCurrentUserCanEdit($groupDto) )
        {
            throw new Redirect404Exception();
        }

        if ( $groupId === null )
        {
            throw new Redirect404Exception();
        }

        $form = new GROUPS_EditGroupForm($groupDto);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            if ( $form->process() )
            {
                OW::getFeedback()->info(OW::getLanguage()->text('groups', 'edit_success_msg'));
            }
            $this->redirect();
        }

        $this->addForm($form);

        $this->assign('imageUrl', empty($groupDto->imageHash) ? false : $this->service->getGroupImageUrl($groupDto));

        $deleteUrl = OW::getRouter()->urlFor('GROUPS_CTRL_Groups', 'delete', array('groupId' => $groupDto->id));
        $viewUrl = $this->service->getGroupUrl($groupDto);
        $lang = OW::getLanguage()->text('groups', 'delete_confirm_msg');

        $js = UTIL_JsGenerator::newInstance();
        $js->newFunction('window.location.href=url', array('url'), 'redirect');
        $js->jQueryEvent('#groups-delete_btn', 'click', UTIL_JsGenerator::composeJsString(
                'if( confirm({$lang}) ) redirect({$url});', array('url' => $deleteUrl, 'lang' => $lang)));
        $js->jQueryEvent('#groups-back_btn', 'click', UTIL_JsGenerator::composeJsString(
                'redirect({$url});', array('url' => $viewUrl)));

        OW::getDocument()->addOnloadScript($js);
    }

    public function join( $params )
    {
        if ( empty($params['groupId']) )
        {
            throw new Redirect404Exception();
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $groupId = (int) $params['groupId'];
        $userId = OW::getUser()->getId();
        
        $groupDto = $this->service->findGroupById($groupId);

        if ( $groupDto === null )
        {
            throw new Redirect404Exception();
        }
        
        if ( !$this->service->isCurrentUserCanView($groupDto) )
        {
            throw new Redirect403Exception();
        }

        $invite = $this->service->findInvite($groupDto->id, $userId);

        if ( $invite !== null )
        {
            $this->service->markInviteAsViewed($groupDto->id, $userId);
        } 
        else if ( $groupDto->whoCanView == GROUPS_BOL_Service::WCV_INVITE )
        {
            $this->redirect(OW::getRouter()->urlForRoute('groups-private-group', array(
                'groupId' => $groupDto->id
            )));
        }
        
        GROUPS_BOL_Service::getInstance()->addUser($groupId, $userId);

        $redirectUrl = OW::getRouter()->urlForRoute('groups-view', array('groupId' => $groupId));
        OW::getFeedback()->info(OW::getLanguage()->text('groups', 'join_complete_message'));

        $this->redirect($redirectUrl);
    }

    public function declineInvite( $params )
    {
        if ( empty($params['groupId']) )
        {
            throw new Redirect404Exception();
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $groupId = (int) $params['groupId'];
        $userId = OW::getUser()->getId();

        GROUPS_BOL_Service::getInstance()->deleteInvite($groupId, $userId);

        $redirectUrl = OW::getRouter()->urlForRoute('groups-invite-list');
        OW::getFeedback()->info(OW::getLanguage()->text('groups', 'invite_declined_message'));

        $this->redirect($redirectUrl);
    }

    public function leave( $params )
    {
        if ( empty($params['groupId']) )
        {
            throw new Redirect404Exception();
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $groupId = (int) $params['groupId'];
        $userId = OW::getUser()->getId();

        GROUPS_BOL_Service::getInstance()->deleteUser($groupId, $userId);

        $redirectUrl = OW::getRouter()->urlForRoute('groups-view', array('groupId' => $groupId));
        OW::getFeedback()->info(OW::getLanguage()->text('groups', 'leave_complete_message'));

        $this->redirect($redirectUrl);
    }

    public function deleteUser( $params )
    {
        if ( empty($params['groupId']) || empty($params['userId']) )
        {
            throw new Redirect404Exception();
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $groupDto = GROUPS_BOL_Service::getInstance()->findGroupById($params['groupId']);

        if ( $groupDto === null )
        {
            throw new Redirect404Exception();
        }

        $isModerator = OW::getUser()->isAuthorized('groups');

        if ( !$isModerator && $groupDto->userId != OW::getUser()->getId()  )
        {
            throw new Redirect403Exception();
        }

        $groupId = (int) $groupDto->id;
        $userId = $params['userId'];

        GROUPS_BOL_Service::getInstance()->deleteUser($groupId, $userId);

        //$redirectUrl = OW::getRouter()->urlForRoute('groups-user-list', array('groupId' => $groupId));

        OW::getFeedback()->info(OW::getLanguage()->text('groups', 'delete_user_success_message'));

        $redirectUri = urldecode($_GET['redirectUri']);
        $this->redirect(OW_URL_HOME . $redirectUri);
    }

    private function getPaging( $page, $perPage, $onPage )
    {
        $paging['page'] = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $paging['perPage'] = $perPage;

        $paging['first'] = ($paging['perPage'] - 1) * $paging['perPage'];
        $paging['count'] = $paging['perPage'];
    }

    public function mostPopularList()
    {
        $language = OW::getLanguage();

        OW::getDocument()->setHeading($language->text('groups', 'group_list_heading'));
        OW::getDocument()->setHeadingIconClass('ow_ic_files');

        OW::getDocument()->setTitle($language->text('groups', 'popular_list_page_title'));
        OW::getDocument()->setDescription($language->text('groups', 'popular_list_page_description'));

        if ( !$this->service->isCurrentUserCanViewList() )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('groups', 'view');
            throw new AuthorizationException($status['msg']);
        }

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $perPage = 20;
        $first = ($page - 1) * $perPage;
        $count = $perPage;

        $dtoList = $this->service->findGroupList(GROUPS_BOL_Service::LIST_MOST_POPULAR, $first, $count);
        $listCount = $this->service->findGroupListCount(GROUPS_BOL_Service::LIST_MOST_POPULAR);

        $paging = new BASE_CMP_Paging($page, ceil($listCount / $perPage), 5);

        $menu = $this->getGroupListMenu();
        $menu->getElement('popular')->setActive(true);
        $this->assign('listType', 'popular');

        $this->displayGroupList($dtoList, $paging, $menu);

        $params = array(
            "sectionKey" => "groups",
            "entityKey" => "mostPopular",
            "title" => "groups+meta_title_most_popular",
            "description" => "groups+meta_desc_most_popular",
            "keywords" => "groups+meta_keywords_most_popular"
        );

        OW::getEventManager()->trigger(new OW_Event("base.provide_page_meta_info", $params));
    }

    public function latestList()
    {
        $language = OW::getLanguage();

        OW::getDocument()->setHeading($language->text('groups', 'group_list_heading'));
        OW::getDocument()->setHeadingIconClass('ow_ic_files');

        OW::getDocument()->setTitle($language->text('groups', 'latest_list_page_title'));
        OW::getDocument()->setDescription($language->text('groups', 'latest_list_page_description'));

        if ( !$this->service->isCurrentUserCanViewList() )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('groups', 'view');
            throw new AuthorizationException($status['msg']);
        }

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $perPage = 20;
        $first = ($page - 1) * $perPage;
        $count = $perPage;

        $dtoList = $this->service->findGroupList(GROUPS_BOL_Service::LIST_LATEST, $first, $count);
        $listCount = $this->service->findGroupListCount(GROUPS_BOL_Service::LIST_LATEST);

        $paging = new BASE_CMP_Paging($page, ceil($listCount / $perPage), 5);

        $menu = $this->getGroupListMenu();
        $menu->getElement('latest')->setActive(true);
        $this->assign('listType', 'latest');

        $this->displayGroupList($dtoList, $paging, $menu);

        $params = array(
            "sectionKey" => "groups",
            "entityKey" => "latest",
            "title" => "groups+meta_title_latest",
            "description" => "groups+meta_desc_latest",
            "keywords" => "groups+meta_keywords_latest"
        );

        OW::getEventManager()->trigger(new OW_Event("base.provide_page_meta_info", $params));
    }

    public function inviteList()
    {
        $userId = OW::getUser()->getId();

        if ( empty($userId) )
        {
            throw new AuthenticateException();
        }

        $language = OW::getLanguage();

        OW::getDocument()->setHeading($language->text('groups', 'group_list_heading'));
        OW::getDocument()->setHeadingIconClass('ow_ic_files');

        OW::getDocument()->setTitle($language->text('groups', 'invite_list_page_title'));

        if ( !$this->service->isCurrentUserCanViewList() )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('groups', 'view');
            throw new AuthorizationException($status['msg']);
        }

        OW::getRegistry()->set('groups.hide_console_invite_item', true);

        $this->service->markAllInvitesAsViewed($userId);

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $perPage = 20;
        $first = ($page - 1) * $perPage;
        $count = $perPage;

        $dtoList = $this->service->findInvitedGroups($userId, $first, $count);
        $listCount = $this->service->findInvitedGroupsCount($userId);

        $paging = new BASE_CMP_Paging($page, ceil($listCount / $perPage), 5);

        $menu = $this->getGroupListMenu();
        $menu->getElement('invite')->setActive(true);
        $this->assign('listType', 'invite');

        $templatePath = OW::getPluginManager()->getPlugin('groups')->getCtrlViewDir() . 'groups_list.html';

        $this->setTemplate($templatePath);

        $acceptUrls = array();
        $declineUrls = array();

        $out = array();

        foreach ( $dtoList as $group )
        {
            $acceptUrls[$group->id] = OW::getRouter()->urlFor('GROUPS_CTRL_Groups', 'join', array(
                'groupId' => $group->id
            ));

            $declineUrls[$group->id] = OW::getRouter()->urlFor('GROUPS_CTRL_Groups', 'declineInvite', array(
                'groupId' => $group->id
            ));
        }

        $acceptLabel = OW::getLanguage()->text('groups', 'invite_accept_label');
        $declineLabel = OW::getLanguage()->text('groups', 'invite_decline_label');

        foreach ( $dtoList as $item )
        {
            /* @var $item GROUPS_BOL_Group */

            $userCount = GROUPS_BOL_Service::getInstance()->findUserListCount($item->id);
            $title = strip_tags($item->title);

            $toolbar = array(
                array(
                    'label' => OW::getLanguage()->text('groups', 'listing_users_label', array(
                        'count' => $userCount
                    ))
                ),

                array(
                    'href' => $acceptUrls[$item->id],
                    'label' => $acceptLabel
                ),

                array(
                    'href' => $declineUrls[$item->id],
                    'label' => $declineLabel
                )
            );

            $out[] = array(
                'id' => $item->id,
                'url' => OW::getRouter()->urlForRoute('groups-view', array('groupId' => $item->id)),
                'title' => $title,
                'imageTitle' => $title,
                'content' => strip_tags($item->description),
                'time' => UTIL_DateTime::formatDate($item->timeStamp),
                'imageSrc' => GROUPS_BOL_Service::getInstance()->getGroupImageUrl($item),
                'users' => $userCount,
                'toolbar' => $toolbar
            );
        }

        $this->addComponent('paging', $paging);

        if ( !empty($menu) )
        {
            $this->addComponent('menu', $menu);
        }
        else
        {
            $this->assign('menu', '');
        }

        if ( !$this->service->isCurrentUserCanCreate() )
        {
            $authStatus = BOL_AuthorizationService::getInstance()->getActionStatus('groups', 'create');
            if ( $authStatus['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
            {
                $this->assign("authMsg", json_encode($authStatus["msg"]));
                $this->assign("showCreate", true);
            }
            else 
            {
                $this->assign("showCreate", false);
            }
        }

        $this->assign('list', $out);
    }


    public function myGroupList()
    {
        $userId = OW::getUser()->getId();

        if ( empty($userId) )
        {
            throw new AuthenticateException();
        }

        $language = OW::getLanguage();

        OW::getDocument()->setHeading($language->text('groups', 'group_list_heading'));
        OW::getDocument()->setHeadingIconClass('ow_ic_files');

        OW::getDocument()->setTitle($language->text('groups', 'my_list_page_title'));

        if ( !$this->service->isCurrentUserCanViewList() )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('groups', 'view');
            throw new AuthorizationException($status['msg']);
        }

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $perPage = 20;
        $first = ($page - 1) * $perPage;
        $count = $perPage;

        $dtoList = $this->service->findMyGroups($userId, $first, $count);
        $listCount = $this->service->findMyGroupsCount($userId);

        $paging = new BASE_CMP_Paging($page, ceil($listCount / $perPage), 5);

        $menu = $this->getGroupListMenu();
        $menu->getElement('my')->setActive(true);
        $this->assign('listType', 'my');

        $this->displayGroupList($dtoList, $paging, $menu);
    }

    public function userGroupList( $params )
    {
        $userDto = BOL_UserService::getInstance()->findByUsername(trim($params['user']));

        if ( empty($userDto) )
        {
            throw new Redirect404Exception();
        }

        // privacy check
        $userId = $userDto->id;
        $viewerId = OW::getUser()->getId();
        $ownerMode = $userId == $viewerId;
        $modPermissions = OW::getUser()->isAuthorized('groups');

        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => GROUPS_BOL_Service::PRIVACY_ACTION_VIEW_MY_GROUPS, 'ownerId' => $userId, 'viewerId' => $viewerId);
            $event = new OW_Event('privacy_check_permission', $privacyParams);

            OW::getEventManager()->trigger($event);
        }

        $language = OW::getLanguage();
        OW::getDocument()->setTitle($language->text('groups', 'user_groups_page_title'));
        OW::getDocument()->setDescription($language->text('groups', 'user_groups_page_description'));
        OW::getDocument()->setHeading($language->text('groups', 'user_group_list_heading', array(
                'userName' => BOL_UserService::getInstance()->getDisplayName($userDto->id)
            )));

        OW::getDocument()->setHeadingIconClass('ow_ic_files');

        if ( !$this->service->isCurrentUserCanViewList() )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('groups', 'view');
            throw new AuthorizationException($status['msg']);
        }

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $perPage = 20;
        $first = ($page - 1) * $perPage;
        $count = $perPage;

        $dtoList = $this->service->findUserGroupList($userDto->id, $first, $count);
        $listCount = $this->service->findUserGroupListCount($userDto->id);

        $paging = new BASE_CMP_Paging($page, ceil($listCount / $perPage), 5);

        $this->assign('hideCreateNew', true);
        
        $this->assign('listType', 'user');

        $this->displayGroupList($dtoList, $paging);

        $vars = BOL_SeoService::getInstance()->getUserMetaInfo($userDto);

        $params = array(
            "sectionKey" => "groups",
            "entityKey" => "userGroups",
            "title" => "groups+meta_title_user_groups",
            "description" => "groups+meta_desc_user_groups",
            "keywords" => "groups+meta_keywords_user_groups",
            "vars" => $vars
        );

        OW::getEventManager()->trigger(new OW_Event("base.provide_page_meta_info", $params));
    }

    private function displayGroupList( $list, $paging, $menu = null )
    {
        $templatePath = OW::getPluginManager()->getPlugin('groups')->getCtrlViewDir() . 'groups_list.html';
        $this->setTemplate($templatePath);

        $out = array();

        foreach ( $list as $item )
        {
            /* @var $item GROUPS_BOL_Group */

            $userCount = GROUPS_BOL_Service::getInstance()->findUserListCount($item->id);
            $title = strip_tags($item->title);

            $toolbar = array(
                array(
                    'label' => OW::getLanguage()->text('groups', 'listing_users_label', array(
                        'count' => $userCount
                    ))
                )
            );

            $out[] = array(
                'id' => $item->id,
                'url' => OW::getRouter()->urlForRoute('groups-view', array('groupId' => $item->id)),
                'title' => $title,
                'imageTitle' => $title,
                'content' => UTIL_String::truncate(strip_tags($item->description), 300, '...'),
                'time' => UTIL_DateTime::formatDate($item->timeStamp),
                'imageSrc' => GROUPS_BOL_Service::getInstance()->getGroupImageUrl($item),
                'users' => $userCount,
                'toolbar' => $toolbar
            );
        }

        $this->addComponent('paging', $paging);

        if ( !empty($menu) )
        {
            $this->addComponent('menu', $menu);
        }
        else
        {
            $this->assign('menu', '');
        }

        $this->assign("showCreate", true);
        
        if ( !$this->service->isCurrentUserCanCreate() )
        {
            $authStatus = BOL_AuthorizationService::getInstance()->getActionStatus('groups', 'create');
            if ( $authStatus['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
            {
                $this->assign("authMsg", json_encode($authStatus["msg"]));
            }
            else 
            {
                $this->assign("showCreate", false);
            }
        }
        
        $this->assign('list', $out);
    }

    public function userList( $params )
    {
        $groupId = (int) $params['groupId'];
        $groupDto = $this->service->findGroupById($groupId);

        if ( $groupDto === null )
        {
            throw new Redirect404Exception();
        }

        if ( $groupDto->whoCanView == GROUPS_BOL_Service::WCV_INVITE && !OW::getUser()->isAuthorized('groups') )
        {
            if ( !OW::getUser()->isAuthenticated() )
            {
                $this->redirect(OW::getRouter()->urlForRoute('groups-private-group', array(
                    'groupId' => $groupDto->id
                )));
            }

            $invite = $this->service->findInvite($groupDto->id, OW::getUser()->getId());
            $user = $this->service->findUser($groupDto->id, OW::getUser()->getId());

            if ( $groupDto->whoCanView == GROUPS_BOL_Service::WCV_INVITE && $invite === null && $user === null )
            {
                $this->redirect(OW::getRouter()->urlForRoute('groups-private-group', array(
                    'groupId' => $groupDto->id
                )));
            }
        }

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $perPage = 20;
        $first = ($page - 1) * $perPage;
        $count = $perPage;

        $dtoList = $this->service->findUserList($groupId, $first, $count);
        $listCount = $this->service->findUserListCount($groupId);

        $listCmp = new GROUPS_UserList($groupDto, $dtoList, $listCount, 20);
        $this->addComponent('listCmp', $listCmp);
        $this->addComponent('groupBriefInfo', new GROUPS_CMP_BriefInfo($groupId));
        
        $this->assign("groupId", $groupId);

        $params = array(
            "sectionKey" => "groups",
            "entityKey" => "groupUsers",
            "title" => "groups+meta_title_group_users",
            "description" => "groups+meta_desc_group_users",
            "keywords" => "groups+meta_keywords_group_users",
            "vars" => array( "group_title" => $groupDto->title )
        );

        OW::getEventManager()->trigger(new OW_Event("base.provide_page_meta_info", $params));
    }

    private function getGroupListMenu()
    {

        $language = OW::getLanguage();

        $items = array();

        $items[0] = new BASE_MenuItem();
        $items[0]->setLabel($language->text('groups', 'group_list_menu_item_popular'))
            ->setKey('popular')
            ->setUrl(OW::getRouter()->urlForRoute('groups-most-popular'))
            ->setOrder(1)
            ->setIconClass('ow_ic_comment');

        $items[1] = new BASE_MenuItem();
        $items[1]->setLabel($language->text('groups', 'group_list_menu_item_latest'))
            ->setKey('latest')
            ->setUrl(OW::getRouter()->urlForRoute('groups-latest'))
            ->setOrder(2)
            ->setIconClass('ow_ic_clock');


        if ( OW::getUser()->isAuthenticated() )
        {
            $items[2] = new BASE_MenuItem();
            $items[2]->setLabel($language->text('groups', 'group_list_menu_item_my'))
                ->setKey('my')
                ->setUrl(OW::getRouter()->urlForRoute('groups-my-list'))
                ->setOrder(3)
                ->setIconClass('ow_ic_files');

            $items[3] = new BASE_MenuItem();
            $items[3]->setLabel($language->text('groups', 'group_list_menu_item_invite'))
                ->setKey('invite')
                ->setUrl(OW::getRouter()->urlForRoute('groups-invite-list'))
                ->setOrder(4)
                ->setIconClass('ow_ic_bookmark');
        }

        return new BASE_CMP_ContentMenu($items);
    }

    public function follow()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $groupId = (int) $_GET['groupId'];

        $groupDto = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);

        if ( $groupDto === null )
        {
            throw new Redirect404Exception();
        }

        $eventParams = array(
            'userId' => OW::getUser()->getId(),
            'feedType' => GROUPS_BOL_Service::ENTITY_TYPE_GROUP,
            'feedId' => $groupId
        );

        $title = UTIL_String::truncate(strip_tags($groupDto->title), 100, '...');

        switch ( $_GET['command'] )
        {
            case 'follow':
                OW::getEventManager()->call('feed.add_follow', $eventParams);
                OW::getFeedback()->info(OW::getLanguage()->text('groups', 'feed_follow_complete_msg', array('groupTitle' => $title)));
                break;

            case 'unfollow':
                OW::getEventManager()->call('feed.remove_follow', $eventParams);
                OW::getFeedback()->info(OW::getLanguage()->text('groups', 'feed_unfollow_complete_msg', array('groupTitle' => $title)));
                break;
        }

        $this->redirect(OW_URL_HOME . $_GET['backUri']);
    }

    public function invite()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }

        $userId = OW::getUser()->getId();

        if ( empty($userId) )
        {
            throw new AuthenticateException();
        }

        $respoce = array();

        $userIds = json_decode($_POST['userIdList']);
        $groupId = $_POST['groupId'];
        $allIdList = json_decode($_POST['allIdList']);

        $group = $this->service->findGroupById($groupId);

        $count = 0;
        foreach ( $userIds as $uid )
        {
            $this->service->inviteUser($group->id, $uid, $userId);

            $count++;
        }

        $respoce['messageType'] = 'info';
        $respoce['message'] = OW::getLanguage()->text('groups', 'users_invite_success_message', array('count' => $count));
        $respoce['allIdList'] = array_diff($allIdList, $userIds);

        exit(json_encode($respoce));
    }

    public function privateGroup( $params )
    {
        $language = OW::getLanguage();

        $this->setPageTitle($language->text('groups', 'private_page_title'));
        $this->setPageHeading($language->text('groups', 'private_page_heading'));
        $this->setPageHeadingIconClass('ow_ic_lock');

        $groupId = $params['groupId'];
        $group = $this->service->findGroupById($groupId);

        $avatarList = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($group->userId));
        $displayName = BOL_UserService::getInstance()->getDisplayName($group->userId);
        $userUrl = BOL_UserService::getInstance()->getUserUrl($group->userId);

        $this->assign('group', $group);
        $this->assign('avatar', $avatarList[$group->userId]);
        $this->assign('displayName', $displayName);
        $this->assign('userUrl', $userUrl);
        $this->assign('creator', $language->text('groups', 'creator'));
    }
    
    public function approve( $params )
    {
        $entityId = $params["groupId"];
        $entityType = GROUPS_CLASS_ContentProvider::ENTITY_TYPE;
        
        $backUrl = OW::getRouter()->urlForRoute("groups-view", array(
            "groupId" => $entityId
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

// Additional calsses

class GROUPS_UserList extends BASE_CMP_Users
{
    /**
     *
     * @var GROUPS_BOL_Group
     */
    protected $groupDto;

    public function __construct( GROUPS_BOL_Group $groupDto, $list, $itemCount, $usersOnPage, $showOnline = true)
    {
        parent::__construct($list, $itemCount, $usersOnPage, $showOnline);
        $this->groupDto = $groupDto;
    }

    public function getContextMenu($userId)
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            return null;
        }

        $isOwner = $this->groupDto->userId == OW::getUser()->getId();
        $isGroupModerator = OW::getUser()->isAuthorized('groups');

        $contextActionMenu = new BASE_CMP_ContextAction();

        $contextParentAction = new BASE_ContextAction();
        $contextParentAction->setKey('group_user_' . $userId);
        $contextActionMenu->addAction($contextParentAction);

        if ( ($isOwner || $isGroupModerator) && $userId != OW::getUser()->getId() )
        {
            $contextAction = new BASE_ContextAction();
            $contextAction->setParentKey($contextParentAction->getKey());
            $contextAction->setKey('delete_group_user');
            $contextAction->setLabel(OW::getLanguage()->text('groups', 'delete_group_user_label'));

            if ( $this->groupDto->userId != $userId )
            {
                $callbackUri = OW::getRequest()->getRequestUri();
                $deleteUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlFor('GROUPS_CTRL_Groups', 'deleteUser', array(
                    'groupId' => $this->groupDto->id,
                    'userId' => $userId
                )), array(
                    'redirectUri' => urlencode($callbackUri)
                ));

                $contextAction->setUrl($deleteUrl);

                $contextAction->addAttribute('data-message', OW::getLanguage()->text('groups', 'delete_group_user_confirmation'));
                $contextAction->addAttribute('onclick', "return confirm($(this).data().message)");
            }
            else
            {
                $contextAction->setUrl('javascript://');
                $contextAction->addAttribute('data-message', OW::getLanguage()->text('groups', 'group_owner_delete_error'));
                $contextAction->addAttribute('onclick', "OW.error($(this).data().message); return false;");
            }

            $contextActionMenu->addAction($contextAction);
        }

        return $contextActionMenu;
    }

    public function getFields( $userIdList )
    {
        $fields = array();

        $qs = array();

        $qBdate = BOL_QuestionService::getInstance()->findQuestionByName('birthdate');

        if ( $qBdate !== null && $qBdate->onView )
            $qs[] = 'birthdate';

        $qSex = BOL_QuestionService::getInstance()->findQuestionByName('sex');

        if ( $qSex !== null && $qSex->onView )
            $qs[] = 'sex';

        $questionList = BOL_QuestionService::getInstance()->getQuestionData($userIdList, $qs);

        foreach ( $questionList as $uid => $question )
        {

            $fields[$uid] = array();

            $age = '';

            if ( !empty($question['birthdate']) )
            {
                $date = UTIL_DateTime::parseDate($question['birthdate'], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);

                $age = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']);
            }

            $sexValue = '';
            if ( !empty($question['sex']) )
            {
                $sex = $question['sex'];

                for ( $i = 0; $i < 31; $i++ )
                {
                    $val = pow(2, $i);
                    if ( (int) $sex & $val )
                    {
                        $sexValue .= BOL_QuestionService::getInstance()->getQuestionValueLang('sex', $val) . ', ';
                    }
                }

                if ( !empty($sexValue) )
                {
                    $sexValue = substr($sexValue, 0, -2);
                }
            }

            if ( !empty($sexValue) && !empty($age) )
            {
                $fields[$uid][] = array(
                    'label' => '',
                    'value' => $sexValue . ' ' . $age
                );
            }
        }

        return $fields;
    }
}

class GROUPS_GroupForm extends Form
{
    public function __construct( $formName )
    {
        parent::__construct($formName);

        $this->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);

        $language = OW::getLanguage();

        $field = new TextField('title');
        $field->setRequired(true);
        $field->setLabel($language->text('groups', 'create_field_title_label'));
        $this->addElement($field);

        $field = new WysiwygTextarea('description');
        $field->setLabel($language->text('groups', 'create_field_description_label'));
        $field->setRequired(true);
        $this->addElement($field);

        $field = new GROUPS_Image('image');
        $field->setLabel($language->text('groups', 'create_field_image_label'));
        $field->addValidator(new GROUPS_ImageValidator());
        $this->addElement($field);

        $whoCanView = new RadioField('whoCanView');
        $whoCanView->setRequired();
        $whoCanView->addOptions(
            array(
                GROUPS_BOL_Service::WCV_ANYONE => $language->text('groups', 'form_who_can_view_anybody'),
                GROUPS_BOL_Service::WCV_INVITE => $language->text('groups', 'form_who_can_view_invite')
            )
        );
        $whoCanView->setLabel($language->text('groups', 'form_who_can_view_label'));
        $this->addElement($whoCanView);

        $whoCanInvite = new RadioField('whoCanInvite');
        $whoCanInvite->setRequired();
        $whoCanInvite->addOptions(
            array(
                GROUPS_BOL_Service::WCI_PARTICIPANT => $language->text('groups', 'form_who_can_invite_participants'),
                GROUPS_BOL_Service::WCI_CREATOR => $language->text('groups', 'form_who_can_invite_creator')
            )
        );
        $whoCanInvite->setLabel($language->text('groups', 'form_who_can_invite_label'));
        $this->addElement($whoCanInvite);
    }

    /**
     *
     * @param GROUPS_BOL_Group $group
     * @return GROUPS_BOL_Group
     */
    public function processGroup( GROUPS_BOL_Group $group )
    {
        $values = $this->getValues();
        $service = GROUPS_BOL_Service::getInstance();

        if ( $values['image'] )
        {
            if ( !empty($group->imageHash) )
            {
                OW::getStorage()->removeFile($service->getGroupImagePath($group));
                OW::getStorage()->removeFile($service->getGroupImagePath($group, GROUPS_BOL_Service::IMAGE_SIZE_BIG));
            }

            $group->imageHash = uniqid();
        }

        $group->title = strip_tags($values['title']);
        
        $values['description'] = UTIL_HtmlTag::stripJs($values['description']);
        $values['description'] = UTIL_HtmlTag::stripTags($values['description'], array('frame'), array(), true);
        
        $group->description = $values['description'];
        $group->whoCanInvite = $values['whoCanInvite'];
        $group->whoCanView = $values['whoCanView'];
        
        $service->saveGroup($group);

        if ( !empty($values['image']) )
        {
            $this->saveImages($values['image'], $group);
        }

        return $group;
    }

    protected function saveImages( $postFile, GROUPS_BOL_Group $group )
    {
        $service = GROUPS_BOL_Service::getInstance();
        
        $smallFile = $service->getGroupImagePath($group, GROUPS_BOL_Service::IMAGE_SIZE_SMALL);
        $bigFile = $service->getGroupImagePath($group, GROUPS_BOL_Service::IMAGE_SIZE_BIG);
        
        $tmpDir = OW::getPluginManager()->getPlugin('groups')->getPluginFilesDir();
        $smallTmpFile = $tmpDir . uniqid('small_') . '.jpg';
        $bigTmpFile = $tmpDir . uniqid('big_') . '.jpg';

        $image = new UTIL_Image($postFile['tmp_name']);
        $image->resizeImage(GROUPS_BOL_Service::IMAGE_WIDTH_BIG, null)
            ->saveImage($bigTmpFile)
            ->resizeImage(GROUPS_BOL_Service::IMAGE_WIDTH_SMALL, GROUPS_BOL_Service::IMAGE_WIDTH_SMALL, true)
            ->saveImage($smallTmpFile);

        try
        {
            OW::getStorage()->copyFile($smallTmpFile, $smallFile);
            OW::getStorage()->copyFile($bigTmpFile, $bigFile);
        }
        catch ( Exception $e ) {}

        unlink($smallTmpFile);
        unlink($bigTmpFile);
    }

    public function process()
    {

    }
}

class GROUPS_CreateGroupForm extends GROUPS_GroupForm
{

    public function __construct()
    {
        parent::__construct('GROUPS_CreateGroupForm');

        $this->getElement('title')->addValidator(new GROUPS_UniqueValidator());

        $field = new Submit('save');
        $field->setValue(OW::getLanguage()->text('groups', 'create_submit_btn_label'));
        $this->addElement($field);
    }

    /**
     * (non-PHPdoc)
     * @see ow_plugins/groups/controllers/GROUPS_GroupForm#process()
     */
    public function process()
    {
        $groupDto = new GROUPS_BOL_Group();
        $groupDto->timeStamp = time();
        $groupDto->userId = OW::getUser()->getId();

        $data = array();
        foreach ( $groupDto as $key => $value )
        {
            $data[$key] = $value;
        }

        $event = new OW_Event(GROUPS_BOL_Service::EVENT_BEFORE_CREATE, array('groupId' => $groupDto->id), $data);
        OW::getEventManager()->trigger($event);
        $data = $event->getData();

        foreach ( $data as $k => $v )
        {
            $groupDto->$k = $v;
        }

        $group = $this->processGroup($groupDto);
        
        $is_forum_connected = OW::getConfig()->getValue('groups', 'is_forum_connected');
        // Add forum group
        if ( $is_forum_connected )
        {
            $event = new OW_Event('forum.create_group', array('entity' => 'groups', 'name' => $group->title, 'description' => $group->description, 'entityId' => $group->getId()));
            OW::getEventManager()->trigger($event);
        }
        
        if ( $group )
        {
            $event = new OW_Event(GROUPS_BOL_Service::EVENT_CREATE, array('groupId' => $group->id));
            OW::getEventManager()->trigger($event);
        }
        
        $group = GROUPS_BOL_Service::getInstance()->findGroupById($group->id);
        
        if ( $group->status == GROUPS_BOL_Group::STATUS_ACTIVE )
        {
            BOL_AuthorizationService::getInstance()->trackAction('groups', 'create');
        }
        
        return $group;
    }
}

class GROUPS_EditGroupForm extends GROUPS_GroupForm
{
    /**
     *
     * @var GROUPS_BOL_Group
     */
    private $groupDto;

    public function __construct( GROUPS_BOL_Group $group )
    {
        parent::__construct('GROUPS_EditGroupForm');

        $this->groupDto = $group;

        $this->getElement('title')->setValue($group->title);
        $this->getElement('title')->addValidator(new GROUPS_UniqueValidator($group->title));
        $this->getElement('description')->setValue($group->description);
        $this->getElement('whoCanView')->setValue($group->whoCanView);
        $this->getElement('whoCanInvite')->setValue($group->whoCanInvite);

        $field = new Submit('save');
        $field->setValue(OW::getLanguage()->text('groups', 'edit_submit_btn_label'));
        $this->addElement($field);
    }

    /**
     * (non-PHPdoc)
     * @see ow_plugins/groups/controllers/GROUPS_GroupForm#process()
     */
    public function process()
    {
        $result = $this->processGroup($this->groupDto);

        if ( $result )
        {
            $event = new OW_Event(GROUPS_BOL_Service::EVENT_EDIT, array('groupId' => $this->groupDto->id));
            OW::getEventManager()->trigger($event);
        }

        return $result;
    }
}

class GROUPS_ImageValidator extends OW_Validator
{

    public function __construct()
    {

    }

    /**
     * @see OW_Validator::isValid()
     *
     * @param mixed $value
     */
    public function isValid( $value )
    {
        if ( empty($value) )
        {
            return true;
        }

        $realName = $value['name'];
        $tmpName = $value['tmp_name'];

        switch ( false )
        {
            case is_uploaded_file($tmpName):
                $this->setErrorMessage(OW::getLanguage()->text('groups', 'errors_image_upload'));
                return false;

            case UTIL_File::validateImage($realName):
                $this->setErrorMessage(OW::getLanguage()->text('groups', 'errors_image_invalid'));
                return false;
        }

        return true;
    }
}

class GROUPS_Image extends FileField
{

    public function getValue()
    {
        return empty($_FILES[$this->getName()]['tmp_name']) ? null : $_FILES[$this->getName()];
    }
}

class GROUPS_UniqueValidator extends OW_Validator
{
    private $exception;

    public function __construct( $exception = null )
    {
        $this->setErrorMessage(OW::getLanguage()->text('groups', 'group_already_exists'));

        $this->exception = $exception;
    }

    public function isValid( $value )
    {
        if ( !empty($this->exception) && trim($this->exception) == trim($value) )
        {
            return true;
        }

        $dto = GROUPS_BOL_Service::getInstance()->findByTitle($value);

        if ( $dto === null )
        {
            return true;
        }

        return false;
    }
}
