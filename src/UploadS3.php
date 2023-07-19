<?php

namespace Simoa;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class UploadS3
{
  private $s3Client;

  public $objectURL;

  private $messageError;

  function __construct($config)
  {
 		$this->config = $config;
	}

  public function setClient()
  {
    if (empty(getenv("HOME"))) {
      putenv('HOME=/home/'.get_current_user());
    }

    $this->s3Client = new S3Client([
        'profile' => $this->config->profile,
        'region'  => $this->config->region,
        'version' => 'latest'
    ]);
  }

  public function handleUploadFile($prefix = "")
  {
    $response = (object)[];
  
    if (is_array($_FILES) && count($_FILES) > 0) {
      foreach ($_FILES as $k => $item) {
        $file = $item['tmp_name'];
        $filename = $item['name'];

        $file = is_array($file) ? $file[0] : $file ;

        if (empty($file)) {
          return (object)[
            "error" => "imageEmpty",
            "_files" => $_FILES
          ];
        }

        $upload = $this->upload(
          $file,
          $filename,
          $prefix
        );

        if ($upload) {
          $response->{$k} = (object)
            [
              "url" => $this->objectURL,
              "id" => $filename,
              "_files" => $_FILES[$k]
          ];
        } else {
          $response->{$k} = (object)[
            "error" => true,
            "message" => $this->messageError
          ];
        }
      }
    } else {
      $response = (object)[
        "error" => true,
        "message" => "emptyFiles"
      ];
    }

    return $response;
  }

  public function upload($sourceFile, $filename = "", $prefix = "", $acl = "public-read")
  {
    $this->setClient();

    $filename =
      (!empty($filename))
        ? $filename
        : basename($sourceFile);

    $key =
      (!empty($prefix))
        ? $prefix . "/" . $filename
        : $filename;

    $mimeContentType = mime_content_type($sourceFile);
    $contentType = (preg_match("/\/(gif|jpe?g|png|pdf|json)$/i", $mimeContentType))
                        ? $mimeContentType
                        : "";

    try {
      $result = $this->s3Client->putObject([
          'ACL' => $acl,
          'Bucket'  => $this->config->bucket,
          'Key'     => $key,
          'SourceFile' => $sourceFile,
          'ContentType' => $contentType
      ]);

      $this->objectURL = $result["ObjectURL"];
      return true;
    } catch (S3Exception $e) {
      $this->messageError = $e->getMessage();
      return false;
    }
	}

  public function getPrivateObjectS3($file, $maxTime = '+1 minutes')
  {
    $this->setClient();
    $cmd = $this->s3Client->getCommand('GetObject', [
      'Bucket' => $this->config->bucket,
      'Key' => $file
    ]);

    $request = $this->s3Client->createPresignedRequest($cmd, $maxTime);
    $presignedUrl = (string)$request->getUri();
    return $presignedUrl;
  }
}
