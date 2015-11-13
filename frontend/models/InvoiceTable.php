<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "invoice_table".
 *
 * @property integer $id
 * @property string $invoice_id
 * @property integer $order_id
 * @property string $amount
 * @property string $products
 * @property string $datetime
 */
class InvoiceTable extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'invoice_table';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['invoice_id', 'order_id', 'amount'], 'required'],
            [['order_id'], 'integer'],
            [['amount'], 'number'],
            [['products'], 'string'],
            [['datetime'], 'safe'],
            [['invoice_id'], 'string', 'max' => 255],
            [['invoice_id'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'invoice_id' => 'Invoice ID',
            'order_id' => 'Order ID',
            'amount' => 'Amount',
            'products' => 'Products',
            'datetime' => 'Datetime',
        ];
    }
}
