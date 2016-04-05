<?php

try {

    $installer = $this;

    $installer->startSetup();
    $installer->endSetup();

} catch (Exception $e) {
    var_dump($e);
}
