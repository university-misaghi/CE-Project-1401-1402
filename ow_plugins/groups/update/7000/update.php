<?php

require_once OW_DIR_SYSTEM_PLUGIN . "base" . DS . "classes" . DS . "file_storage.php";
require_once OW_DIR_SYSTEM_PLUGIN . "base" . DS . "classes" . DS . "amazon_cloud_storage.php";

$groupsQuery = "SELECT * FROM " . OW_DB_PREFIX . "groups_group WHERE imageHash IS NOT NULL";
$groups = OW::getDbo()->queryForList($groupsQuery);

$groupsPlugin = OW::getPluginManager()->getPlugin("groups");

foreach ( $groups as $group )
{
    $smallPath = $groupsPlugin->getUserFilesDir() . 'group-' . $group["id"] . '-' . $group["imageHash"] . '.jpg';
    $bigPath = $groupsPlugin->getUserFilesDir() . 'group-' . $group["id"] . '-big-' . $group["imageHash"] . '.jpg';

    Updater::getStorage()->copyFile($smallPath, $bigPath);
}