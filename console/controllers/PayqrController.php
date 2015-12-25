<?php

namespace console\controllers;

use yii\console\Controller;

class PayqrController extends Controller{
    
    public function actionPayqrReceiver()
    {
        echo "PayqrReceiver";
    }

    public function actionPayqrSendPayData()
    {
        /*
         * Формируем URL-для создания invoice
         * @var string $URL
         * Comment: см. раздел 3.1.2 и 3.2
         * http://confluence.hq.qrteam.ru/pages/viewpage.action?pageId=8227824&preview=/8227824/10158087/payqr-doc_invoices.pdf
         */
        $URL = "https://payqr.ru/shop/api/1.0/invoices/qr";

        /*
         * @var array $cart
         *
         */
        $cart[] = [
            'article' => '1',
            'name' => 'Копилка',
            'quantity' => '1',
            'amount' => '10.00',
            'imageUrl' => 'http://wexplain.ru/wp-content/uploads/2013/12/276.jpg'
        ];

        /*
         * @var array $userDataArray
         * Передаем информацию о пользовательских данных
         */
        $userDataArray = [
            'lastname' => 'Иванов',
            'firstname' => 'Иван',
            'middlename' => 'Иванович',
            'phone' => '+7(927)1111111',
            'email' => 'test@test.com',
            'region' => '',
            'city' => 'Москва',
            'address' => 'ул. Дубиниская, 12',
            'token' => ''
        ];

        //Заполняем пользовательскими данными поля
        $POSTDATA = "";
        $POSTDATA .= "merchId=094711-13811";
        $POSTDATA .= "&amount=1";
        $POSTDATA .= "&cart=" . json_encode($cart);

        //Устанавливаем инициализирующие invoice параметры
        $POSTDATA .= "&scenario=pay";
        $POSTDATA .= "&orderIdRequired=required";
        $POSTDATA .= "&userData=" . json_encode($userDataArray);
        /*
         *
         */
        $POSTDATAJSON['merchId'] = '094711-13811';
        $POSTDATAJSON['amount'] = '10';
        $POSTDATAJSON['cart'] = $cart;
        $POSTDATAJSON['scenario'] = 'pay';
        $POSTDATAJSON['amount'] = '1';
        $POSTDATAJSON['userData'] = $userDataArray;

        /*
         * cURL
         * @var class cURL $request
         */
        $request = curl_init();
        curl_setopt($request, CURLOPT_URL, $URL);
        curl_setopt($request, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($request, CURLOPT_POST, 1);
        curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($POSTDATAJSON));

        $rawResponse = curl_exec($request);

        $info = curl_getinfo($request);

        if((int)$info['http_code'] == 200)
        {
            file_put_contents("payqr.png", $rawResponse, FILE_APPEND);
        }

        file_put_contents("payqrresponse.log", print_r($info, true), FILE_APPEND);

        curl_close($request);
    }
}
