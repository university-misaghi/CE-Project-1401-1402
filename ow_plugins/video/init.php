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
$plugin = OW::getPluginManager()->getPlugin('video');

$classesToAutoload = array(
    'VideoProviders' => $plugin->getRootDir() . 'classes' . DS . 'video_providers.php'
);

OW::getAutoloader()->addClassArray($classesToAutoload);

OW::getRouter()->addRoute(
    new OW_Route(
        'video_view_list',
        'video/viewlist/:listType/',
        'VIDEO_CTRL_Video',
        'viewList',
        array('listType' => array('default' => 'latest'))
    )
);

OW::getRouter()->addRoute(new OW_Route('video_list_index', 'video/', 'VIDEO_CTRL_Video', 'viewList'));
OW::getRouter()->addRoute(new OW_Route('view_clip', 'video/view/:id/', 'VIDEO_CTRL_Video', 'view'));
OW::getRouter()->addRoute(new OW_Route('edit_clip', 'video/edit/:id/', 'VIDEO_CTRL_Video', 'edit'));
OW::getRouter()->addRoute(new OW_Route('view_list', 'video/viewlist/:listType/', 'VIDEO_CTRL_Video', 'viewList'));
OW::getRouter()->addRoute(new OW_Route('view_tagged_list_st', 'video/viewlist/tagged/', 'VIDEO_CTRL_Video', 'viewTaggedList'));
OW::getRouter()->addRoute(new OW_Route('view_tagged_list', 'video/viewlist/tagged/:tag', 'VIDEO_CTRL_Video', 'viewTaggedList'));
OW::getRouter()->addRoute(new OW_Route('video_user_video_list', 'video/user-video/:user', 'VIDEO_CTRL_Video', 'viewUserVideoList'));
OW::getRouter()->addRoute(new OW_Route('video_admin_config', 'admin/video/', 'VIDEO_CTRL_Admin', 'index'));

OW::getThemeManager()->addDecorator('video_list_item', $plugin->getKey());

VIDEO_CLASS_EventHandler::getInstance()->init();
VIDEO_CLASS_ContentProvider::getInstance()->init();