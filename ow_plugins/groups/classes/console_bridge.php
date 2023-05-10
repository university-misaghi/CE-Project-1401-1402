<?php

class GROUPS_CLASS_ConsoleBridge
{
    const INVITATION_JOIN = 'group-join';

    /**
     * Singleton instance.
     *
     * @var GROUPS_CLASS_ConsoleBridge
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return GROUPS_CLASS_ConsoleBridge
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }



    private function __construct()
    {

    }

    public function onInvite( OW_Event $event )
    {
        $params = $event->getParams();

        $group = GROUPS_BOL_Service::getInstance()->findGroupById($params['groupId']);
        $groupUrl = OW::getRouter()->urlForRoute('groups-view', array('groupId' => $group->id));
        $groupTitle = UTIL_String::truncate($group->title, 100, '...');
        $groupImage = GROUPS_BOL_Service::getInstance()->getGroupImageUrl($group);

        $userId = OW::getUser()->getId();
        $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId));
        $avatar = $avatars[$userId];

        $invitationEvent = new OW_Event('invitations.add', array(
            'pluginKey' => 'groups',
            'entityType' => self::INVITATION_JOIN,
            'entityId' => $group->id,
            'userId' => $params['userId'],
            'time' => time(),
            'action' => 'groups-invitation'
        ), array(
            'string' => array(
                'key' => 'groups+invitation_join_string_1',
                'vars' => array(
                    'group' => '<a href="' . $groupUrl . '">' . $groupTitle . '</a>',
                    'user1' => '<a href="' . $avatar['url'] . '">' . $avatar['title'] . '</a>'
                )
            ),
            'users' => array($userId),
            'avatar' => $avatar,
            'contentImage' => array(
                'src' => $groupImage,
                'url' => $groupUrl
            )
        ));

        OW::getEventManager()->trigger($invitationEvent);
    }

    public function onInviteDublicate( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['entityType'] != self::INVITATION_JOIN )
        {
            return;
        }

        $data = $event->getData();
        $oldData = $params['oldData'];

        $data['users'] = array_merge($oldData['users'], $data['users']);
        $userCount = count($data['users']);

        if ( $userCount > 1 )
        {
            $data['string']['key'] = 'groups+invitation_join_string_2';
            $data['string']['vars']['user2'] = $oldData['string']['vars']['user1'];
        }

        if ( $userCount > 2 )
        {
            $data['string']['key'] = 'groups+invitation_join_string_many';
        }

        $event->setData($data);
    }

    public function onInviteRender( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['entityType'] != self::INVITATION_JOIN )
        {
            return;
        }

        $data = $params['data'];
        $users = $data['users'];

        if ( count($users) > 2 )
        {
            $otherUsers = array_slice($data['users'], 2);
            $data['string']['vars']['otherUsers'] = '<a href="javascript://" onclick="OW.showUsers(' . json_encode($otherUsers) . ');">' .
                OW::getLanguage()->text('groups', 'invitation_join_string_other_users', array( 'count' => count($otherUsers) )) . '</a>';
        }

        $groupId = (int) $params['entityId'];
        $itemKey = $params['key'];

        $data['toolbar'] = array(
            array(
                'label' => OW::getLanguage()->text('groups', 'invite_accept_label'),
                'id'=> 'toolbar_accept_' . $itemKey
            ),
            array(
                'label' => OW::getLanguage()->text('groups', 'invite_decline_label'),
                'id'=> 'toolbar_ignore_' . $itemKey
            )
        );

        $event->setData($data);

        $jsData = array(
            'groupId' => $groupId,
            'itemKey' => $itemKey
        );

        $js = UTIL_JsGenerator::newInstance();
        $js->jQueryEvent("#toolbar_ignore_$itemKey", 'click',
                'OW.Invitation.send("groups.ignore", e.data.groupId).removeItem(e.data.itemKey);',
        array('e'), $jsData);

        $js->jQueryEvent("#toolbar_accept_$itemKey", 'click',
                'OW.Invitation.send("groups.accept", e.data.groupId);
                 $("#toolbar_ignore_" + e.data.itemKey).hide();
                 $("#toolbar_accept_" + e.data.itemKey).hide();',
        array('e'), $jsData);

        OW::getDocument()->addOnloadScript($js->generateJs());
    }

    public function onItemSend( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['entityType'] != self::INVITATION_JOIN )
        {
            return;
        }

        $data = $params['data'];
        $users = $data['users'];

        if ( count($users) > 2 )
        {
            $otherUsers = array_slice($data['users'], 2);
            $data['string']['vars']['otherUsers'] = OW::getLanguage()->text('groups', 'invitation_join_string_other_users', array( 'count' => count($otherUsers) ));
        }

        $event->setData($data);
    }


    //Notifications

    public function onCollectNotificationActions( BASE_CLASS_EventCollector $e )
    {
        $sectionLabel = OW::getLanguage()->text('groups', 'email_notification_section_label');

        $e->add(array(
            'section' => 'groups',
            'sectionIcon' => 'ow_ic_files',
            'sectionLabel' => $sectionLabel,
            'action' => 'groups-add_comment',
            'description' => OW::getLanguage()->text('groups', 'email_notification_comment_setting'),
            'selected' => true
        ));

        $e->add(array(
            'section' => 'groups',
            'action' => 'groups-invitation',
            'sectionIcon' => 'ow_ic_files',
            'sectionLabel' => $sectionLabel,
            'description' => OW::getLanguage()->text('groups', 'notifications_new_message'),
            'selected' => true
        ));
    }


    public function onComment( OW_Event $e )
    {
        $params = $e->getParams();

        if ( empty($params['entityType']) || $params['entityType'] != GROUPS_BOL_Service::ENTITY_TYPE_WAL )
        {
            return;
        }

        $group = GROUPS_BOL_Service::getInstance()->findGroupById($params['entityId']);
        $groupUrl = OW::getRouter()->urlForRoute('groups-view', array('groupId' => $group->id));
        $groupTitle = UTIL_String::truncate($group->title, 100, '...');
        $groupImage = GROUPS_BOL_Service::getInstance()->getGroupImageUrl($group);
        $comment = BOL_CommentService::getInstance()->findComment($params['commentId']);

        $userId = OW::getUser()->getId();
        $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId));
        $avatar = $avatars[$userId];

        $notificationParams = array(
            'pluginKey' => 'groups',
            'action' => 'groups-add_comment',
            'entityType' => $params['entityType'],
            'entityId' => $params['entityId'],
            'userId' => null,
            'time' => time()
        );

        $notificationData = array(
            'string' => array(
                'key' => 'groups+email_notification_comment',
                'vars' => array(
                    'userName' => $avatar['title'],
                    'userUrl' => $avatar['url'],
                    'url' => $groupUrl,
                    'title' => $groupTitle
                )
            ),
            'avatar' => $avatar,
            'content' => $comment->getMessage(),
            'contentImage' => $groupImage
        );

        $userIds = GROUPS_BOL_Service::getInstance()->findGroupUserIdList($group->id);

        foreach ( $userIds as $uid )
        {
            if ( $uid == $userId )
            {
                continue;
            }

            $notificationParams['userId'] = $uid;

            $event = new OW_Event('notifications.add', $notificationParams, $notificationData);
            OW::getEventManager()->trigger($event);
        }
    }



    public function onCommand( OW_Event $event )
    {
        $params = $event->getParams();

        if ( !in_array($params['command'], array('groups.accept', 'groups.ignore')) )
        {
            return;
        }

        $groupId = $params['data'];
        $userId = OW::getUser()->getId();
        $jsResponse = UTIL_JsGenerator::newInstance();

        if ( $params['command'] == 'groups.accept' )
        {
            GROUPS_BOL_Service::getInstance()->addUser($groupId, $userId);
            $jsResponse->callFunction(array('OW', 'info'), array( OW::getLanguage()->text('groups', 'join_complete_message') ));
        }
        else if ( $params['command'] == 'groups.ignore' )
        {
            GROUPS_BOL_Service::getInstance()->deleteInvite($groupId, $userId);
        }

        $event->setData($jsResponse);
    }

    public function removeFromInvitations( OW_Event $event )
    {
        $params = $event->getParams();
        $groupId = $params['groupId'];
        $userId = $params['userId'];

        OW::getEventManager()->call('invitations.remove', array(
            'userId' => $userId,
            'entityType' => self::INVITATION_JOIN,
            'entityId' => $groupId
        ));
    }

    public function onGroupDelete( OW_Event $event )
    {
        $params = $event->getParams();
        $groupId = $params['groupId'];

        OW::getEventManager()->call('invitations.remove', array(
            'entityType' => self::INVITATION_JOIN,
            'entityId' => $groupId
        ));

        OW::getEventManager()->call('notifications.remove', array(
            'entityType' => GROUPS_BOL_Service::ENTITY_TYPE_WAL,
            'entityId' => $groupId
        ));
    }

    public function genericInit()
    {
        //Invitations
        OW::getEventManager()->bind('invitations.on_dublicate', array($this, 'onInviteDublicate'));
        
        //Notifications
        OW::getEventManager()->bind('notifications.collect_actions', array($this, 'onCollectNotificationActions'));
        OW::getEventManager()->bind('base_add_comment', array($this, 'onComment'));
        OW::getEventManager()->bind('notifications.on_item_send', array($this, 'onItemSend'));
        
        //Groups
        OW::getEventManager()->bind(GROUPS_BOL_Service::EVENT_INVITE_DELETED, array($this, 'removeFromInvitations'));
        OW::getEventManager()->bind(GROUPS_BOL_Service::EVENT_INVITE_ADDED, array($this, 'onInvite'));
        OW::getEventManager()->bind(GROUPS_BOL_Service::EVENT_ON_DELETE, array($this, 'onGroupDelete'));
    }
    
    public function init()
    {
        $this->genericInit();
        
        //Invitations
        OW::getEventManager()->bind('invitations.on_command', array($this, 'onCommand'));
        OW::getEventManager()->bind('invitations.on_item_render', array($this, 'onInviteRender'));
    }
}