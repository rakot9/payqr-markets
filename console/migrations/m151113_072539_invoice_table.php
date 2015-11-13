<?php

use yii\db\Schema;
use yii\db\Migration;

class m151113_072539_invoice_table extends Migration
{
    public $tablePrefix;
    public $tableName;
    
    public function before()
    {
        $this->tablePrefix = Yii::$app->getDb()->tablePrefix;
        $this->tableName = $this->tablePrefix. 'invoice_table';
    }

    
    public function up()
    {
        $this->before();
        
        $tableOptions = null;
        
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        
        $this->createTable($this->tableName, [
            'id' => Schema::TYPE_PK,
            'invoice_id' => Schema::TYPE_STRING . '(255) NOT NULL',
            'order_id' => Schema::TYPE_INTEGER . '(11) NULL',
            'amount' => Schema::TYPE_DECIMAL . '(10,2) NULL',
            'data' => Schema::TYPE_TEXT . ' NULL',
            'datetime' => Schema::TYPE_TIMESTAMP . ' DEFAULT CURRENT_TIMESTAMP',
            'is_paid' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT 0'
        ], $tableOptions);
        
        $this->createIndex('invoice_id', $this->tableName , 'invoice_id', true);
    }

    public function down()
    {
        $this->before();
        
        $this->dropIndex('invoice_id', $this->tableName);
        
        $this->dropTable($this->tableName);

        echo "m151113_072539_invoice_table cannot be reverted.\n";

        return true;
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
