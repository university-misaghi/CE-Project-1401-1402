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

class SLIDESHOW_CTRL_Slide extends OW_ActionController
{
	public function __construct()
	{
		parent::__construct();
	}

	public function uploadFile( $params )
	{
	    $uniqName = isset($params['uniqName']) ? trim($params['uniqName']) : null;
	    
	    $formElementId = 'file_' . $uniqName;
	    $language = OW::getLanguage();
	    
        if ( !OW::getUser()->isAdmin() )
        {
            throw new AuthenticationException();
            exit();
        }
        
	    $result = array('input_id' => $formElementId, 'error' => true, 'message' => '');
	    
	    if ( !OW::getRequest()->isPost() )
	    {
	        $result['message'] = "Not authorized";
	    }
	    else
	    {
            $service = SLIDESHOW_BOL_Service::getInstance();
            
            if ( empty($_FILES['slide']) )
            {
                $result['message'] = "File not selected";
            }
            else 
            {
                $slide = $_FILES['slide'];
    
                if ( is_uploaded_file($slide['tmp_name']) )
                {
                    $iniValue = floatval(ini_get('upload_max_filesize'));
                    $maxSize = 1024 * 1024 * ($iniValue ? $iniValue : 4);
    
                    if ( !UTIL_File::validateImage($slide['name']) )
                    {
                        $result['message'] = $language->text('slideshow', 'upload_file_extension_not_allowed');
                    }
                    else if ( $slide['size'] > $maxSize )
                    {
                        $result['message'] = $language->text('slideshow', 'upload_file_max_filesize_error');
                    }
                    else if ( $slideId = $service->addTmpSlide($uniqName, $slide) )
                    {
                        $result['slide_id'] = $slideId;
                        $result['error'] = false;
                    }
                    else
                    {
                        $result['message'] = $language->text('slideshow', 'upload_file_error'); 
                    }
                }
            }
	    }

        exit("<script>parent.window.OW.trigger('slideshow.upload_file_complete', [" . json_encode($result) . "]);</script>");
    }
    
    public function updateFile( $params )
    {
        if ( !OW::getUser()->isAdmin() )
        {
            throw new AuthenticationException();
            exit();
        }
        
    	$slideId = isset($params['slideId']) ? trim($params['slideId']) : null;
    	$service = SLIDESHOW_BOL_Service::getInstance();
    	
    	$slide = $service->findSlideById($slideId);
    	
    	$result = array('error' => true, 'message' => '');
    	
    	if ( $slide )
    	{
            $formElementId = 'file_' . $slide->widgetId;
            $language = OW::getLanguage();
        
            if ( empty($_FILES['slide']) )
            {
                $result['message'] = "File not selected";
            }
            else 
            {
                $file = $_FILES['slide'];
    
                if ( is_uploaded_file($file['tmp_name']) )
                {
                    $iniValue = floatval(ini_get('upload_max_filesize'));
                    $maxSize = 1024 * 1024 * ($iniValue ? $iniValue : 4);
    
                    if ( !UTIL_File::validateImage($file['name']) )
                    {
                        $result['message'] = $language->text('slideshow', 'upload_file_extension_not_allowed');
                    }
                    else if ( $file['size'] > $maxSize )
                    {
                        $result['message'] = $language->text('slideshow', 'upload_file_max_filesize_error');
                    }
                    else if ( $service->updateSlideImage($slide->id, $file) )
                    {
                        $result['slide_id'] = $slideId;
                        $result['error'] = false;
                        $result['input_id'] = $formElementId;
                    }
                    else
                    {
                        $result['message'] = $language->text('slideshow', 'upload_file_error'); 
                    }
                }
            }
        }

        exit("<script>parent.window.OW.trigger('slideshow.upload_file_complete', [" . json_encode($result) . "]);</script>");
    }
}