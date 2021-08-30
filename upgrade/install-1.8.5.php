<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_8_5($object) {
    return $object->registerHook('displayAdminOrderContentShip') &&
            $object->registerHook('displayAdminOrderTabShip') &&
            $object->registerHook('displayAdminOrderTabLink') &&
            $object->registerHook('displayAdminOrderTabContent');
}
