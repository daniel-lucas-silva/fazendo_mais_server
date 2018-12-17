<?php

use Phalcon\Mvc\Controller;

class ControllerBase extends Controller
{
  public function registerLog() {
    // gets user token
    $token_decoded = $this->decodeToken($this->getToken());

    // Gets URL route from request
    $url = $this->request->get();

    // Initiates log db transaction
    $this->db_log->begin();
    $newLog = new Logs();
    $newLog->email = $token_decoded->user_email; // gets username
    $newLog->route = $url['_url']; // gets route
    $newLog->date = $this->getNowDateTime();
    if (!$newLog->save()) {
      // rollback transaction
      $this->db_log->rollback();
      // Send errors
      $errors = array();
      foreach ($newLog->getMessages() as $message) {
        $errors[$message->getField()] = $message->getMessage();
      }
      $this->buildErrorResponse(400, 'common.COULD_NOT_BE_CREATED', $errors);
    } else {
      // Commit the transaction
      $this->db_log->commit();
    }
  }

  public function getNowDateTime() {
    $now = new DateTime();
    $now = $now->format('Y-m-d H:i:s');
    return $now;
  }

  public function getNowDateTimePlusMinutes($minutes_to_add) {
    $now = new DateTime();
    $now->add(new DateInterval('PT' . $minutes_to_add . 'M'));
    $now = $now->format('Y-m-d H:i:s');
    return $now;
  }

  public function iso8601_to_utc($date) {
    return $datetime = date('Y-m-d H:i:s', strtotime($date));
  }

  public function utc_to_iso8601($date) {
    if (!empty($date) && ($date != '0000-00-00') && ($date != '0000-00-00 00:00') && ($date != '0000-00-00 00:00:00')) {
      $datetime = new DateTime($date);
      return $datetime->format('Y-m-d\TH:i:s\Z');
    } else {
      return null;
    }
  }

  public function array_push_assoc($array, $key, $value) {
    $array[$key] = $value;
    return $array;
  }

  public function getQueryLimit($limit) {
    if ($limit != '') {
      if ($limit > 150) {
        $setLimit = 150;
      }
      if ($limit <= 0) {
        $setLimit = 1;
      }
      if (($limit >= 1) && ($limit <= 150)) {
        $setLimit = $limit;
      }
    } else {
      $setLimit = 5;
    }
    return $setLimit;
  }

  public function initializeGet() {
    if (!$this->request->isGet()) {
      die();
    }
  }

  public function initializeUpload() {
    if (!$this->request->hasFiles()) {
      die();
    }
  }

  public function initializePost() {
    if (!$this->request->isPost()) {
      die();
    }
  }

  public function initializePatch() {
    if (!$this->request->isPatch()) {
      die();
    }
  }

  public function initializeDelete() {
    if (!$this->request->isDelete()) {
      die();
    }
  }

  public function encodeToken($data) {
    $token_encoded = $this->jwt->encode($data, $this->tokenConfig['secret']);
//    $token_encoded = $this->mycrypt->encryptBase64($token_encoded);
    return $token_encoded;
  }

  public function decodeToken($token) {
//    $token = $this->mycrypt->decryptBase64($token);
    $token = $this->jwt->decode($token, $this->tokenConfig['secret'], array('HS256'));
    return $token;
  }

  public function getToken() {
    $authHeader = $this->request->getHeader('Authorization');
    $authQuery = $this->request->getQuery('token');
    return $authQuery ? $authQuery : $this->parseBearerValue($authHeader);
  }

  protected function parseBearerValue($string) {
    if (strpos(trim($string), 'Bearer') !== 0) {
      return null;
    }
    return preg_replace('/.*\s/', '', $string);
  }

  public function buildSuccessResponse($code, $messages, $data = '') {
    switch ($code) {
      case 200:
        $status = 'OK';
        break;
      case 201:
        $status = 'Created';
        break;
      case 202:
        break;
    }
    $generated = array(
      "status" => $status,
      "code" => $code,
      "messages" => $messages,
      "data" => $data,
    );
    $this->response->setStatusCode($code, $status)->sendHeaders();
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setJsonContent($generated, JSON_NUMERIC_CHECK)->send();
    die();
  }

  public function buildErrorResponse($code, $messages, $data = '') {
    switch ($code) {
      case 400:
        $status = 'Bad Request';
        break;
      case 401:
        $status = 'Unauthorized';
        break;
      case 403:
        $status = 'Forbidden';
        break;
      case 404:
        $status = 'Not Found';
        break;
      case 409:
        $status = 'Conflict';
        break;
    }
    $generated = array(
      "status" => $status,
      "code" => $code,
      "messages" => $messages,
      "data" => $data,
    );
    $this->response->setStatusCode($code, $status)->sendHeaders();
    $this->response->setContentType('application/json', 'UTF-8');
    $this->response->setJsonContent($generated, JSON_NUMERIC_CHECK)->send();
    die();
  }

  function genSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);

    if (empty($text)) {
      return 'n-a';
    }

    return $text;
  }

  function genImage($base64, $width = 300, $height = 300, $quality = 50) {
//    $image=base64_decode($base64);
    $image = file_get_contents($base64);

    $im = new Imagick ();
    $im->readImageBlob($image);
    $im->setImageFormat("jpeg");
    $im->setFormat("jpeg");
    $im->cropThumbnailImage( $width, $height);
    $im->optimizeImageLayers();
    $im->setImageCompression(Imagick::COMPRESSION_JPEG);
    $im->setImageCompressionQuality($quality);

    $output = $im->getimageblob();

    return $output;
  }

  function storeFile($file, $path) { } // TODO: ...

  function delFile($path) { } // TODO: ...
}
