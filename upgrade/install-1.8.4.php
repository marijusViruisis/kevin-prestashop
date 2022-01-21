<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_8_4($object) {
    $query = 'ALTER TABLE `' . _DB_PREFIX_ . 'kevin`
  DROP IF EXISTS `ip_port`,
  DROP IF EXISTS `user_agent`,
  DROP IF EXISTS `device_id`;';

    if (Db::getInstance()->execute($query) == false) {

        return false;
    }
    return $object->registerHook('header') &&
            $object->registerHook('backOfficeHeader') &&
            $object->registerHook('payment') &&
            $object->registerHook('orderConfirmation') &&
            $object->registerHook('paymentOptions') &&
            $object->registerHook('orderConfirmation') &&
            $object->registerHook('displayOrderConfirmation');
}
