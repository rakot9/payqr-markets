<?php

use yii\db\Schema;
use yii\db\Migration;

class m150922_084433_create_market_table extends Migration
{
    public $tablePrefix;
    public $tableName;
    
    public function before()
    {
        $this->tablePrefix = Yii::$app->getDb()->tablePrefix;
        $this->tableName = $this->tablePrefix. 'market';
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
            'name' => Schema::TYPE_STRING . '(124) NOT NULL',
            'settings' => Schema::TYPE_DECIMAL . '(10,2) NOT NULL'
        ], $tableOptions);
        
        $this->createIndex('market_id', $this->tableName , 'id', true);
    }

    public function down()
    {
        $this->before();
        
        $this->dropIndex('market_id', $this->tableName);
        
        $this->dropTable($this->tableName);

        echo "m150922_084433_create_market_table cannot be reverted.\n";

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
