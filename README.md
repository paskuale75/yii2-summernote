# Yii2 Summernote widget. Summernote 0.8.0

[Yii2](http://www.yiiframework.com) [Summernote](http://hackerwins.github.io/summernote) widget. Super simple WYSIWYG editor on Bootstrap

## Installation

### Composer

The preferred way to install this extension is through [Composer](http://getcomposer.org/).

Either run

	php composer.phar require marqu3s/yii2-summernote "dev-master"

or add

	"marqu3s/yii2-summernote": "dev-master"

to the require section of your composer.json

## Usage
```php
<?php
use marqu3s\summernote\Summernote;

/** @var $form \yii\widgets\ActiveForm */
/** @var $model \yii\base\Model */

echo $form->field($model, 'content')->widget(Summernote::class, [
    'clientOptions' => [
        // ...
    ]
]);
```

or
```php
<?php
use marqu3s\summernote\Summernote;

echo Summernote::widget([
    'name' => 'editor_id',
    'clientOptions' => [
        // ...
    ]
]);
```
	
### Bootstrap version
By default `\marqu3s\summernote\SummernoteAsset` loads Bootstrap 4 compatible Version of Summernote.
If you need Boostrap 3 compatible version, configure your application like this:
```php
'assetManager' => [
    'bundles' => [
        'marqu3s\summernote\SummernoteAsset'   => [
            'css' => [
                'summernote.css'
            ],
            'js' => [
                'summernote.js'
            ], 
            'depends' => [
                'yii\bootstrap\BootstrapPluginAsset',
            ]
        ]
    ]
]
``` 

If you need Bootstrap 5 compatible one, the configuration would look like this:
```php
'assetManager' => [
    'bundles' => [
        'marqu3s\summernote\SummernoteAsset'   => [
            'css' => [
                'summernote-bs5.css'
            ],
            'js' => [
                'summernote-bs5.js'
            ], 
            'depends' => [
                'yii\bootstrap\BootstrapPluginAsset',
            ]
        ]
    ]
]
``` 

### Uploading directly to Amazon S3

To upload images inserted into the editor to S3, you have to configure a few options.




```php
<?php
use marqu3s\summernote\Summernote;

/** @var $model \yii\base\Model */

echo Summernote::widget([
    'uploadToS3' => true,
    'signEndpoint' => '/<controller>/sign-aws-request?v4=true',
    'bucket' => 'S3-BUCKET-NAME',
    //'folder' => '',
    'folder' => new \yii\web\JsExpression("function() { return $('#aFormFieldId').val() + '/'; }"),
    'filenamePrefix' => "'{$model->id}-'",
    'maxFileSize' => 1024000,
    'expiration' => gmdate('Y-m-d\TH:i:s.000\Z', strtotime('+5 minutes')),
    'clientOptions' => [
        ...
    ]
]);
```
	
Then, in your controller, configure an action as the signEndpoint to sign the POST request that will upload the image.

```php
<?php
public function actions()
{
   return [
      'sign-aws-request' => [
          'class' => 'marqu3s\summernote\actions\SignAwsRequestAction',
          'clientPrivateKey' => 'AWS-KEY',
          'clientPrivateSecret' => 'AWS-SECRET',
          'expectedBucketName' => 'BUCKET-NAME',
          'expectedHostName' => 'BUCKET-NAME',
          'expectedMaxSize' => 'MAX-FILE-SIZE'
      ]
   ];
}
```

See [clientOptions](http://hackerwins.github.io/summernote/features.html)

## Original Author

[Aleksandr Zelenin](https://github.com/zelenin/), e-mail: [aleksandr@zelenin.me](mailto:aleksandr@zelenin.me)

## Updates by

[João Marques](https://github.com/marqu3s/), e-mail: [joao@jjmf.com](mailto:joao@jjmf.com)
