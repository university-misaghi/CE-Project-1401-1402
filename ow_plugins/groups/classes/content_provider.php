<?php

class GROUPS_CLASS_ContentProvider
{
    const ENTITY_TYPE = GROUPS_BOL_Service::FEED_ENTITY_TYPE;
    
    /**
     * Singleton instance.
     *
     * @var GROUPS_CLASS_ContentProvider
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return GROUPS_CLASS_ContentProvider
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
     *
     * @var GROUPS_BOL_Service
     */
    private $service;
    
    private function __construct()
    {
        $this->service = GROUPS_BOL_Service::getInstance();
    }
    
    public function onCollectTypes( BASE_CLASS_EventCollector $event )
    {
        $event->add(array(
            "pluginKey" => "groups",
            "group" => "groups",
            "groupLabel" => OW::getLanguage()->text("groups", "content_groups_label"),
            "entityType" => self::ENTITY_TYPE,
            "entityLabel" => OW::getLanguage()->text("groups", "content_group_label"),
            "displayFormat" => "image_content"
        ));
    }
    
    public function onGetInfo( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( $params["entityType"] != self::ENTITY_TYPE )
        {
            return;
        }
        
        $groups = $this->service->findGroupListByIds($params["entityIds"]);
        $out = array();
        foreach ( $groups as $group )
        {
            $info = array();

            $info["id"] = $group->id;
            $info["userId"] = $group->userId;

            $info["title"] = $group->title;
            $info["description"] = $group->description;
            $info["url"] = $this->service->getGroupUrl($group);
            $info["timeStamp"] = $group->timeStamp;

            $info["image"] = array(
                "thumbnail" => $this->service->getGroupImageUrl($group),
            );
            
            $out[$group->id] = $info;
        }
                
        $event->setData($out);
        
        return $out;
    }
    
    public function onUpdateInfo( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();
        
        if ( $params["entityType"] != self::ENTITY_TYPE )
        {
            return;
        }
        
        foreach ( $data as $groupId => $info )
        {
            $group = $this->service->findGroupById($groupId);
            $group->status = $info["status"];
            
            $this->service->saveGroup($group);
        }
    }
    
    public function onDelete( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( $params["entityType"] != self::ENTITY_TYPE )
        {
            return;
        }
        
        foreach ( $params["entityIds"] as $groupId )
        {
            $this->service->deleteGroup($groupId);
        }
    }

    // Groups events
    
    public function onBeforeGroupDelete( OW_Event $event )
    {
        $params = $event->getParams();
        
        OW::getEventManager()->trigger(new OW_Event(BOL_ContentService::EVENT_BEFORE_DELETE, array(
            "entityType" => self::ENTITY_TYPE,
            "entityId" => $params["groupId"]
        )));
    }
    
    public function onAfterGroupAdd( OW_Event $event )
    {
        $params = $event->getParams();
        
        OW::getEventManager()->trigger(new OW_Event(BOL_ContentService::EVENT_AFTER_ADD, array(
            "entityType" => self::ENTITY_TYPE,
            "entityId" => $params["groupId"]
        ), array(
            "string" => array("key" => "groups+feed_create_string")
        )));
    }
    
    public function onAfterGroupEdit( OW_Event $event )
    {
        $params = $event->getParams();
        
        OW::getEventManager()->trigger(new OW_Event(BOL_ContentService::EVENT_AFTER_CHANGE, array(
            "entityType" => self::ENTITY_TYPE,
            "entityId" => $params["groupId"]
        ), array(
            "string" => array("key" => "groups+group_edited_string")
        )));
    }
    
    public function init()
    {
        OW::getEventManager()->bind(GROUPS_BOL_Service::EVENT_ON_DELETE, array($this, "onBeforeGroupDelete"));
        OW::getEventManager()->bind(GROUPS_BOL_Service::EVENT_CREATE, array($this, "onAfterGroupAdd"));
        OW::getEventManager()->bind(GROUPS_BOL_Service::EVENT_EDIT, array($this, "onAfterGroupEdit"));
        
        OW::getEventManager()->bind(BOL_ContentService::EVENT_COLLECT_TYPES, array($this, "onCollectTypes"));
        OW::getEventManager()->bind(BOL_ContentService::EVENT_GET_INFO, array($this, "onGetInfo"));
        OW::getEventManager()->bind(BOL_ContentService::EVENT_UPDATE_INFO, array($this, "onUpdateInfo"));
        OW::getEventManager()->bind(BOL_ContentService::EVENT_DELETE, array($this, "onDelete"));
    }
}