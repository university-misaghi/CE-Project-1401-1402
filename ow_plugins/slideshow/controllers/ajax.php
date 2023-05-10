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
 * @package ow.plugin.slideshow.controllers
 * @since 1.4.0
 */
 
class SLIDESHOW_CTRL_Ajax extends OW_ActionController
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function addSlide()
    {
    	if ( !OW::getRequest()->isAjax() )
    	{
            exit(json_encode(array('result' => false)));
    	}
    	
        if ( !OW::getUser()->isAdmin() )
        {
            throw new AuthenticationException();
            exit();
        }
    	
    	if ( !empty($_POST['uniqName']) )
    	{
            $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : null;
            $url = !empty($_POST['url']) ? htmlspecialchars($_POST['url']) : null;
            
            $service = SLIDESHOW_BOL_Service::getInstance();
            $slideId = (int) $_POST['slideId'];
            $service->addSlide($slideId, $title, $url);
            
            exit(json_encode(array('result' => true, 'slideId' => $slideId)));
    	}
        
        exit;
    }
    
    public function editSlide()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            exit(json_encode(array('result' => false)));
        }
        
        if ( !OW::getUser()->isAdmin() )
        {
            throw new AuthenticationException();
            exit();
        }
        
        if ( !empty($_POST['slideId']) )
        {
            $title = !empty($_POST['title']) ? htmlspecialchars($_POST['title']) : null;
            $url = !empty($_POST['url']) ? htmlspecialchars($_POST['url']) : null;
            
            $service = SLIDESHOW_BOL_Service::getInstance();
            $slideId = (int) $_POST['slideId'];
            $slide = $service->findSlideById($slideId);
            
            if ( !$slide )
            {
                exit(json_encode(array('result' => false)));
            }
            
            $slide->label = $title;
            $slide->url = $url;
            
            $service->updateSlide($slide);
            
            exit(json_encode(array('result' => true, 'slideId' => $slideId)));
        }
        
        exit;
    }
    
    public function redrawList( $params )
    {
        if ( !OW::getUser()->isAdmin() )
        {
            throw new AuthenticationException();
            exit();
        }
        
        $uniqName = $params['uniqName'];
        
        $service = SLIDESHOW_BOL_Service::getInstance();
        
        $slides = $service->getSlideList($uniqName);

        $markup = '';
        if ( $slides )
        {
            foreach ( $slides as $slide )
            {
                $cmp = new SLIDESHOW_CMP_Slide($slide);
                $markup .= $cmp->render();
            }
        }
        
        exit(json_encode(array('markup' => $markup)));
    }
    
    public function deleteSlide( )
    {
    	if ( !OW::getUser()->isAdmin() )
    	{
    		throw new AuthenticationException();
            exit();
    	}
    	
        $slideId = $_POST['slideId'];
        
        $service = SLIDESHOW_BOL_Service::getInstance();
        
        $slide = $service->findSlideById($slideId);
        $service->deleteSlideById($slideId);
                
        $slides = $service->getSlideList($slide->widgetId);

        $markup = '';
        if ( $slides )
        {
            foreach ( $slides as $slide )
            {
                $cmp = new SLIDESHOW_CMP_Slide($slide);
                $markup .= $cmp->render();
            }
        }
        
        exit(json_encode(array('markup' => $markup)));
    }
    
    public function reorderList( $params )
    {
        if ( !OW::getUser()->isAdmin() )
        {
            throw new AuthenticationException();
            exit();
        }
        
        $service = SLIDESHOW_BOL_Service::getInstance();
        
        if ( !empty($_POST['slide-list']) )
        {
            foreach ( $_POST['slide-list'] as $order => $id )
            {
                $slide = $service->findSlideById($id);
                if ( empty($slide) )
                {
                    continue;
                }
                
                $slide->order = $order + 1;
                $service->updateSlide($slide);
            }
        }
        
        exit;
    }
}