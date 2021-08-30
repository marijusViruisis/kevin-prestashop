<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_6_9($object)
{
    $query = 'ALTER TABLE `' . _DB_PREFIX_ . 'kevin` 
        ADD `ip_port` VARCHAR(64) NULL DEFAULT NULL,
        ADD `user_agent` TEXT NULL DEFAULT NULL,
        ADD `device_id` VARCHAR(64) NULL DEFAULT NULL;';

    if (Db::getInstance()->execute($query) == false) {

        return false;
    }

    return true;
}
