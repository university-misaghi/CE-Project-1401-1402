<?php

class VIDEO_CLASS_ContentProvider
{
    const ENTITY_TYPE = VIDEO_BOL_ClipService::ENTITY_TYPE;
    
    /**
     * Singleton instance.
     *
     * @var VIDEO_CLASS_ContentProvider
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return VIDEO_CLASS_ContentProvider
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
     * @var VIDEO_BOL_ClipService
     */
    private $service;
    
    private function __construct()
    {
        $this->service = VIDEO_BOL_ClipService::getInstance();
    }
    
    public function onCollectTypes( BASE_CLASS_EventCollector $event )
    {
        $event->add(array(
            "pluginKey" => "video",
            "group" => "video",
            "groupLabel" => OW::getLanguage()->text("video", "content_video_group_label"),
            "entityType" => self::ENTITY_TYPE,
            "entityLabel" => OW::getLanguage()->text("video", "content_video_label"),
            "displayFormat" => "video"
        ));
    }
    
    public function onGetInfo( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( $params["entityType"] != self::ENTITY_TYPE )
        {
            return;
        }
        
        $entityList = $this->service->findClipByIds($params["entityIds"]);
        $out = array();
        foreach ( $entityList as $entity )
        {
            /* @var $entity VIDEO_BOL_Clip */
            
            $info = array();

            $info["id"] = $entity->id;
            $info["userId"] = $entity->userId;

            $info["title"] = $entity->title;
            $info["description"] = $entity->description;
            $info["url"] = $url = OW::getRouter()->urlForRoute('view_clip', array(
                'id' => $entity->id
            ));
            $info["html"] = $entity->code;
            $info["timeStamp"] = $entity->addDatetime;
            $info["provider"] = $entity->provider;

            $info["image"] = array(
                "thumbnail" => $this->service->getClipThumbUrl($entity->id)
            );
            
            if ( $info["image"]["thumbnail"] == "undefined" )
            {
                $info["image"]["thumbnail"] = $this->service->getClipDefaultThumbUrl();
            }
            
            $info["status"] = $entity->status == VIDEO_BOL_ClipDao::STATUS_APPROVED
                    ? BOL_ContentService::STATUS_ACTIVE
                    : BOL_ContentService::STATUS_APPROVAL;
            
            $out[$entity->id] = $info;
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
        
        foreach ( $data as $entityId => $info )
        {
            $statusActive = $info["status"] == BOL_ContentService::STATUS_ACTIVE;
            $status = $statusActive ? VIDEO_BOL_ClipDao::STATUS_APPROVED : VIDEO_BOL_ClipDao::STATUS_APPROVAL;

            // Set tags status
            BOL_TagService::getInstance()->setEntityStatus(VIDEO_BOL_ClipService::TAGS_ENTITY_TYPE, $entityId, $statusActive);
            
            // Set rates status
            BOL_RateService::getInstance()->setEntityStatus(VIDEO_BOL_ClipService::RATES_ENTITY_TYPE, $entityId, $statusActive);
            
            $entityDto = $this->service->findClipById($entityId);
            $entityDto->status = $status;
            
            $this->service->saveClip($entityDto);
        }
    }
    
    public function onDelete( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( $params["entityType"] != self::ENTITY_TYPE )
        {
            return;
        }
        
        foreach ( $params["entityIds"] as $entityId )
        {
            $this->service->deleteClip($entityId);
        }
    }

    // Video events
    
    public function onBeforeClipDelete( OW_Event $event )
    {
        $params = $event->getParams();
        
        OW::getEventManager()->trigger(new OW_Event(BOL_ContentService::EVENT_BEFORE_DELETE, array(
            "entityType" => self::ENTITY_TYPE,
            "entityId" => $params["clipId"]
        )));
    }
    
    public function onAfterClipAdd( OW_Event $event )
    {
        $params = $event->getParams();
        
        OW::getEventManager()->trigger(new OW_Event(BOL_ContentService::EVENT_AFTER_ADD, array(
            "entityType" => self::ENTITY_TYPE,
            "entityId" => $params["clipId"]
        ), array(
            "string" => array("key" => "video+clip_add_string")
        )));
    }
    
    public function onAfterClipEdit( OW_Event $event )
    {
        $params = $event->getParams();
        
        OW::getEventManager()->trigger(new OW_Event(BOL_ContentService::EVENT_AFTER_CHANGE, array(
            "entityType" => self::ENTITY_TYPE,
            "entityId" => $params["clipId"]
        ), array(
            "string" => array("key" => "video+clip_edited_string")
        )));
    }
    
    public function init()
    {
        OW::getEventManager()->bind(VIDEO_BOL_ClipService::EVENT_BEFORE_DELETE, array($this, "onBeforeClipDelete"));
        OW::getEventManager()->bind(VIDEO_BOL_ClipService::EVENT_AFTER_ADD, array($this, "onAfterClipAdd"));
        OW::getEventManager()->bind(VIDEO_BOL_ClipService::EVENT_AFTER_EDIT, array($this, "onAfterClipEdit"));
        
        OW::getEventManager()->bind(BOL_ContentService::EVENT_COLLECT_TYPES, array($this, "onCollectTypes"));
        OW::getEventManager()->bind(BOL_ContentService::EVENT_GET_INFO, array($this, "onGetInfo"));
        OW::getEventManager()->bind(BOL_ContentService::EVENT_UPDATE_INFO, array($this, "onUpdateInfo"));
        OW::getEventManager()->bind(BOL_ContentService::EVENT_DELETE, array($this, "onDelete"));
    }
}