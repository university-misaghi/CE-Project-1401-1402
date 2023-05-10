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
$plugin = OW::getPluginManager()->getPlugin('event');
$router = OW::getRouter();
$router->addRoute(new OW_Route('event.add', 'event/add', 'EVENT_CTRL_Base', 'add'));
$router->addRoute(new OW_Route('event.edit', 'event/edit/:eventId', 'EVENT_CTRL_Base', 'edit'));
$router->addRoute(new OW_Route('event.delete', 'event/delete/:eventId', 'EVENT_CTRL_Base', 'delete'));
$router->addRoute(new OW_Route('event.view', 'event/:eventId', 'EVENT_CTRL_Base', 'view'));
$router->addRoute(new OW_Route('event.main_menu_route', 'events', 'EVENT_CTRL_Base', 'eventsList', array('list' => array(OW_Route::PARAM_OPTION_HIDDEN_VAR => 'latest'))));
$router->addRoute(new OW_Route('event.view_event_list', 'events/:list', 'EVENT_CTRL_Base', 'eventsList'));
$router->addRoute(new OW_Route('event.main_user_list', 'event/:eventId/users', 'EVENT_CTRL_Base', 'eventUserLists', array('list' => array(OW_Route::PARAM_OPTION_HIDDEN_VAR => 'yes'))));
$router->addRoute(new OW_Route('event.user_list', 'event/:eventId/users/:list', 'EVENT_CTRL_Base', 'eventUserLists'));
$router->addRoute(new OW_Route('event.private_event', 'event/:eventId/private', 'EVENT_CTRL_Base', 'privateEvent'));
$router->addRoute(new OW_Route('event.invite_accept', 'event/:eventId/:list/invite_accept', 'EVENT_CTRL_Base', 'inviteListAccept'));
$router->addRoute(new OW_Route('event.invite_decline', 'event/:eventId/:list/invite_decline', 'EVENT_CTRL_Base', 'inviteListDecline'));
$router->addRoute(new OW_Route('event.approve', 'event/approve/:eventId/', 'EVENT_CTRL_Base', 'approve'));


$provider = EVENT_CLASS_ContentProvider::getInstance();
$provider->init();

$eventHandler = new EVENT_CLASS_EventHandler();
$eventHandler->genericInit();
$eventHandler->init();
