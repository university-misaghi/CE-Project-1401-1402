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
 * Send gift component
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.virtual_gifts.components
 * @since 1.0
 */
class VIRTUALGIFTS_CMP_SendGift extends OW_Component
{
    public function __construct( $recipientId )
    {
        parent::__construct();

        $userId = OW::getUser()->getId();
        $giftService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
        $authService = BOL_AuthorizationService::getInstance();

        if ( !OW::getUser()->isAuthorized('virtualgifts', 'send_gift') )
        {
            $status = $authService->getActionStatus('virtualgifts', 'send_gift');
            $this->assign('authMessage', $status['msg']);
            return;
        }
        
        $form = new SendGiftForm($recipientId);
        $this->addForm($form);
                
        $categoriesSetup = $giftService->categoriesSetup();
        
        if ( $categoriesSetup )
        {
            $templates = $giftService->getTemplateListByCategories();
            if ( !$templates )
            {
                $templates = $giftService->getTemplateList();
                $categoriesSetup = false;
            }
        }
        else 
        {
            $templates = $giftService->getTemplateList();
        }
        
        $this->assign('catSetup', $categoriesSetup);
        
        if ( $categoriesSetup )
        {
            $menuItems = array();
            $order = 1;
            foreach ( $templates as $id => $tpl )
            {
                $item = new BASE_MenuItem();
                $item->setLabel($tpl['title']);
                $item->setUrl("js-call:$id");
                $item->setKey($id);
                $item->setIconClass('ow_ic_heart');
                $item->setOrder($order);
                $item->setActive($order == 1);
                
                array_push($menuItems, $item);
                $order++;
            }
            
            $menu = new BASE_CMP_ContentMenu($menuItems);
            $this->addComponent('menu', $menu);
        }
        
        $this->assign('tpls', $templates);

        $showPrice = OW::getPluginManager()->isPluginActive('usercredits')
            && !$authService->isActionAuthorizedForUser($userId, 'virtualgifts', 'send_gift');

        $this->assign('showPrice', $showPrice);
        $this->assign('balance', OW::getEventManager()->call('usercredits.get_balance'));
        
        $url = OW::getPluginManager()->getPlugin('virtualgifts')->getStaticCssUrl() . 'style.css';
        OW::getDocument()->addStyleSheet($url);
    }
    
    public static function process( $data )
    {
        $resp = array();
        $lang = OW::getLanguage();
        $giftService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
        $authService = BOL_AuthorizationService::getInstance();
        
        if ( !OW::getUser()->isAuthenticated() )
        {
            $resp['error'] = $lang->text('virtualgifts', 'not_authenticated');
            echo json_encode($resp);
            exit;
        }

        if ( empty($data['tplId']) || !$tpl = $giftService->findTemplateById($data['tplId']) )
        {
            $resp['error'] = $lang->text('virtualgifts', 'gift_not_selected');
            echo json_encode($resp);
            exit;
        }
        
        $tplId = (int) $data['tplId'];
        $senderId = OW::getUser()->getId();
        $recipientId = (int) $data['recipientId'];
        
        if ( !OW::getUser()->isAuthorized('virtualgifts', 'send_gift', array('tplId' => $tplId)) )
        {
            $status = $authService->getActionStatus(
                'virtualgifts', 'send_gift', array('tplId' => $tplId)
            );
            $resp['error'] = $status['msg'];
            echo json_encode($resp);
            exit;
        }
        
        $gift = new VIRTUALGIFTS_BOL_UserGift();
        $gift->senderId = $senderId;
        $gift->recipientId = $recipientId;
        $gift->private = $data['isPrivate'] == 'on' ? 1 : 0;
        $gift->templateId = $tplId;
        if ( !empty($data['message']) )
        {
            $gift->message = strip_tags(trim($data['message']));
        }
        $gift->sendTimestamp = time();
        
        if ( $giftId = $giftService->sendUserGift($gift) )
        {
            $authService->trackAction('virtualgifts', 'send_gift', array('tplId' => $tplId));

            if ( !$gift->private )
            {
                // Newsfeed
                $event = new OW_Event('feed.action', array(
                    'pluginKey' => 'virtualgifts',
                    'entityType' => 'user_gift',
                    'entityId' => $giftId,
                    'userId' => $recipientId
                ));
            
                OW::getEventManager()->trigger($event);
            }
            
            $event = new OW_Event('virtualgifts.send_gift', array(
                'recipientId' => $recipientId,
                'senderId' => $senderId,
                'giftId' => $giftId
            ));
            OW::getEventManager()->trigger($event);
            
            $resp['message'] = $lang->text('virtualgifts', 'gift_sent');
            echo json_encode($resp);
        }
        else
        {
            $resp['error'] = $lang->text('virtualgifts', 'gift_not_sent');
            echo json_encode($resp);            
        }
        exit;
    }
}

class SendGiftForm extends Form 
{
    public function __construct( $recipientId )
    {
        parent::__construct('send-gift-form');
        
        $this->setAjax(true);
        $this->setAction(OW::getRouter()->urlFor('VIRTUALGIFTS_CTRL_Gifts', 'ajaxSendGift'));
        
        $lang = OW::getLanguage();
        
        $tplId = new HiddenField('tplId');
        $v = new RequiredValidator();
        $v->setErrorMessage($lang->text('virtualgifts', 'gift_not_selected'));
        $tplId->addValidator($v);
        $this->addElement($tplId);
        
        $message = new Textarea('message');
        $this->addElement($message);
        
        $recipient = new HiddenField('recipientId');
        $recipient->setValue($recipientId);
        $this->addElement($recipient);
        
        $user = BOL_UserService::getInstance()->getUserName($recipientId);
        $isPrivate = new CheckboxField('isPrivate');
        $isPrivate->setLabel($lang->text('virtualgifts', 'send_private_gift', array('user' => $user)));
        $this->addElement($isPrivate);
        
        $submit = new Submit('send');
        $submit->setValue($lang->text('virtualgifts', 'btn_send'));
        $this->addElement($submit);
        
        $js = 'owForms["'.$this->getName().'"].bind("success", function(data){
            if ( data.error != undefined ){
                OW.error(data.error);
            }
            if ( data.message != undefined ){
                OW.info(data.message);
            }
            sendGiftFloatBox.close()
        });';
        
        OW::getDocument()->addOnloadScript($js);
    }
}