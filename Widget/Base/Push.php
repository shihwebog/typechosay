<?php

namespace TypechoPlugin\Say\Widget\Base;

use Typecho\Date;
use Typecho\Http\Client;
use Widget\ActionInterface;
use Widget\Options;
use Widget\Base\Contents;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Push extends Contents
{
  public function execute() {}

  public function insert(array $rows): int {
      $rows['type'] = 'say';
      $rows['authorId'] = 1;
      return parent::insert($rows);
  }

  public function asyncProcess($source, $target){
    $pluginOption = Options::alloc()->plugin('Say');
    switch ($pluginOption->staticStorage) {
      case 'local':
        # code...
        return -1;
      case 'qiniu':
        # code...
        return -1;
      case 'upyun':
        return $this->upyunAsyncProcess($source, $target, $this->getCallbackUrl());
    }
  }

  private function upyunAsyncProcess($source, $tasks, $callback){
    $pluginOption = Options::alloc()->plugin('Say');

    $bucketName = $pluginOption->upyunBucketName;
    $operator = $pluginOption->upyunOperator;
    $password = $pluginOption->upyunPassword;

		$uri = "/pretreatment/";
		$date = gmdate('D, d M Y H:i:s \G\M\T');
		$signature = base64_encode(hash_hmac("sha1", "POST&$uri&$date", md5("{$password}"), true));
		$header = array("Authorization:UPYUN {$operator}:$signature", "Date:$date");
		$tasks = base64_encode(json_encode($tasks));
		$formdata = "accept=json&service={$bucketName}&source=$source&notify_url=$callback&tasks=$tasks";
    
    $client = Client::get();
    $client->setMethod('POST');
    $client->setHeader('Authorization', "UPYUN $operator:$signature");
    $client->setHeader('Date', $date);
    $client->setData($formdata);

    $client->send('http://p0.api.upyun.com' . $uri);
    return $client->getResponseStatus();
  }

  public function spiderman($source, $target){
    $pluginOption = Options::alloc()->plugin('Say');
    switch ($pluginOption->staticStorage) {
      case 'local':
        # code...
        return -1;
      case 'qiniu':
        # code...
        return -1;
      case 'upyun':
        return $this->upyunSpiderman($source, $target, $this->getCallbackUrl());
    }
  }

  private function upyunSpiderman($source, $target, $callback){
    $pluginOption = Options::alloc()->plugin('Say');

    $bucketName = $pluginOption->upyunBucketName;
    $operator = $pluginOption->upyunOperator;
    $password = $pluginOption->upyunPassword;

    $uri = "/pretreatment/";
    $date = gmdate('D, d M Y H:i:s \G\M\T');
    $signature = base64_encode(hash_hmac("sha1", "POST&$uri&$date", md5("{$password}"), true));
    $header = array("Authorization:UPYUN {$operator}:$signature", "Date:$date");
    $tasks = base64_encode(json_encode(array(array('url' => $source, 'save_as' => $target))));
    $formdata = "service={$bucketName}&app_name=spiderman&notify_url=$callback&tasks=$tasks";

    $client = Client::get();
    $client->setMethod('POST');
    $client->setHeader('Authorization', "UPYUN $operator:$signature");
    $client->setHeader('Date', $date);
    $client->setData($formdata);

    $client->send('http://p0.api.upyun.com' . $uri);
    return $client->getResponseStatus();
  }

  public function getCallbackUrl(){
    if(strripos($this->options->siteUrl, '/') == strlen($this->options->siteUrl) - 1) {
      return $this->options->siteUrl . 'action/Say?push=&channel=callback';
    } else {
      return $this->options->siteUrl . '/action/Say?push=&channel=callback';
    }
  }

  public function getTargetPath(){
    $pluginOption = Options::alloc()->plugin('Say');
    $date = new Date();
    $savePath = preg_replace(array('/\{year\}/', '/\{month\}/', '/\{day\}/'), array($date->year, $date->month, $date->day), $pluginOption->savePath);
    return $savePath . sprintf('%u', crc32(uniqid(rand(10000, 99999)))) ;
  }

  /**
   * 请求文件进行压缩
   */
  public function tinyPngShrinkFile($file){
    return $this->tinyPngShrink($file, true);
  }

  /**
   * 请求URL进行压缩
   */
  public function tinyPngShrinkUrl($source){
    return $this->tinyPngShrink($source, false);
  }

  private function tinyPngShrink($source, $isFile = false){
    $pluginOption = Options::alloc()->plugin('Say');
    $tinyPNGKey = $pluginOption->tinyPNGKey;

    if($tinyPNGKey){
      $client = Client::get();
      $client->setMethod('POST');
      $client->setHeader('Authorization', "Basic " . base64_encode('api:' . $tinyPNGKey));
      $client->setHeader('Content-Type', "application/json");
      
      if($isFile){
        $client->setData(file_get_contents($source['tmp_name']));
      }else{
        $data = json_encode(array(
          "source" => array(
            "url" => strval($source)
          )
        ), 320);
        $client->setData($data);
      }
      $client->setTimeout(9000);
      $client->setOption(CURLOPT_SSL_VERIFYPEER, false);
      $client->setOption(CURLOPT_SSL_VERIFYHOST, true);
      $client->send('https://api.tinify.com/shrink');
      if($client->getResponseStatus() >= 200 && $client->getResponseStatus() < 299){
        return $client->getResponseHeader('location');
      }else{
        $this->log($client->getResponseBody());
        return $source;
      }
    }else{
      return $source;
    }
  }

  /**
   * 命令模式
   */
  public function commandMode($content){
    $command = explode(' ', $content);
    switch ($command[0]) {
      case '/help':
        return '帮助';
        break;
      case '/m':
      case '/merge':
        return '合并';
        break;
      default:
        return '';
        break;
    }
  }

  public function findNeteaseInfo($id){
    $url = "https://neteasecloudmusicapi-production-d640.up.railway.app/song/detail?ids={$id}&realIP=116.25.146.177";

    $client = Client::get();
    $client->setTimeout(9000);
    $client->setOption(CURLOPT_SSL_VERIFYPEER, false);
    $client->setOption(CURLOPT_SSL_VERIFYHOST, true);
    $client->send($url);
    $this->log('$client->getResponseStatus() : ' . $client->getResponseStatus());
    
    if($client->getResponseStatus() >= 200 && $client->getResponseStatus() < 299){
        $body = Json_decode($client->getResponseBody(), true);
        return $body['songs'][0];
    }else{
        // $this->log($client->getResponseBody());
      return null;
    }
  }

  public static function log($e) {
    $pluginOption = Options::alloc()->plugin('Say');
    $logger = $pluginOption->logger;

    if(!$logger){
      return;
    }

    $path = __TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__ . '/Say/Logs';
    if(!file_exists($path)){
      mkdir($path);
    }
    $fileName = $path . '/log' . date('Ymd') . '.txt';
    $str = "\n" . date('Y-m-d H:i:s') . "\n";
    file_put_contents($fileName, $str, FILE_APPEND);
    file_put_contents($fileName, $e, FILE_APPEND);
  }
}
?>
