<?php
/**
 * Created by PhpStorm.
 * User: joao
 * Date: 01/08/17
 * Time: 08:42
 */

namespace marqu3s\summernote\actions;

use Aws\S3\PostObjectV4;
use Aws\S3\S3Client;
use Aws\Sdk;
use Yii;
use yii\base\Action;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Json;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * Class SignAwsRequestAction
 * Use this class to facilitate uploading stuff to Amazon S3.
 *
 * This class can sign POST request to upload files to a bucket.
 * To use, add it to a controller.
 *
 * ```
 * public function actions()
 * {
 *     return [
 *         'sign-aws-request' => [
 *             'class' => 'common\actions\SignAwsRequestAction',
 *             'clientPrivateKey' => 'AWS-KEY',
 *             'clientPrivateSecret' => 'AWS-SECRET',
 *             'expectedBucketName' => 'BUCKET-NAME',
 *             'expectedHostName' => 'BUCKET-NAME',
 *             'expectedMaxSize' => 'MAX-FILE-SIZE'
 *         ]
 *     ];
 * }
 * ```
 *
 * Then use the following endpoint to sign the requests using the version 4 (prefered):
 * /<controller-name>/sign-aws-request?v4=true
 *
 * Or use the following endpoint to sign the requests using the version 2:
 * /<controller-name>/sign-aws-request
 *
 * NOTE: You will need to add the Amazon PHP SDK to your project!
 * The recommended way to install the PHP SDK is using composer.
 *
 * ```
 * php composer.phar require aws/aws-sdk-php
 * ```
 *
 * For more options: http://docs.aws.amazon.com/aws-sdk-php/v3/guide/getting-started/installation.html
 *
 * @package common\actions
 */
class SignAwsRequestAction extends Action
{
    public $clientPrivateKey;
    public $clientPrivateSecret;
    public $expectedBucketName;
    public $expectedHostName;
    public $expectedMaxSize;

    /**
     * Runs the sign action
     * @throws ServerErrorHttpException
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     */
    public function run()
    {
        $request = Yii::$app->request;

        if ($request->method === 'OPTIONS') {
            # This first conditional will only ever evaluate to true in a
            # CORS environment
            $this->handlePreflight();
        } elseif ($request->method === 'DELETE') {
            # This second conditional will only ever evaluate to true if
            # the delete file feature is enabled.
            $this->handleCorsRequest(); // only needed in a CORS environment
            $this->deleteObject();
        } elseif ($request->method === 'POST') {
            # This is all you really need if not using the delete file feature
            # and not working in a CORS environment
            $this->handleCorsRequest();

            # Assumes the successEndpoint has a parameter of "success" associated with it,
            # to allow the server to differentiate between a successEndpoint request
            # and other POST requests (all requests are sent to the same endpoint in this example).
            # This condition is not needed if you don't require a callback on upload success.
            $success = $request->getBodyParam('success', $request->getQueryParam('success', false));
            $method = $request->getBodyParam('_method', $request->getQueryParam('_method'));

            if ($success) {
                $this->verifyFileInS3($this->shouldIncludeThumbnail());
            } elseif ($method) {
                $this->deleteObject();
            } else {
                $this->signRequest();
            }
        }
    }

    /**
     * This will retrieve the "intended" request method.  Normally, this is the
     * actual method of the request.  Sometimes, though, the intended request method
     * must be hidden in the parameters of the request.  For example, when attempting to
     * send a DELETE request in a cross-origin environment in IE9 or older, it is not
     * possible to send a DELETE request.  So, we send a POST with the intended method,
     * DELETE, in a "_method" parameter.
     */
    protected function getRequestMethod()
    {
        $method = Yii::$app->request->getBodyParam('_method', Yii::$app->request->getQueryParam('_method'));

        return $method ? $method : Yii::$app->request->method;
    }

    /**
     * Only needed in cross-origin setups
     */
    protected function handlePreflight()
    {
        $this->handleCorsRequest();
        Yii::$app->response->headers->set('Access-Control-Allow-Methods', 'POST');
        Yii::$app->response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
    }

    /**
     *  Only needed in cross-origin setups
     */
    protected function handleCorsRequest()
    {
        // If you are relying on CORS, you will need to adjust the allowed domain here.
        Yii::$app->response->headers->set('Access-Control-Allow-Origin', '*');
        //header('Access-Control-Allow-Origin: http://joao-portal2.riic.local');
    }

    /**
     * @return S3Client
     */
    protected function getS3Client(): S3Client
    {
        $sharedConfig = [
            'version' => 'latest',
            'region' => 'sa-east-1',
            'credentials' => [
                // User credentials on AWS
                'key' => $this->clientPrivateKey,
                'secret' => $this->clientPrivateSecret
            ]
        ];

        $sdk = new Sdk($sharedConfig);

        return $sdk->createS3();
    }

    /**
     * Only needed if the delete file feature is enabled
     */
    protected function deleteObject()
    {
        $bucket = Yii::$app->request->getBodyParam('bucket', Yii::$app->request->getQueryParam('bucket'));
        $key = Yii::$app->request->getBodyParam('key', Yii::$app->request->getQueryParam('key'));

        $this->getS3Client()->deleteObject([
            'Bucket' => $bucket,
            'Key' => $key,
        ]);
    }

    /**
     * Signs a request.
     * @throws \Exception
     */
    protected function signRequest()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $responseBody = Yii::$app->request->rawBody;
        $contentAsObject = Json::decode($responseBody);
        $jsonContent = Json::encode($contentAsObject);

        if (!empty($contentAsObject['headers'])) {
            $this->signRestRequest($contentAsObject['headers']);
        } else {
            $this->signPolicy($jsonContent);
        }
    }

    /**
     * @param string $headersStr
     */
    protected function signRestRequest(string $headersStr)
    {
        $v4 = Yii::$app->request->getBodyParam('v4', Yii::$app->request->getQueryParam('v4', false));
        $version = $v4 ? 4 : 2;
        if ($this->isValidRestRequest($headersStr, $version)) {
            if ($version == 4) {
                $response = ['signature' => $this->signV4RestRequest($headersStr)];
            } else {
                $response = ['signature' => $this->sign($headersStr)];
            }
            echo Json::encode($response);
        } else {
            echo Json::encode(['invalid' => true]);
        }
    }

    /**
     * @param string $headersStr
     * @param integer $version
     *
     * @return bool
     */
    protected function isValidRestRequest(string $headersStr, int $version): bool
    {
        if ($version == 2) {
            $pattern = "/\/{$this->expectedBucketName}\/.+$/";
        } else {
            $pattern = "/host:{$this->expectedHostName}/";
        }
        preg_match($pattern, $headersStr, $matches);

        return count($matches) > 0;
    }

    /**
     * @param string $policyStr
     * @throws \Exception
     */
    protected function signPolicy(string $policyStr)
    {
        $v4 = Yii::$app->request->getBodyParam('v4', Yii::$app->request->getQueryParam('v4', false));
        $policyObj = Json::decode($policyStr, true);
        if ($this->isPolicyValid($policyObj)) {
            $encodedPolicy = base64_encode($policyStr);
            if ($v4) {
                $response = $this->signV4Policy($policyObj);
            } else {
                $response = ['policy' => $encodedPolicy, 'signature' => $this->sign($encodedPolicy)];
            }
            echo Json::encode($response);
        } else {
            echo Json::encode(['invalid' => true]);
        }
    }

    /**
     * @param array $policy
     *
     * @return bool
     */
    protected function isPolicyValid(array $policy): bool
    {
        $conditions = ArrayHelper::remove($policy, 'conditions', []);
        $bucket = null;
        $parsedMaxSize = null;
        foreach ($conditions as $condition) {
            if (isset($condition['bucket'])) {
                $bucket = $condition['bucket'];
            } elseif (isset($condition[0]) && strcasecmp($condition[0], 'content-length-range') === 0) {
                $parsedMaxSize = $condition[2];
            }
        }

        return $bucket === $this->expectedBucketName && $parsedMaxSize == (string)$this->expectedMaxSize;
    }

    /**
     * @param string $stringToSign
     *
     * @return string
     */
    protected function sign(string $stringToSign): string
    {
        return base64_encode(hash_hmac('sha1', $stringToSign, $this->clientPrivateKey, true));
    }

    /**
     * @param array $policyObj
     *
     * @return array
     * @throws \Exception
     */
    protected function signV4Policy(array $policyObj): array
    {
        $post = new PostObjectV4(
            $this->getS3Client(),
            $this->expectedBucketName,
            [],
            $policyObj['conditions'],
            $policyObj['expiration']
        );
        $formInputs = $post->getFormInputs();

        return [
            'policy' => ArrayHelper::getValue($formInputs, 'Policy'),
            'signature' => ArrayHelper::getValue($formInputs, 'X-Amz-Signature'),
            'x_amz_date' => ArrayHelper::getValue($formInputs, 'X-Amz-Date'),
            'x_amz_credential' => ArrayHelper::getValue($formInputs, 'X-Amz-Credential'),
            'x_amz_algorithm' => ArrayHelper::getValue($formInputs, 'X-Amz-Algorithm')
        ];
    }

    /**
     * @param string $rawStringToSign
     *
     * @return string
     */
    protected function signV4RestRequest(string $rawStringToSign): string
    {

        $pattern = '/.+\\n.+\\n(\\d+)\/(.+)\/s3\/aws4_request\\n(.+)/s';
        preg_match($pattern, $rawStringToSign, $matches);
        $hashedCanonicalRequest = hash('sha256', $matches[3]);
        $stringToSign = preg_replace(
            '/^(.+)\/s3\/aws4_request\\n.+$/s',
            '$1/s3/aws4_request' . "\n" . $hashedCanonicalRequest,
            $rawStringToSign
        );
        $dateKey = hash_hmac('sha256', $matches[1], 'AWS4' . $this->clientPrivateKey, true);
        $dateRegionKey = hash_hmac('sha256', $matches[2], $dateKey, true);
        $dateRegionServiceKey = hash_hmac('sha256', 's3', $dateRegionKey, true);
        $signingKey = hash_hmac('sha256', 'aws4_request', $dateRegionServiceKey, true);

        return hash_hmac('sha256', $stringToSign, $signingKey);
    }

    /**
     * This is not needed if you don't require a callback on upload success.
     *
     * @param bool $includeThumbnail
     * @throws ServerErrorHttpException
     */
    protected function verifyFileInS3(bool $includeThumbnail)
    {
        $bucket = Yii::$app->request->getBodyParam('bucket', Yii::$app->request->getQueryParam('bucket'));
        $key = Yii::$app->request->getBodyParam('key', Yii::$app->request->getQueryParam('key'));

        # If utilizing CORS, we return a 200 response with the error message in the body
        # to ensure Fine Uploader can parse the error message in IE9 and IE8,
        # since XDomainRequest is used on those browsers for CORS requests.  XDomainRequest
        # does not allow access to the response body for non-success responses.
        if (isset($this->expectedMaxSize) && $this->getObjectSize($bucket, $key) > $this->expectedMaxSize) {
            # You can safely uncomment this next line if you are not depending on CORS
            $this->deleteObject();
            throw new ServerErrorHttpException(Json::encode(['error' => 'File is too big!', 'preventRetry' => true]));
        } else {
            $link = $this->getTempLink($bucket, $key);
            $response = ['tempLink' => $link];
            if ($includeThumbnail) {
                $response['thumbnailUrl'] = $link;
            }
            echo Json::encode($response);
        }
    }

    /**
     * Provide a time-bombed public link to the file.
     *
     * @param string $bucket
     * @param string $key
     *
     * @return string
     */
    protected function getTempLink(string $bucket, string $key): string
    {
        return $this->getS3Client()->getObjectUrl($bucket, $key);
    }

    /**
     * Return an object size.
     *
     * @param string $bucket
     * @param string $key
     *
     * @return mixed
     */
    protected function getObjectSize(string $bucket, string $key)
    {
        $objInfo = $this->getS3Client()->headObject([
            'Bucket' => $bucket,
            'Key' => $key,
        ]);

        return $objInfo['ContentLength'];
    }

    /**
     * Return true if it's likely that the associate file is natively
     * viewable in a browser.  For simplicity, just uses the file extension
     * to make this determination, along with an array of extensions that one
     * would expect all supported browsers are able to render natively.
     *
     * @param string $filename
     *
     * @return boolean
     * @throws \yii\base\InvalidConfigException
     */
    protected function isFileViewableImage(string $filename): bool
    {
        $mime = FileHelper::getMimeType($filename);
        $viewableMimes = [
            'image/jpeg',
            'image/jpg',
            'image/bmp',
            'image/png',
            'image/gif',
            'image/webp'
        ];

        return in_array($mime, $viewableMimes);
    }

    /**
     * Returns true if we should attempt to include a link
     * to a thumbnail in the uploadSuccess response.  In it's simplest form
     * (which is our goal here - keep it simple) we only include a link to
     * a viewable image and only if the browser is not capable of generating a client-side preview.
     *
     * @return boolean
     * @throws \yii\base\InvalidConfigException
     */
    protected function shouldIncludeThumbnail(): bool
    {
        $filename = Yii::$app->request->getBodyParam('name', Yii::$app->request->getQueryParam('name'));
        $browserPreviewCapable = Yii::$app->request->getBodyParam(
            'isBrowserPreviewCapable',
            Yii::$app->request->getQueryParam('isBrowserPreviewCapable', false)
        );
        $isPreviewCapable = $browserPreviewCapable == 'true';
        $isFileViewableImage = $this->isFileViewableImage($filename);

        return !$isPreviewCapable && $isFileViewableImage;
    }
}
