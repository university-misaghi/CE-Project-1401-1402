<?php

try {
    $action = Updater::getAuthorizationService()->findAction("groups", "delete_comment_by_content_owner");
    Updater::getAuthorizationService()->deleteAction($action->id);
} catch (Exception $e) {}
