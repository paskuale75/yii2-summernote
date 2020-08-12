<?php

namespace marqu3s\summernote;

use Yii;
use yii\web\AssetBundle;

class SummernoteAsset extends AssetBundle
{
    /**
     * {@inheritDoc}
     */
    public $sourcePath = '@bower/summernote/dist';

    /**
     * {@inheritDoc}
     */
    public $css = [
        'summernote-bs4.css'
    ];
    /**
     * {@inheritDoc}
     */
    public $js = [
        'summernote-bs4.js'
    ];

    /**
     * {@inheritDoc}
     */
    public $depends = [
        'yii\bootstrap4\BootstrapPluginAsset',
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $postfix = YII_DEBUG ? '' : '.min';

        if (isset(Yii::$app->params['bsVersion']) && Yii::$app->params['bsVersion'] == 4) {
            $this->depends = ['yii\bootstrap4\BootstrapPluginAsset'];
            $this->css[] = 'summernote-bs4.css';
            $this->js[] = 'summernote-bs4' . $postfix . '.js';
        } else {
            $this->depends = ['yii\bootstrap\BootstrapPluginAsset'];
            $this->css[] = 'summernote.css';
            $this->js[] = 'summernote' . $postfix . '.js';
        }

        parent::init();
    }
}
