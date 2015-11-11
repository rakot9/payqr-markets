<?php
/* @var $this yii\web\View */

use frontend\modules\Payqr;
use yii\grid\GridView;

$this->title = 'My Yii Application';
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
            //            'buttons' =>[ 
            //                'update' => function(){return "";},
            //                'delete' => function(){return "";},
            //            ]
                    ]
                ],
            ]) ?>
            
        </div>

    </div>
</div>
