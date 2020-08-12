<?php

namespace marqu3s\summernote;

use yii\web\AssetBundle;

class CodemirrorAsset extends AssetBundle
{
    /**
     * {@inheritDoc}
     */
    public $sourcePath = '@bower/codemirror';

    /**
     * {@inheritDoc}
     */
    public $css = [
        'lib/codemirror.css',
        'theme/monokai.css'
    ];

    /**
     * {@inheritDoc}
     */
    public $js = [
        'lib/codemirror.js',
        'mode/htmlmixed/htmlmixed.js'
    ];
}
