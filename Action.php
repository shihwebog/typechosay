<?php
namespace TypechoPlugin\Say;

use Typecho\Widget;
use Widget\ActionInterface;


class Action extends Widget implements ActionInterface
{

    public function execute() {}

    public function action(){
      if($this->request->is('say')){  //插件设置业务
          Widget::widget("TypechoPlugin\Say\Widget\Contents\Edit")->action();
      }else if($this->request->is('push')){
          if($this->request->is('channel=ding')){
            Widget::widget("TypechoPlugin\Say\Widget\Push\Ding")->action();
          }else if($this->request->is('channel=wxwork')){
            Widget::widget("TypechoPlugin\Say\Widget\Push\WxWork")->action();
          }else if($this->request->is('channel=chrome') || $this->request->is('channel=firefox')){
            Widget::widget("TypechoPlugin\Say\Widget\Push\Browser")->action();
          }else if($this->request->is('channel=test')){
            Widget::widget("TypechoPlugin\Say\Widget\Push\Test")->action();
          }
      }else if($this->request->is('auth')){
        Widget::widget("TypechoPlugin\Say\Widget\Push\Browser")->action();
      }
    }
}
?>