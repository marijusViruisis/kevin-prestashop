<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_8_7($object) {
    return  $object->registerHook('backOfficeHeader') &&
            $object->registerHook('header');
}
