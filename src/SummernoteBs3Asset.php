<?php
/**
 * @package yii2-summernote
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace marqu3s\summernote;

use yii\web\AssetBundle;

class SummernoteBs3Asset extends AssetBundle
{
    /**
     * {@inheritDoc}
     */
    public $sourcePath = '@bower/summernote/dist';

    /**
     * {@inheritDoc}
     */
    public $css = [
        'summernote.css'
    ];
    /**
     * {@inheritDoc}
     */
    public $js = [
        'summernote.js'
    ];

    /**
     * {@inheritDoc}
     */
    public $depends = [
        'yii\bootstrap\BootstrapPluginAsset',
    ];
}
