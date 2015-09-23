<?php

use yii\db\Schema;
use yii\db\Migration;

class m150923_075305_create_tests_table extends Migration
{
    public $tablePrefix;
    public $tableName;
    
    public function before()
    {
        $this->tablePrefix = Yii::$app->getDb()->tablePrefix;
        $this->tableName = $this->tablePrefix. 'tests';
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
            'user_id' => Schema::TYPE_INTEGER . ' NOT NULL',
            'test' => Schema::TYPE_STRING . ' NOT NULL',
            'is_schedule' => Schema::TYPE_BOOLEAN . ' DEFAULT false',
            'report' => Schema::TYPE_TEXT . ' DEFAULT NULL',
            'type_report' => Schema::TYPE_STRING . ' NULL'
        ], $tableOptions);
        
        $this->createIndex('test_id', $this->tableName , 'id', true);
    }

    public function down()
    {
        $this->before();
        
        $this->dropIndex('test_id', $this->tableName);
        
        $this->dropTable($this->tableName);

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
