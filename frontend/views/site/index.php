<?php
/* @var $this yii\web\View */

use frontend\modules\Payqr;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'PayQR редактор кнопок';
?>
<div class="site-index">

    <div class="jumbotron">
        <!--        
        <h1>Congratulations!</h1>

        <p class="lead">You have successfully created your Yii-powered application.</p>

        <p><a class="btn btn-lg btn-success" href="http://www.yiiframework.com">Get started with Yii</a></p>-->
    </div>

    <div class="body-content">
        <div class="row">
            <h2>Здесь представлены настройки кнопок пользователя</h2>
            <?php
            echo GridView::widget([
                'dataProvider' => $dataProvider,
                'columns' => [
                    'id',
                    'name',
                    [
                        'class' => yii\grid\ActionColumn::className(),
                        'template' => '{update} {delete}',
                        'buttons' =>[ 
                            'update' => function ($url, $model, $key) {
                                return Html::a('<span class="glyphicon glyphicon-pencil"></span>', '?r=payqr/button/edit&market_id='.$key);
                            },
                            'delete' => function ($url, $model, $key) {
                                return Html::a('<span class="glyphicon glyphicon-trash"></span>', '?r=payqr/button/delete&market_id='.$key);
                            },
                        ]
                    ]
                ],
            ]);
            echo \yii\bootstrap\Html::a('Создать кнопку', ['payqr/button/edit'], ['class'=>'btn btn-primary']);
            ?>
        </div>

    </div>
</div>
