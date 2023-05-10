<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2009, Skalfa LLC
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
 * @author Zarif Safiullin <zaph.saph@gmail.com>
 * @package ow_plugins.friends.components
 * @since 1.0
 */
class FRIENDS_CMP_ConsoleFriendRequests extends BASE_CMP_ConsoleDropdownList
{
    public function __construct()
    {
        parent::__construct( OW::getLanguage()->text('friends', 'console_requests_title'), 'friend_requests' );


        $this->addClass('ow_friend_request_list');
    }

    public function initJs()
    {
        parent::initJs();

        $jsUrl = OW::getPluginManager()->getPlugin('friends')->getStaticJsUrl() . 'friend_request.js';
        OW::getDocument()->addScript($jsUrl);

        $js = UTIL_JsGenerator::newInstance();
        $js->addScript('OW.FriendRequest = new OW_FriendRequest({$key}, {$params});', array(
            'key' => $this->getKey(),
            'params' => array(
                'rsp' => OW::getRouter()->urlFor('FRIENDS_CTRL_Action', 'ajax')
            )
        ));

        OW::getDocument()->addOnloadScript($js);
    }
}