<?php
use yii\bootstrap\Tabs;

echo '<h1>Конструктор кнопки</h1>';

echo Tabs::widget([
    'items' => [
        [
            'label' => 'Сценарий Bay',
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