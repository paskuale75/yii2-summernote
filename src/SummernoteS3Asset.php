<?php

namespace marqu3s\summernote;

use yii\web\AssetBundle;

class SummernoteS3Asset extends AssetBundle
{
    /**
     * {@inheritDoc}
     */
    public $sourcePath = '@marqu3s/summernote/assets';

    /**
     * {@inheritDoc}
     */
    public $js = [
        'summernote-s3.js'
    ];
}
