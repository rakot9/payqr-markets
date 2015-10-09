<?php
use yii\grid\GridView;

echo GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        'id',
        'name',
        [
            'class' => yii\grid\ActionColumn::className(),
            'template' => '{update} {delete}',
//            'buttons' =>[ 
//                'update' => function(){return "";},
//                'delete' => function(){return "";},
//            ]
        ]
    ],
]) ?>