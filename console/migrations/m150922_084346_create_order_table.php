<?php

use yii\db\Schema;
use yii\db\Migration;

class m150922_084346_create_order_table extends Migration
{
    public $tablePrefix;
    public $tableName;
    
    public function before()
    {
        $this->tablePrefix = Yii::$app->getDb()->tablePrefix;
        $this->tableName = $this->tablePrefix. 'order';
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
            'user_id' => Schema::TYPE_INTEGER . '(11) NOT NULL',
            'status_id' => Schema::TYPE_INTEGER . '(11) NOT NULL',
            'amount' => Schema::TYPE_DECIMAL . '(10,2) NOT NULL',
            'products' => Schema::TYPE_TEXT . ' NOT NULL',
            'datetime' => Schema::TYPE_TIMESTAMP . ' DEFAULT CURRENT_TIMESTAMP'
        ], $tableOptions);
        
        $this->createIndex('order_id', $this->tableName , 'id', true);
    }

    public function down()
    {
        $this->before();
        
        $this->dropIndex('order_id', $this->tableName);
        
        $this->dropTable($this->tableName);

        echo "m150922_084346_create_order_table cannot be reverted.\n";

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
