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

OW::getRouter()->addRoute(new OW_Route('slideshow.ajax-add-slide', 'slideshow/ajax/add-slide/', 'SLIDESHOW_CTRL_Ajax', 'addSlide'));
OW::getRouter()->addRoute(new OW_Route('slideshow.ajax-edit-slide', 'slideshow/ajax/edit-slide/', 'SLIDESHOW_CTRL_Ajax', 'editSlide'));
OW::getRouter()->addRoute(new OW_Route('slideshow.ajax-redraw-list', 'slideshow/ajax/redraw-list/:uniqName', 'SLIDESHOW_CTRL_Ajax', 'redrawList'));
OW::getRouter()->addRoute(new OW_Route('slideshow.ajax-delete-slide', 'slideshow/ajax/delete-slide/', 'SLIDESHOW_CTRL_Ajax', 'deleteSlide'));
OW::getRouter()->addRoute(new OW_Route('slideshow.ajax-reorder-list', 'slideshow/ajax/reorder-list/', 'SLIDESHOW_CTRL_Ajax', 'reorderList'));
OW::getRouter()->addRoute(new OW_Route('slideshow.upload-file', 'slideshow/upload-file/:uniqName/', 'SLIDESHOW_CTRL_Slide', 'uploadFile'));
OW::getRouter()->addRoute(new OW_Route('slideshow.update-file', 'slideshow/update-file/:slideId/', 'SLIDESHOW_CTRL_Slide', 'updateFile'));
OW::getRouter()->addRoute(new OW_Route('slideshow.uninstall', 'admin/plugins/slideshow/uninstall', 'SLIDESHOW_CTRL_Admin', 'uninstall'));

SLIDESHOW_CLASS_EventHandler::getInstance()->init();