<?php

namespace TypechoPlugin\Say\Widget\Push;

use Typecho\Common;
use Typecho\Date as TypechoDate;
use Typecho\Db;
use Typecho\Db\Exception;
use Typecho\Widget\Helper\Form;
use Typecho\Http\Client as HttpClient;
use Say\Widget\Push\Push;
use Widget\ActionInterface;
use Widget\Notice;
use Widget\Base\Metas;
use Utils\Helper;
use TypechoPlugin\Say\Lib\City;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Test extends Push implements ActionInterface
{
    public function execute(){}

    public function action(){
      // echo $this->tinyPngShrinkUrl("https://wework.qpic.cn/wwpic/952549_Rp0yHNUXTWqfny8_1659972600/");
    }
}
