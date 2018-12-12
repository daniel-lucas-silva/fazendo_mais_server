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
    return $this->jwt->encode($data, $this->tokenConfig['secret']);
  }

  public function decodeToken($token) {
    return $this->jwt->decode($token, $this->tokenConfig['secret'], array('HS256'));
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

  public function validPhone($phone) {
    preg_match('/^(?:(?:\+|00)?(55)\s?)?(?:\(?([1-9][0-9])\)?\s?)?(?:((?:9\d|[2-9])\d{3})\-?(\d{4}))$/', $phone, $matches);
    return $matches;
  }

  function siteURL() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'] . "/";
    return $protocol.$domainName;
  }

  function base64ToImg($data) {

    $filename = uniqid().".png";
    $dir = APP_PATH . "/public/files/";

    if(file_put_contents($dir.$filename,file_get_contents($data))){
      return $this->siteURL()."/files/".$filename;
    }
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

  function genKeywords($text) {
    $text = preg_replace('~[^\pL\d]+~u', ' ', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^ \w]+~', '', $text);
    $text = trim($text, ' ');
    $text = preg_replace('~ +~', ' ', $text);
    $text = strtolower($text);
    if (empty($text))
      return 'n-a';
    return $text;
  }

  function genImage($data) {

  }

  function genThumbnail($imagedata) {

    $image=base64_decode($imagedata);

    $im->readimageblob($image);
    $im->thumbnailImage(200,82,true);
    // Add a subtle border
    $color=new ImagickPixel();
    $color->setColor("rgb(220,220,220)");
    $im->borderImage($color,1,1);

//    Output the image
//    $output = $im->getimageblob();
//    $outputtype = $im->getFormat();

    $im->optimizeImageLayers();

// Compression and quality
    $im->setImageCompression(Imagick::COMPRESSION_JPEG);
    $im->setImageCompressionQuality(25);

// Write the image back
    $im->writeImages("File_Path/Image_Opti.jpg", true);

//    header("Content-type: $outputtype");
//    echo $output;
  }

  function delImage($path) {}

  // '/src="(data:image\/[^;]+;base64[^"]+)"/i' extract image from text
}
