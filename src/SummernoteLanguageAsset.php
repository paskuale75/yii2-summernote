<?php

namespace marqu3s\summernote;

use yii\web\AssetBundle;

class SummernoteLanguageAsset extends AssetBundle
{
    /**
     * The Language to load
     */
    public $language;
    /**
     * {@inheritDoc}
     */
    public $sourcePath = '@bower/summernote/dist/lang';
    /**
     * {@inheritDoc}
     */
    public $depends = [
        'marqu3s\summernote\SummernoteAsset'
    ];

    /**
     * {@inheritDoc}
     */
    public function registerAssetFiles($view)
    {
        $this->js[] = 'summernote-' . $this->language . '.js';
        parent::registerAssetFiles($view);
    }
}
