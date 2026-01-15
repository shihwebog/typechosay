<?php

namespace TypechoPlugin\Say;

use Typecho\Db;
use Typecho\Widget;
use Typecho\Plugin as Typecho_Plugin;
use Typecho\Plugin\PluginInterface;
use Typecho\Plugin\Exception;
use Typecho\Router;
use Typecho\Widget\Helper\Layout;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Typecho\Widget\Helper\Form\Element\Radio;
use Typecho\Widget\Helper\Form\Element\Checkbox;
use Typecho\Widget\Helper\Form\Element\Textarea;
use Widget\Archive;
use Widget\Base\Contents;
use Widget\Options;
use Utils\Helper;

/**
 * 碎语，支持多种消息类型的微博客插件。
 * 
 * @package Say
 * @author 冰剑
 * @version 1.1.0
 * @link https://digu.plus
 */
class Plugin implements PluginInterface
{
    public static function activate()
    {
        Helper::addAction('Say', 'Say_Action');
        Helper::addRoute('say', '/says/', 'Widget_Archive', 'render', 'feed');
        Helper::addRoute('say_page', '/says/[page:digital]/', 'Widget_Archive', 'render', 'feed');
        Helper::addRoute('say_tag', '/says/tag/[keywords]/', 'Widget_Archive', 'render', 'feed');
        Helper::addRoute('say_tag_page', '/says/tag/[keywords]/[page:digital]/', 'Widget_Archive', 'render', 'feed');
        Helper::addPanel(2, 'Say/Page/write.php', '撰写碎语', '撰写新碎语', 'administrator');
        Helper::addPanel(2, 'Say/Page/write.php&dcid=', '编辑碎语', '编辑碎语', 'administrator', true);
        Helper::addPanel(3, 'Say/Page/manage.php', '碎语', '管理碎语', 'administrator');
        Archive::pluginHandle()->handle = __CLASS__ . '::handle';
        Contents::pluginHandle()->filter = __CLASS__ . '::filter';
    }

    public static function deactivate() {
        if (Options::alloc()->plugin('Say')->clear) {
            $db = Db::get();
            if("Pdo_Mysql" === $db->getAdapterName() || "Mysql" === $db->getAdapterName()){
                $db->query("delete from " . $db->getPrefix() . "contents where type = 'say'");
            }
        }

        Helper::removePanel(2, 'Say/Page/write.php');
        Helper::removePanel(2, 'Say/Page/write.php&dcid=');
        Helper::removePanel(3, 'Say/Page/manage.php');
        Helper::removeRoute('say_tag_page');
        Helper::removeRoute('say_tag');
        Helper::removeRoute('say_page');
        Helper::removeRoute('say');
        Helper::removeAction('Say');
    }

    public static function handle($type, $archive, $select) {
      $archive->parameter->pageSize = 10;
      $archive->currentPage = $archive->request->filter('int')->page ?? 1;
      $archive->setArchiveSlug('says');
      $archive->setArchiveType('say');
      $select->where('table.contents.type = ?', 'say');
      if($type == 'say' || $type == 'say_page'){
        $archive->setArchiveTitle('碎语');
      }else if($type == 'say_tag' || $type == 'say_tag_page'){
        $keywords = $archive->request->filter('url', 'search')->keywords;
        $archive->setArchiveTitle(sprintf('标签 %s 的碎语', $keywords));
        $archive->setPageRow(['keywords' => urlencode($keywords)]);
        $archive->setArchiveUrl(Router::url($type, ['keywords' => urlencode($keywords)], $archive->options->index));
        
        $select->where('table.contents.text like ?', '%#' . $keywords . ' %');
      }
      return false;
    }

    public static function filter($value, $content){
      $option = Helper::options();
      $pluginOption = $option->plugin('Say');
      $content = json_decode($value['text'], true);
      $text = $content['text'];
      $html = $text;

      if (preg_match_all("/#(.)+?(\s|$)/", $text, $matches)){
        $keywords = [];
        
        foreach($matches[0] as $key => $val) {
          $keywords[$key] = substr(trim($val), 1);
        }
        $keywords = array_unique($keywords);

        foreach($keywords as $key => $val) {
          $url = Router::url(
            'say_tag', 
            ['keywords' => urlencode($val)], 
            $option->index
          );

          $aTag = "<a href=\"$url\">#$val</a>";
          $html = str_ireplace('#' . $val, $aTag, $html);
        }
      }

      $value['source'] = $value['template'];
      $value['domain'] = $pluginOption->staticDomain;

      switch ($value['title']) {
        // 文本消息
        case 'text':
          $value['text'] = $text;
          $value['html'] = $html;
          break;
        // 图片消息
        case 'image':
          $value['text'] = $text ?? '';
          $value['images'] = $content['images'];
          break;
        // 图文消息
        case 'textpic':
          $value['text'] = $text ?? '';
          $value['html'] = $html;
          $value['images'] = $content['images'];
          break;
        // 语音消息
        case 'voice':
          $value['text'] = $text ?? '';
          $value['html'] = $html;
          $value['voice'] = $content['voice'];
          break;
        // 位置消息
        case 'location':
          $value['text'] = $text ?? '';
          $value['html'] = $html ?? '';
          $value['x'] = $content['x'];
          $value['y'] = $content['y'];
          $value['zoom'] = $content['zoom'];
          $value['label'] = $content['label'] ?? '';

          $srcFormat = '//restapi.amap.com/v3/staticmap?key=%s&scale=%s&location=%s,%s&zoom=%s';
          $src = sprintf($srcFormat,
          $pluginOption->amapKey,
          $pluginOption->amapScale,
          $value['y'], $value['x'], $value['zoom']);
          $value['src'] = $src;
          break;
        // 链接消息
        case 'link':
          $value['name'] = $content['title'];
          $value['description'] = $content['description'];
          $value['url'] = $content['url'];
          $value['cover'] = $content['cover'];
          break;
        case 'music':
          $value['text'] = $text ?? '';
          $value['html'] = $html ?? '';
          $value['form'] = $content['form'];
          $value['url'] = $content['url'];
          $value['id'] = $content['id'];
          $value['name'] = $content['name'];
          $value['artist'] = $content['artist'];
          $value['cover'] = $content['cover'];
          break;
      }

      return $value;
    }

    public static function config(Form $form) {
      $layout = new Layout();
      $layout->html(_t('<h3>基础配置</h3>'));
      $form->addItem($layout);

      $listSize = new Text('listSize', NULL, 10, _t('碎语列表数目'), _t(''));
      $form->addInput($listSize);

      $staticStorage = new Radio('staticStorage',
      [/*'local' => _t('本地'), 'qiniu' => _t('七牛云'), */'upyun' => _t('又拍云')],
      'upyun', _t('静态资源存储'), _t('静态资源（图片、语音）保存的位置。
      <br />七牛云免费资源：存储空间10G、CDN流量 HTTP 10G；[<a target="_blank" href="https://s.qiniu.com/fm6Bbm">点击申请</a>]
      <br />又拍云免费资源：存储空间10G、CDN流量 HTTP&HTTPS 15G；[<a target="_blank" href="https://console.upyun.com/register/?invite=NgDOtWKWX">点击申请</a>]'));
      $form->addInput($staticStorage);

      $staticDomain = new Text('staticDomain', NULL, '',
        'CDN 加速域名', '静态资源图片域名，最后不带"/"');
      $form->addInput($staticDomain);

      $savePath = new Text('savePath', null, '/says/{year}/{month}/', _t('保存路径'), _t('附件保存的路径，可选参数：{year} 年份、{month} 月份、{day} 日期'));
      $form->addInput($savePath);

      $tinyPNGKey = new Text('tinyPNGKey', null, '', _t('TinyPNG Key'), _t('为了节省空间及流量，将会对图片进行在线压缩后再进行存储，不填写则不会进行压缩。[<a target="_blank" href="https://tinify.cn/developers">点击申请</a>]' . (!function_exists('pcntl_fork') ? '<br /><strong class="warning">当前PHP环境不支持 pcntl_fork 函数，会出现图片多次发送的情况。</strong>' : '')));
      $form->addInput($tinyPNGKey);

      $apiKey = new Text('apiKey', NULL, NULL, _t('API Key'), _t('用于Chrome、Firefox插件发送消息，建议使用<a target="_blank" href="https://www.uuidgenerator.net/">随机 UUID </a>作为 API Key。'));
      $form->addInput($apiKey);

      $logger = new Radio('logger', [1 => _t('是'), 0 => _t('否')], 0, _t('记录日志'), _t('建议在需要调试问题的时候开启，平常请保持关闭状态。'));
      $form->addInput($logger);

      $clear = new Radio('clear', [1 => _t('是'), 0 => _t('否')], 0, _t('删除数据'), _t('禁用本插件时，是否删除插件产生的所有数据。<strong class="warning">请慎选，一切后果自负。</strong>'));
      $form->addInput($clear);

      $layout = new Layout();
      $layout->html(_t('<h3>企业微信配置</h3>'));
      $form->addItem($layout);

      $wxwCorpId = new Text('wxwCorpId', NULL, '', _t('企业ID'), _t(''));
      $form->addInput($wxwCorpId);

      $wxwCorpSecret = new Text('wxwCorpSecret', NULL, '', _t('应用Secret'), _t(''));
      $form->addInput($wxwCorpSecret);

      $wxwToken = new Text('wxwToken', NULL, '', _t('应用Token'), _t(''));
      $form->addInput($wxwToken);

      $wxwEncodingAESKey = new Text('wxwEncodingAESKey', NULL, '', _t('应用EncodingAESKey'), _t(''));
      $form->addInput($wxwEncodingAESKey);

      $wxwEmoji = new Textarea('wxwEmoji', null, null, _t('表情替换'), _t('因微信的表情会以文本形式传递过来，需要在这里进行替换配置后，才可以显示emoji'));
      $form->addInput($wxwEmoji);

      $layout = new Layout();
      $layout->html(_t('<h3>高德地图配置</h3>'));
      $form->addItem($layout);

      $amapKey = new Text('amapKey', NULL, '', _t('Key'), _t('请在<a href="https://lbs.amap.com/">高德开放平台</a>进行申请'));
      $form->addInput($amapKey);

      $amapScale = new Radio('amapScale',
        [1 => _t('普通'), 2 => _t('高清')],
        1, _t('清晰度'), _t('设置为高清图，图片高度和宽度都增加一倍')
      );
      $form->addInput($amapScale);

      $layout = new Layout();
      $layout->html(_t('<h3>又拍云配置</h3>'));
      $form->addItem($layout);

      $upyunBucketName = new Text('upyunBucketName', NULL, '', _t('服务名称'), _t(''));
      $form->addInput($upyunBucketName);

      $upyunOperator = new Text('upyunOperator', NULL, '', _t('操作员名称'), _t(''));
      $form->addInput($upyunOperator);

      $upyunPassword = new Text('upyunPassword', NULL, '', _t('操作员密码'), _t(''));
      $form->addInput($upyunPassword);

      return $form;
    }
    
    public static function personalConfig(Form $form){}
}