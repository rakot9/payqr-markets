<?php
use yii\bootstrap\Tabs;

if(!\Yii::$app->user->getId()) {
    Yii::$app->getResponse()->redirect(['site/login']);
}

echo '<h1>Конструктор кнопки</h1>';

echo Tabs::widget([
    'items' => [
        [
            'label' => 'Сценарий Buy',
            'content' => $this->render('buy',['button'=>$buy]),
            'headerOptions' => [],
            'options' => ['id' => 'scenario'],
            'active' => true
        ],
        [
            'label' => 'Сценарий Pay',
            'content' => $this->render('pay',['pay'=>$pay]),
            'headerOptions' => [],
        ],
    ]
]);