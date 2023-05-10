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
 * Slideshow add slide component
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.slideshow.components
 * @since 1.4.0
 */
class SLIDESHOW_CMP_AddSlide extends OW_Component
{
    public function __construct( $uniqName )
    {
        parent::__construct();
        
        $form = new SLIDESHOW_CLASS_AddSlideForm($uniqName);
        $this->addForm($form);
        
        $script = '$("#btn-add-slide").click(function(){
        	var file = $("#file_'.$uniqName.'");
        	
        	if ( file.val() != "" ) {
        		OW.inProgressNode($(this));
            	window.uploadSlideFields["'.$uniqName.'"].startUpload();
    		}
        });
        
        document.addSlideFloatbox.bind("close", function(){
            OW.unbind("slideshow.upload_file");
            OW.unbind("slideshow.upload_file_complete");
        });
        
        window.owForms["add-slide-form"].bind("success", function(data){
            $.ajax({
                type: "post",
                url: '.json_encode(OW::getRouter()->urlForRoute('slideshow.ajax-redraw-list', array('uniqName' => $uniqName))).',
                data: {},
                dataType: "json",
                success: function(data){
                    markup = data.markup;
                    document.addSlideFloatbox.close();
                    $("#slides-tbl tbody").html(data.markup);
                }
            });
        });';
        
        OW::getDocument()->addOnloadScript($script);
    }
}