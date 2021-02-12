<?php

namespace marqu3s\summernote;

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
}
