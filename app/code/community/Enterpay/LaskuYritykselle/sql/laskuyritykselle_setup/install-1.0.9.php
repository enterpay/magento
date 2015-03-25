<?php

try {

    $installer = $this;
    $installer->startSetup();

    $table_names = array(
        'sales/quote',
        'sales/quote_address',
        'sales/order'
    );

    foreach ($table_names as $table_name) {

        $installer->getConnection()->addColumn(
            $installer->getTable($table_name),
            'base_payment_charge',
            array(
                'type' => Varien_Db_Ddl_Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => true,
                'comment' => 'Base Payment Charge',
            )
        );

        $installer->getConnection()->addColumn(
            $installer->getTable($table_name),
            'payment_charge',
            array(
                'type' => Varien_Db_Ddl_Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => true,
                'comment' => 'Payment Charge',
            )
        );

    }

    $installer->endSetup();

} catch (Exception $e) {
    var_dump($e);
}
