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
$plugin = OW::getPluginManager()->getPlugin('groups');

//Admin Routs
OW::getRouter()->addRoute(new OW_Route('groups-admin-widget-panel', 'admin/plugins/groups', 'GROUPS_CTRL_Admin', 'panel'));
OW::getRouter()->addRoute(new OW_Route('groups-admin-additional-features', 'admin/plugins/groups/additional', 'GROUPS_CTRL_Admin', 'additional'));
OW::getRouter()->addRoute(new OW_Route('groups-admin-uninstall', 'admin/plugins/groups/uninstall', 'GROUPS_CTRL_Admin', 'uninstall'));

//Frontend Routs
OW::getRouter()->addRoute(new OW_Route('groups-create', 'groups/create', 'GROUPS_CTRL_Groups', 'create'));
OW::getRouter()->addRoute(new OW_Route('groups-edit', 'groups/:groupId/edit', 'GROUPS_CTRL_Groups', 'edit'));
OW::getRouter()->addRoute(new OW_Route('groups-view', 'groups/:groupId', 'GROUPS_CTRL_Groups', 'view'));
OW::getRouter()->addRoute(new OW_Route('groups-join', 'groups/:groupId/join', 'GROUPS_CTRL_Groups', 'join'));
OW::getRouter()->addRoute(new OW_Route('groups-customize', 'groups/:groupId/customize', 'GROUPS_CTRL_Groups', 'customize'));
OW::getRouter()->addRoute(new OW_Route('groups-most-popular', 'groups/most-popular', 'GROUPS_CTRL_Groups', 'mostPopularList'));
OW::getRouter()->addRoute(new OW_Route('groups-latest', 'groups/latest', 'GROUPS_CTRL_Groups', 'latestList'));
OW::getRouter()->addRoute(new OW_Route('groups-invite-list', 'groups/invitations', 'GROUPS_CTRL_Groups', 'inviteList'));
OW::getRouter()->addRoute(new OW_Route('groups-my-list', 'groups/my', 'GROUPS_CTRL_Groups', 'myGroupList'));

OW::getRouter()->addRoute(new OW_Route('groups-index', 'groups', 'GROUPS_CTRL_Groups', 'index'));
OW::getRouter()->addRoute(new OW_Route('groups-user-groups', 'users/:user/groups', 'GROUPS_CTRL_Groups', 'userGroupList'));
OW::getRouter()->addRoute(new OW_Route('groups-leave', 'groups/:groupId/leave', 'GROUPS_CTRL_Groups', 'leave'));

OW::getRouter()->addRoute(new OW_Route('groups-user-list', 'groups/:groupId/users', 'GROUPS_CTRL_Groups', 'userList'));
OW::getRouter()->addRoute(new OW_Route('groups-private-group', 'groups/:groupId/private', 'GROUPS_CTRL_Groups', 'privateGroup'));

OW::getRegistry()->addToArray(BASE_CMP_AddNewContent::REGISTRY_DATA_KEY,
    array(
        BASE_CMP_AddNewContent::DATA_KEY_ICON_CLASS => 'ow_ic_comment',
        BASE_CMP_AddNewContent::DATA_KEY_URL => OW::getRouter()->urlForRoute('groups-create'),
        BASE_CMP_AddNewContent::DATA_KEY_LABEL => OW::getLanguage()->text('groups', 'add_new_label')
));

$eventHandler = GROUPS_CLASS_EventHandler::getInstance();
$eventHandler->genericInit();

OW::getEventManager()->bind('forum.activate_plugin', array($eventHandler, "onForumActivate"));
OW::getEventManager()->bind('forum.find_forum_caption', array($eventHandler, "onForumFindCaption"));
OW::getEventManager()->bind('forum.uninstall_plugin', array($eventHandler, "onForumUninstall"));
OW::getEventManager()->bind('forum.collect_widget_places', array($eventHandler, "onForumCollectWidgetPlaces"));

OW::getEventManager()->bind('feed.collect_widgets', array($eventHandler, "onFeedCollectWidgets"));
OW::getEventManager()->bind('feed.on_widget_construct', array($eventHandler, "onFeedWidgetConstruct"));
OW::getEventManager()->bind('feed.on_item_render', array($eventHandler, "onFeedItemRender"));

OW::getEventManager()->bind('admin.add_admin_notification', array($eventHandler, "onCollectAdminNotifications"));
OW::getEventManager()->bind(BASE_CMP_QuickLinksWidget::EVENT_NAME, array($eventHandler, 'onCollectQuickLinks'));
OW::getEventManager()->bind("base.collect_seo_meta_data", array($eventHandler, 'onCollectMetaData'));

GROUPS_CLASS_ConsoleBridge::getInstance()->init();
GROUPS_CLASS_ContentProvider::getInstance()->init();