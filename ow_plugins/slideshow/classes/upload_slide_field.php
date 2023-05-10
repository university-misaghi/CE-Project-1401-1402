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

/* 
 * Ajax Upload Slide Field Class
 * 
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.slideshow.classes
 * @since 1.4.0
 */
class SLIDESHOW_CLASS_UploadSlideField extends FormElement
{
	private $uniqName;
	
	private $slideId; 
	
    /**
     * Constructor.
     *
     * @param string $name
     */
    public function __construct( $name, $uniqName, $slideId = null )
    {
        parent::__construct($name);

        $this->uniqName = $uniqName;
        
        $this->slideId = $slideId;
    }

    /**
     * @see FormElement::renderInput()
     *
     * @param array $params
     * @return string
     */
    public function renderInput( $params = null )
    {
        parent::renderInput($params);

        $elementId = 'file_' . $this->uniqName;
        
        $router = OW::getRouter();

        $respUrl = $this->slideId ? 
            $router->urlForRoute('slideshow.update-file', array('slideId' => $this->slideId)) :
            $router->urlForRoute('slideshow.upload-file', array('uniqName' => $this->uniqName));
        
        $params = array('elementId' => $elementId, 'fileResponderUrl' => $respUrl);

        $script = "window.uploadSlideFields = {};
        	window.uploadSlideFields['" . $this->uniqName . "'] = new uploadSlideField(" . json_encode($params) . ");
			window.uploadSlideFields['" . $this->uniqName . "'].init();";

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin("slideshow")->getStaticJsUrl() . 'upload_slide_field.js');
        OW::getDocument()->addOnloadScript($script);

        $fileAttr = array('type' => 'file', 'id' => $elementId);
        $fileField = UTIL_HtmlTag::generateTag('input', $fileAttr);

        $hiddenAttr = array('type' => 'hidden', 'name' => $this->getName(), 'id' => 'hidden_' . $this->uniqName);
        $hiddenField = UTIL_HtmlTag::generateTag('input', $hiddenAttr);

        return '<span class="'. $elementId .'_cont">' . $fileField . '</span>' . $hiddenField;
    }
}