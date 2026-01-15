<?php

namespace TypechoPlugin\Say\Widget\Contents;

use Typecho\Common;
use Typecho\Db;
use Typecho\Widget\Helper\PageNavigator\Box;
use Widget\Base\Contents;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 碎语管理组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Admin extends Contents
{
    private $countSql;
    private $total = false;
    private $currentPage;

    /**
     * 入口函数
     *
     * @throws Db\Exception
     */
    public function execute()
    {
      $select = $this->select();

      $this->parameter->setDefault('pageSize=10');
      $this->currentPage = $this->request->get('page', 1);

      $select->where('table.contents.type = ?', 'say');
      
      if (null != ($keywords = $this->request->filter('search')->keywords)) {
        $select->where('table.contents.text like ?', '%' . $keywords . '%');
      }

      if (in_array($this->request->status, ['publish', 'private', 'hidden'])) {
        $select->where('table.contents.status = ?', $this->request->status);
      } else {
        $select->where('table.contents.status = ?', 'publish');
      }

      $this->countSql = clone $select;

      $select->order('created', Db::SORT_DESC)
             ->page($this->currentPage, $this->parameter->pageSize);

      $this->db->fetchAll($select, [$this, 'push']);
    }
    
    public function pageNav()
    {
      $query = $this->request->makeUriByRequest('page={page}');

      /** 使用盒状分页 */
      $nav = new Box(
          false === $this->total ? $this->total = $this->size($this->countSql) : $this->total,
          $this->currentPage,
          $this->parameter->pageSize,
          $query
      );
      $nav->render('&laquo;', '&raquo;');
    }
    
}

