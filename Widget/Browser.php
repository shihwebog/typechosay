<?php

namespace TypechoPlugin\Say\Widget\Push;

use Typecho\Common;
use Typecho\Date as TypechoDate;
use Typecho\Db;
use Typecho\Db\Exception;
use Typecho\Http\Client;
use Typecho\Widget\Helper\Form;
use Widget\ActionInterface;
use Widget\Notice;
use Widget\Base\Metas;
use Utils\Helper;
use TypechoPlugin\Say\Widget\Base\Push;
use TypechoPlugin\Say\Lib\WxWorkCallbackCrypto;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Browser extends Push implements ActionInterface
{
    public function execute(){}

    public function action(){
      try{
        $param = $this->request->from(
          'apiKey',
          'text',
          'channel',
          'x',
          'y',
          'address'
        );
        $this->response->setHeader("Access-Control-Allow-Origin", "*");
        $pluginOption = $this->options->plugin('Say');
        if($param['apiKey'] !== $pluginOption->apiKey){
          $this->response->throwJson(['code' => 1, 'message' => _t('API KEY 校验失败')]);
        }
        // 如果是校验，则返回校验结果。
        if($this->request->is('auth')){
          $this->response->throwJson([
            'code' => 0, 
            'message' => _t('success'),
            'data' => [
              'amapKey' => $pluginOption->amapKey,
              'tinyPNGKey' => $pluginOption->tinyPNGKey
            ]
          ]);
        }
        
        switch ($param['channel']) {
          case 'chrome':
            $say['template'] = 'Chrome';
            break;
          case 'firefox':
            $say['template'] = 'Firefox';
            break;
          default:
            $say['template'] = 'unkown';
            break;
        }
        $say['status'] = 'publish';
        
        $text = array();
        if($param['text']){
          // 文字：有文字存在
          $say['title'] = 'text';
          $text['text'] = strval($param['text']);
        }

        if (!empty($_FILES)) {
          // 图片：有图片存在
          $say['title'] = 'image';
          // 存在图片，提前生成命名
          $target = $this->getTargetPath();
          $text['images'] = array(
            "$target.jpg"
          );

          $pid = -1;
          if(function_exists('pcntl_fork')){
            $pid = pcntl_fork();
          }

          if($pid > 0){ //父进程
          }elseif($pid == 0){// 子进程
            $shrink = $this->tinyPngShrinkFile($_FILES["image"]);
    
            // 抓取图片
            $this->spiderman(
              "$shrink",
              "$target.jpg"
            );
            //子进程
            exit(0);
          }else{// 出错
            $shrink = $this->tinyPngShrinkFile($_FILES["image"]);
    
            // 抓取图片
            $this->spiderman(
              "$shrink",
              "$target.jpg"
            );
          }
        }else if($param['x'] && $param['y']){
          // 位置：有坐标系参数
          $say['title'] = 'location';
          $text['x'] = $param['x'];
          $text['y'] = $param['y'];
          $text['label'] = $param['address'];
          $text['zoom'] = 13;
        }

        // 图文：有文字且有图片
        if($param['text'] && !empty($_FILES)){
          $say['title'] = 'textpic';
        }
        
        $say['text'] = json_encode($text, 320);

        if($this->insert($say)){
          $this->response->throwJson([
            'code' => 0, 
            'message' => _t('success')
          ]);
        }else{
          $this->response->throwJson([
            'code' => 1, 
            'message' => _t('fail')
          ]);
        }
      } catch (Exception $e) {
        $this->log($e);
      }
    }
}
