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

class WxWork extends Push implements ActionInterface
{
    private $source = '微信';

    public function execute(){}

    /**
     * 文本消息
     */
    private function typeText($data){
      $pluginOption = $this->options->plugin('Say');

      $emojis = explode(PHP_EOL, $pluginOption->wxwEmoji);

      $text = trim($data->Content);
      foreach($emojis as $val) {
        $emoji = explode('=', $val);
        if(count($emoji)){
          $text = str_replace(trim($emoji[0]), trim($emoji[1]), $text);
        }
      }

      $content = array(
        'text' => strval($text)
      );

      $say['title'] = 'text';
      $say['template'] = $this->source;
      $say['status'] = 'publish';

      // 匹配网易云音乐
      if (preg_match("/http[s]?:\/\/music\.163\.com\/song\?id=([0-9]+)[&userid=[0-9]+]?/i", $text, $matches)){
        $say['title'] = 'music'; //音乐类型
        $content['text'] = trim(str_replace($matches[0], "", $text));
        $music = $this->findNeteaseInfo($matches[1]);
        if($music){
          $artist = '';
          foreach ($music['ar'] as $ar) {
            $artist = $artist . $ar['name'] . ';';
          }
          $artist = substr($artist, 0, strlen($artist) - 1);

          $content = array_merge($content, array(
            'form' => "netease",
            'url' => "//music.163.com/song?id=" . $matches[1],
            'id' => $matches[1],
            'name' => $music['name'],
            'artist' => $artist,
            'cover' => $music['al']['picUrl']
          ));
        }
      }
      
      $say['text'] = json_encode($content, 320);
      return $this->insert($say);
    }

    /**
     * 图片消息
     */
    private function typeImage($data){
      $say['title'] = 'image';
      $say['template'] = $this->source;
      $say['status'] = 'publish';

      $target = $this->getTargetPath();

      $content = array(
        'images' => array(
          "$target.jpg"
        )
      );
      $say['text'] = json_encode($content, 320);

      $pid = -1;
      if(function_exists('pcntl_fork')){
        $pid = pcntl_fork();
      }
      
      if($pid > 0){
        return $this->insert($say);
      }elseif($pid == 0){
        // 子进程
        $shrink = $this->tinyPngShrinkUrl($data->PicUrl);

        // 抓取图片
        $this->spiderman(
          "$shrink",
          "$target.jpg"
        );
        exit(0);
      }else{
        // 主进程
        $shrink = $this->tinyPngShrinkUrl($data->PicUrl);
        $this->log($shrink);

        // 抓取图片
        $this->spiderman(
          "$shrink",
          "$target.jpg"
        );
        return $this->insert($say);
      }
    }

    /**
     * 语音消息
     */
    private function typeVoice($data){
      $say['title'] = 'voice';
      $say['template'] = $this->source;
      $say['status'] = 'publish';

      $accessToken = $this->getAccessToken();
      $mediaId = $data->MediaId;
      $mediaUrl = "https://qyapi.weixin.qq.com/cgi-bin/media/get?access_token=$accessToken&media_id=$mediaId";
      $target = $this->getTargetPath();
      
      // 抓取音频
      if($this->spiderman(
        "$mediaUrl",
        "$target.amr"
      ) < 200 )
      {
        return 0;
      }

      $tasks = array(
        array(
          'type' => 'audio',
          'avopts' => '',
          'save_as' => "$target.mp3"
        )
      );

      if($this->asyncProcess(
        "$target.amr",
        $tasks
      ) < 200 ) {
        return 0;
      }

      $content = array(
        'voice' => array(
          'mp3' => "$target.mp3",
          'amr' => "$target.amr",
        )
      );

      $say['text'] = json_encode($content, 320);
      return $this->insert($say);
    }

    /**
     * 位置消息
     */
    private function typeLocation($data){
      $say['title'] = 'location';
      $say['template'] = $this->source;
      $say['status'] = 'publish';

      // 腾讯与高德均采用GCJ-02坐标系，坐标无需转换
      $content = array(
        'x' => strval($data->Location_X),
        'y' => strval($data->Location_Y),
        'zoom' => strval($data->Scale) - 2,  //腾讯与高德地图缩放级别不一致，大致相差2个级别
        'label' => strval($data->Label),
      );
      $say['text'] = json_encode($content, 320);
      return $this->insert($say);
    }

    /**
     * 链接消息
     */
    private function typeLink($data){
      $say['title'] = 'link';
      $say['template'] = $this->source;
      $say['status'] = 'publish';

      $target = $this->getTargetPath();

      if($this->spiderman(
        "$data->PicUrl",
        "$target"
      ) < 200 ) {
        return 0;
      }

      $content = array(
        'title' => strval($data->Title),
        'description' => strval($data->Description),
        'url' => strval($data->Url),
        'cover' => $target,
      );

      $say['text'] = json_encode($content, 320);
      return $this->insert($say);
    }

    private function replyMessage($data, $content){
      $format = '<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName> 
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>';
      return sprintf($format, $data->FromUserName, $data->ToUserName, time(), $content);
    }

    private function getAccessToken(){
      $pluginOption = $this->options->plugin('Say');
      $client = Client::get();
      $client->setQuery([
        'corpid' => $pluginOption->wxwCorpId,
        'corpsecret' => $pluginOption->wxwCorpSecret,
      ]);
      $client->setOption(CURLOPT_SSL_VERIFYPEER, false);
      $client->setOption(CURLOPT_SSL_VERIFYHOST, true);
      $client->send('https://qyapi.weixin.qq.com/cgi-bin/gettoken');
      $response = $client->getResponseBody();
      $body = json_decode($response, true);
      return $body['access_token'];
    }

    public function action(){
      try{
        $param = $this->request->from(
          'msg_signature',
          'timestamp',
          'nonce',
          'echostr'
        );
        $pluginOption = $this->options->plugin('Say');
        $crypt = new WxWorkCallbackCrypto($pluginOption->wxwToken, $pluginOption->wxwEncodingAESKey, $pluginOption->wxwCorpId);
        if($this->request->isGet()){
          $text = $crypt->verifyURL($param['msg_signature'], $param['timestamp'], $param['nonce'], $param['echostr']);
          $this->response->throwContent($text);
        }else if($this->request->isPost()){
          $requestData = file_get_contents('php://input');
          $text = $crypt->decryptMsg($param['msg_signature'], $param['timestamp'], $param['nonce'], $requestData);
          $data = simplexml_load_string($text, 'SimpleXMLElement', LIBXML_NOCDATA);
          $cid = '';
          $this->log("$data->MsgType -> " . $text);
          $this->log(substr(trim($data->Content), 0, 1));
          if($data->MsgType == 'text' && substr(trim($data->Content), 0, 1) == '/'){
            $replyContent = $this->commandMode(trim($data->Content));
          } else if(in_array($data->MsgType, ['text', 'image', 'voice', 'location', 'link'])){
            switch ($data->MsgType) {
              case 'text':
                $cid = $this->typeText($data);
                break;
              case 'image':
                $cid = $this->typeImage($data);
                break;
              case 'voice':
                $cid = $this->typeVoice($data);
                break;
              case 'location':
                $cid = $this->typeLocation($data);
                break;
              case 'link':
                $cid = $this->typeLink($data);
                break;
              default:
                break;
            }
            $replyContent = $cid ? '[烟花] 碎语发送成功，ID是 ' . $cid : '[心碎] 碎语发送失败';
          } else {
            $replyContent = '[奸笑] 消息类型不支持或未开启';
          }
          $replyMessage = $this->replyMessage($data, $replyContent);
          $encryptMsg = $crypt->encryptMsg($replyMessage, time(), Common::randString(32));
          $this->response->throwContent($encryptMsg, 'text/xml');
        }
      } catch (Exception $e) {
        $this->log($e);
      }
    }
}
