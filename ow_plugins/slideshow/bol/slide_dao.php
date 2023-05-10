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
 * Data Access Object for `slideshow_slide` table.
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.slideshow.bol
 * @since 1.4.0
 */
class SLIDESHOW_BOL_SlideDao extends OW_BaseDao
{
    /**
     * Singleton instance.
     *
     * @var SLIDESHOW_BOL_SlideDao
     */
    private static $classInstance;

    /**
     * Constructor.
     */
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns an instance of class.
     *
     * @return SLIDESHOW_BOL_SlideDao
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'SLIDESHOW_BOL_Slide';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'slideshow_slide';
    }
    
    /**
     * Returns active slide list for widget
     * 
     * @param string $uniqueName
     */
    public function findListByUniqueName( $uniqueName )
    {
        $example = new OW_Example();
        $example->andFieldEqual('widgetId', $uniqueName);
        $example->andFieldEqual('status', 'active');
        $example->setOrder('`order` ASC');
        
        return $this->findListByExample($example);
    }
    
    /**
     * Returns all slide list for widget
     * 
     * @param string $uniqueName
     */
    public function findAllByUniqueName( $uniqueName )
    {
        $example = new OW_Example();
        $example->andFieldEqual('widgetId', $uniqueName);

        return $this->findListByExample($example);
    }
    
    /**
     * Returns next slide order number
     * 
     * @param string $uniqName
     */
    public function getNextOrder( $uniqName )
    {
        $example = new OW_Example();
        $example->andFieldEqual('widgetId', $uniqName);
        $example->setOrder('`order` DESC');
        $example->setLimitClause(0, 1);
        
        $last = $this->findObjectByExample($example);
        
        return $last ? $last->order + 1 : 1;
    }
    
    /**
     * Returns all slides marked for removal with limit
     * 
     * @param int $limit
     */
    public function getListForRemoval( $limit )
    {
        $example = new OW_Example();
        $example->andFieldEqual('status', 'delete');
        $example->setOrder('`order` ASC');
        $example->setLimitClause(0, $limit);
        
        return $this->findListByExample($example);
    }
}