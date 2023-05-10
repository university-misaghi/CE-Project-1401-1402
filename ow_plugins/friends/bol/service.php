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
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_plugins.friends.bol
 * @since 1.0
 */
class FRIENDS_BOL_Service
{
    const STATUS_ACTIVE = FRIENDS_BOL_FriendshipDao::VAL_STATUS_ACTIVE;
    const STATUS_PENDING = FRIENDS_BOL_FriendshipDao::VAL_STATUS_PENDING;
    const STATUS_IGNORED = FRIENDS_BOL_FriendshipDao::VAL_STATUS_IGNORED;

    /**
     * @var FRIENDS_BOL_FriendshipDao
     */
    private $friendshipDao;
    /**
     * Class instance
     *
     * @var FRIENDS_BOL_Service
     */
    private static $classInstance;

    /**
     * Class constructor
     *
     */
    protected function __construct()
    {
        $this->friendshipDao = FRIENDS_BOL_FriendshipDao::getInstance();
    }

    /**
     * Returns class instance
     *
     * @return FRIENDS_BOL_Service
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function saveFriendship(FRIENDS_BOL_Friendship $friendship)
    {
        $this->friendshipDao->save($friendship);

        return $friendship;
    }

    public function getUnreadFriendRequestsForUserIdList($userIdList)
    {
        if ( empty($userIdList) )
        {
            return array();
        }

        return $this->friendshipDao->findUnreadFriendRequestsForUserIdList($userIdList);
    }

    /**
     * Save new friendship request
     *
     * @param integer $requesterId
     * @param integer $userId
     */
    public function request( $requesterId, $userId )
    {
        $this->friendshipDao->request($requesterId, $userId);
    }

    public function onRequest( $requesterId, $userId )
    {
        $event = new OW_Event('friends.request-sent', array(
            'senderId' => $requesterId,
            'recipientId' => $userId,
            'time' => time()
        ));

        OW::getEventManager()->trigger($event);
    }

    /**
     * Accept new friendship request
     *
     * @param integer $userId
     * @param integer $requesterId
     * @return FRIENDS_BOL_Friendship
     */
    public function accept( $userId, $requesterId )
    {
        return $this->friendshipDao->accept($userId, $requesterId);
    }

    public function onAccept( $userId, $requesterId, FRIENDS_BOL_Friendship $frendshipDto )
    {
        $se = BOL_UserService::getInstance();

        $names = $se->getDisplayNamesForList(array($requesterId, $userId));
        $uUrls = $se->getUserUrlsForList(array($requesterId, $userId));
        
        //Add Newsfeed activity action
        $event = new OW_Event('feed.action', array(
            'pluginKey' => 'friends',
            'entityType' => 'friend_add',
            'entityId' => $frendshipDto->id,
            'userId' => array($requesterId, $userId),
            'feedType' => 'user',
            'feedId' => $requesterId
        ), array(
            'string' => array("key" => 'friends+newsfeed_action_string', "vars" => array(
                'user_url' => $uUrls[$userId],
                'name' => $names[$userId],
                'requester_url' => $uUrls[$requesterId],
                'requester_name' => $names[$requesterId]
            ))
        ));
        OW::getEventManager()->trigger($event);

        //Send notification about accept of friendship request
        $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId));
        $avatar = $avatars[$userId];

        $notificationParams = array(
            'pluginKey' => 'friends',
            'action' => 'friends-accept',
            'entityType' => 'friends-accept',
            'entityId' => $frendshipDto->id,
            'userId' => $requesterId,
            'time' => time()
        );

        $receiver = '<a href="'.$uUrls[$userId].'" target="_blank" >'.$names[$userId].'</a>';

        $notificationData = array(
            'string' => array(
                'key' => 'friends+notify_accept',
                'vars' => array(
                    'receiver' => $receiver
                )
            ),
            'avatar' => $avatar,
            'url' => $uUrls[$userId]
        );

        $event = new OW_Event('notifications.add', $notificationParams, $notificationData);
        OW::getEventManager()->trigger($event);

        $event = new OW_Event('friends.request-accepted', array(
            'senderId' => $requesterId,
            'recipientId' => OW::getUser()->getId(),
            'time' => time()
        ));

        OW::getEventManager()->trigger($event);
    }

    /**
     * Ignore new friendship request
     *
     * @param integer $requesterId
     * @param integer $userId
     */
    public function ignore( $requesterId, $userId )
    {
        $this->friendshipDao->ignore($requesterId, $userId);
    }

    /**
     * Cancel friendship
     *
     * @param integer $requesterId
     * @param integer $userId
     */
    public function cancel( $requesterId, $userId )
    {
        $this->friendshipDao->cancel($requesterId, $userId);
    }
    
    /**
     * getDto friendship
     *
     * @param integer $requesterId
     * @param integer $userId
     */
    public function findByRequesterIdAndUserId( $requesterId, $userId )
    {
        return $this->friendshipDao->findByRequesterIdAndUserId($requesterId, $userId);
    }

    /**
     * Activate friendship
     *
     * @param integer $requesterId
     * @param integer $userId
     */
    public function activate( $requesterId, $userId )
    {
        $this->friendshipDao->activate($requesterId, $userId);
    }

    public function findFriendship( $userId, $user2Id )
    {
        return $this->friendshipDao->findFriendship($userId, $user2Id);
    }

    public function findFriendshipById( $friendshipId )
    {
        return $this->friendshipDao->findFriendshipById($friendshipId);
    }

    public function findFriendIdList( $userId, $first, $count, $type = 'friends' )
    {

        switch ( $type )
        {
            case 'friends':
                return $this->friendshipDao->findFriendIdList($userId, $first, $count);


            case 'sent-requests':
                return $this->friendshipDao->findRequestedUserIdList($userId, $first, $count);

            case 'got-requests':

                return $this->friendshipDao->findRequesterUserIdList($userId, $first, $count);
        }

        return array(array(), 0);
    }

    public function count( $userId = null, $friendId = null, $status = FRIENDS_BOL_Service::STATUS_ACTIVE, $orStatus = null, $viewed = null, $exclude = null )
    {
        return $this->friendshipDao->count($userId, $friendId, $status, $orStatus, $viewed, $exclude);
    }

    public function countFriends( $userId )
    {
        return $this->friendshipDao->findUserFriendsCount($userId);
    }

    public function deleteUserFriendships( $userId )
    {
        $this->friendshipDao->deleteUserFriendships($userId);
    }

    public function findAllActiveFriendships()
    {
        return $this->friendshipDao->findAllActiveFriendships();
    }

    public function findActiveFriendships( $first, $count )
    {
        return $this->friendshipDao->findActiveFriendships($first, $count);
    }
    /* -------------------- */

    public function findUserFriendsInList( $userId, $first, $count, $userIdList = null )
    {
        return $this->friendshipDao->findFriendIdList($userId, $first, $count, $userIdList);
    }

    public function findCountOfUserFriendsInList( $userId, $userIdList = null )
    {
        return $this->friendshipDao->findUserFriendsCount($userId, $userIdList);
    }

    public function findFriendshipListByUserId( $userId, $userIdList = array() )
    {
        return $this->friendshipDao->findFriendshipListByUserId($userId, $userIdList);
    }

    public function findRequestList( $userId, $beforeStamp, $offset, $count, $exclude = null )
    {
        return $this->friendshipDao->findRequestList($userId, $beforeStamp, $offset, $count, $exclude);
    }

    public function findNewRequestList( $userId, $afterStamp )
    {
        return $this->friendshipDao->findNewRequestList($userId, $afterStamp);
    }

    public function markViewedByIds( $idList, $viewed = true )
    {
        $this->friendshipDao->markViewedByIds($idList, $viewed);
    }

    public function markAllViewedByUserId( $userId, $viewed = true )
    {
        $this->friendshipDao->markAllViewedByUserId($userId, $viewed);
    }

    public function findFriendIdListByDisplayName( $userId, $search, $first, $count, $userIdList )
    {
        return $this->friendshipDao->findFriendIdListByDisplayName($userId, $search, $first, $count, $userIdList);
    }
}