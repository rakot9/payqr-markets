<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "{{%market}}".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $name
 * @property string $settings
 */
class Market extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%market}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'name', 'settings'], 'required'],
            [['user_id'], 'integer'],
            [['settings'], 'string'],
            [['name'], 'string', 'max' => 124]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '№ п/п',
            'user_id' => 'Идентификатор пользователя',
            'name' => 'Название магазина',
            'settings' => 'Настройки',
        ];
    }
    
    public function getSettings()
    {
        return $this->settings;
    }
}
