<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "invoice_table".
 *
 * @property integer $id
 * @property string $invoice_id
 * @property string $order_id
 * @property string $amount
 * @property string $data
 * @property string $datetime
 * @property integer $is_paid
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
            [['invoice_id'], 'required'],
            [['order_id', 'data'], 'string'],
            [['amount'], 'number'],
            [['datetime'], 'safe'],
            [['is_paid'], 'integer'],
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
            'data' => 'Data',
            'datetime' => 'Datetime',
            'is_paid' => 'Is Paid',
        ];
    }
    
    public function createInvoice($invoiceId, $orderJSON, $amount)
    {
        $this->invoice_id = $invoiceId;
        $this->order_id = $orderJSON;
        $this->amount = $amount;
        $this->iteration = 1;
        $this->order_request = 0;
        $this->save();
    }
}
