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

$config = OW::getConfig();

if ( !$config->configExists('slideshow', 'uninstall_inprogress') )
{
    $config->addConfig('slideshow', 'uninstall_inprogress', 0, 'Plugin is being uninstalled');
}

if ( !$config->configExists('slideshow', 'uninstall_cron_busy') )
{
    $config->addConfig('slideshow', 'uninstall_cron_busy', 0, 'Uninstall queue is busy');
}

$sql = "CREATE TABLE IF NOT EXISTS `".OW_DB_PREFIX."slideshow_slide` (
  `id` int(11) NOT NULL auto_increment,
  `widgetId` varchar(255) NOT NULL,
  `label` varchar(255) default NULL,
  `url` varchar(255) default NULL,
  `order` int(11) default '0',
  `width` int(11) NOT NULL default '0',
  `height` int(11) NOT NULL default '0',
  `ext` varchar(5) default NULL,
  `addStamp` int(11) default '0',
  `status` ENUM( 'active', 'delete' ) NULL DEFAULT 'active',
  PRIMARY KEY  (`id`),
  KEY `order` (`order`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

OW::getDbo()->query($sql);

OW::getPluginManager()->addUninstallRouteName('slideshow', 'slideshow.uninstall');

$path = OW::getPluginManager()->getPlugin('slideshow')->getRootDir() . 'langs.zip';
OW::getLanguage()->importPluginLangs($path, 'slideshow');
