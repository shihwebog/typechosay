<?php

namespace TypechoPlugin\Say\Widget\Contents;

use Typecho\Common;
use Typecho\Date as TypechoDate;
use Typecho\Db;
use Typecho\Db\Exception;
use Typecho\Widget\Helper\Form;
use Widget\ActionInterface;
use Widget\Notice;
use Widget\Base\Contents;
use Widget\Base\Metas;
use Utils\Helper;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 编辑碎语组件
 *
 * @property-read array|null $draft
 */
class Edit extends Contents implements ActionInterface
{
    /**
     * 入口函数
     */
    public function execute()
    {
    }

    /**
     * 生成表单
     *
     * @param string|null $action 表单动作
     * @return Form
     * @throws Exception
     */
    public function form(?string $action = null): Form
    {
        /** 构建表格 */
        $form = new Form($this->security->getIndex('/action/Say?say'), Form::POST_METHOD);

        /** 内容 */
        $text = new Form\Element\Textarea(
            'text',
            null,
            null,
            _t('碎语内容') . ' *',
            null,
        );
        $form->addInput($text);

        /** 状态 */
        $status = new Form\Element\Radio(
          'status',
          [
            'publish' => _t('公开'),
            'private' => _t('私密'),
            'hidden' => _t('隐藏')
          ],
          'publish',
          _t('碎语状态') . ' *',
          null,
        );
        $form->addInput($status);

        /** 链接动作 */
        $do = new Form\Element\Hidden('do');
        $form->addInput($do);

        /** 链接主键 */
        $cid = new Form\Element\Hidden('cid');
        $form->addInput($cid);

        /** 提交按钮 */
        $submit = new Form\Element\Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        if (isset($this->request->cid) && 'insert' != $action) {
            /** 更新模式 */
            $link = $this->db->fetchRow($this->select()
                ->where('cid = ?', $this->request->cid)
                ->limit(1));

            if (!$link) {
                $this->response->redirect(Helper::url('Say/Page/manage.php', $this->options->adminUrl));
            }

            $cid->value($link['cid']);
            $mid->value($link['mid']);
            $title->value($link['title']);
            $url->value($link['url']);
            $status->value($link['status']);
            $description->value($link['description']);
            $icon->value($link['icon']);
            $page->value($this->request->get('page', 1));

            $do->value('update');
            $submit->value(_t('编辑碎语'));
            $_action = 'update';
        } else {
            $do->value('publish');
            $submit->value(_t('增加碎语'));
            $_action = 'publish';
        }

        if (empty($action)) {
            $action = $_action;
        }

        /** 给表单增加规则 */
        if ('publish' == $action || 'update' == $action) {
            $text->addRule('required', _t('必须填写碎语内容'));
            $status->addRule('required', _t('必须选择碎语状态'));
        }

        return $form;
    }
    
    /**
     * 发布碎语
     */
    public function publishSay()
    {
        // if ($this->form('insert')->validate()) {
        //     $this->response->goBack();
        // }

        /** 取出数据 */
        $data = $this->request->from(
          'text',
          'status'
        );

        $contents['type'] = 'say';
        $contents['title'] = 'text';  //纯文本
        $contents['template'] = 'Typecho';
        $contents['status'] = $data['status'];
        $contents['created'] = TypechoDate::time();

        $text = array(
          'text' => $data['text']
        );
        $contents['text'] = json_encode($text, 320);

        // /** 插入数据 */
        $contents['cid'] = $this->insert($contents);

        // /** 设置高亮 */
        Notice::alloc()->highlight($this->theId);

        /** 提示信息 */
        Notice::alloc()->set(
            _t('碎语已经被增加'),
            'success'
        );

        /** 转向原页 */
        $this->response->redirect(Helper::url('Say/Page/manage.php'), $this->options->adminUrl);
    }

    /**
     * 标记碎语（公开、私密、隐藏）
     *
     * @throws Exception
     */
    public function markSay()
    {
        $cids = $this->request->filter('int')->getArray('cid');
        $status = $this->request->get('status');
        $statusList = [
          'publish' => _t('公开'),
          'private' => _t('私密'),
          'hidden'  => _t('隐藏')
        ];
        $updateCount = 0;

        if ($cids && is_array($cids)) {
            foreach ($cids as $cid) {
                $condition = $this->db->sql()->where('cid = ?', $cid);
                $this->db->query($condition->update('table.contents')->rows(['status' => $status]));
                $updateCount++;
            }
        }

        /** 提示信息 */
        Notice::alloc()->set(
            $updateCount > 0 ? _t('碎语已经被标记为' . $statusList[$status]) : _t('没有碎语被标记为' . $statusList[$status]),
            $updateCount > 0 ? 'success' : 'notice'
        );

        /** 转向原页 */
        $this->response->goBack();
    }

    /**
     * 删除碎语
     *
     * @throws Exception
     */
    public function deleteSay()
    {
        $cids = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        if ($cids && is_array($cids)) {
            foreach ($cids as $cid) {
                $condition = $this->db->sql()->where('cid = ?', $cid);

                if ($this->delete($condition)) {
                    $deleteCount++;
                }
            }
        }

        /** 提示信息 */
        Notice::alloc()->set(
            $deleteCount > 0 ? _t('碎语已经被删除') : _t('没有碎语被删除'),
            $deleteCount > 0 ? 'success' : 'notice'
        );

        /** 转向原页 */
        $this->response->goBack();
    }

    /**
     * 合并碎语
     */
    public function mergeSay()
    {
        $cids = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        $rows = [];
        if ($cids && is_array($cids)) {
            $select = $this->select();
            foreach ($cids as $cid) {
              $select->orWhere('cid = ?', $cid);
            }
            $select->order('created', Db::SORT_ASC);
            $rows = $this->db->fetchAll($select);
        }

        if(count($rows) <= 0){
          Notice::alloc()->set(_t('没有碎语被合并'), 'notice');
        }else{
          $textCount = 0;
          $textpicCount = 0;
          $imageCount = 0;
          $locationCount = 0;
          $voiceCount = 0;
  
          foreach ($rows as $row) {
            switch ($row['title']) {
              case 'text':
                $textCount = ++$textCount;
                break;
              case 'textpic':
                $textpicCount = ++$textpicCount;
                break;
              case 'image':
                $imageCount = ++$imageCount;
                break;
              case 'location':
                $locationCount = ++$locationCount;
                break;
              case 'voice':
                $voiceCount = ++$voiceCount;
                break;
              default:
                break;
            }
          }

          
          $cid = null;  //合并后保留的数据
          $deleteSQL = $this->db->sql();  //合并后被删除的数据
          $newRows = null;
          /**
           * 允许一条text与一条及以上images合并
           * 允许一条textpic与一条及以上images合并
           * 允许一条text与一条location合并
           * 允许一条text与一条voice合并
           * 允许多条images合并
           */
          if($textCount == 1 && $textpicCount <= 0 && $imageCount >= 1 && $locationCount <= 0 && $voiceCount <=0){
            // 允许一条text与一条及以上images合并
            $text = '';
            $images = [];
            foreach ($rows as $row) {
              $content = json_decode($row['text'], true);
              if(!$cid) {
                $cid = $row['cid'];
              }else{
                $deleteSQL->orWhere('cid = ?', $row['cid']);
              }
              if($row['title'] == 'text'){
                $text = $content['text'];
              }else if($row['title'] == 'image'){
                $images = array_merge($images, $content['images']);
              }
            }

            $newContent = [
              'text' => $text,
              'images' => $images,
            ];
            $newRows = [
              'title' => 'textpic',
              'text' => json_encode($newContent, 320)
            ];
          }else if($textCount <= 0 && $textpicCount == 1 && $imageCount >= 1 && $locationCount <= 0 && $voiceCount <=0){
            //允许一条textpic与一条及以上images合并
            $text = '';
            $images = [];
            foreach ($rows as $row) {
              $content = json_decode($row['text'], true);
              if(!$cid) {
                $cid = $row['cid'];
              }else{
                $deleteSQL->orWhere('cid = ?', $row['cid']);
              }
              if($row['title'] == 'textpic'){
                $text = $content['text'];
                $images = array_merge($images, $content['images']);
              }else if($row['title'] == 'image'){
                $images = array_merge($images, $content['images']);
              }
            }

            $newContent = [
              'text' => $text,
              'images' => $images,
            ];
            $newRows = [
              'title' => 'textpic',
              'text' => json_encode($newContent, 320)
            ];
          }else if($textCount == 1 && $textpicCount <= 0 && $imageCount <= 0 && $locationCount == 1 && $voiceCount <=0){
            //允许一条text与一条location合并
            $text = '';
            foreach ($rows as $row) {
              $content = json_decode($row['text'], true);
              if(!$cid) {
                $cid = $row['cid'];
              }else{
                $deleteSQL->orWhere('cid = ?', $row['cid']);
              }
              if($row['title'] == 'text'){
                $text = $content['text'];
              }else if($row['title'] == 'location'){
                $newContent = $content;
              }
            }

            $newContent['text'] = $text;
            $newRows = [
              'title' => 'location',
              'text' => json_encode($newContent, 320)
            ];
          }else if($textCount == 1 && $textpicCount <= 0 && $imageCount <= 0 && $locationCount <= 0 && $voiceCount == 1){
            //允许一条text与一条voice合并
            $text = '';
            foreach ($rows as $row) {
              $content = json_decode($row['text'], true);
              if(!$cid) {
                $cid = $row['cid'];
              }else{
                $deleteSQL->orWhere('cid = ?', $row['cid']);
              }
              if($row['type'] == 'text'){
                $text = $content['text'];
              }else if($row['type'] == 'voice'){
                $newContent = $content;
              }
            }

            $newContent['text'] = $text;
            $newRows = [
              'title' => 'voice',
              'text' => json_encode($newContent, 320)
            ];
          }else if($textCount <= 0 && $textpicCount <= 0 && $imageCount >= 2 && $locationCount <= 0 && $voiceCount <=0){
            //允许多条images合并
            $images = [];
            foreach ($rows as $row) {
              $content = json_decode($row['text'], true);
              if(!$cid) {
                $cid = $row['cid'];
              }else{
                $deleteSQL->orWhere('cid = ?', $row['cid']);
              }
              $images = array_merge($images, $content['images']);
            }

            $newContent['images'] = $images;
            $newRows = [
              'title' => 'image',
              'text' => json_encode($newContent, 320)
            ];
          }

          if($cid){
            $this->update($newRows, $this->db->sql()->where('cid = ?', $cid));
            $this->delete($deleteSQL);

            Notice::alloc()->highlight('say-' . $cid);
            Notice::alloc()->set(_t('碎语已经被合并'), 'success');
          }else{
            Notice::alloc()->set(_t('没有碎语被合并，请查看合并规则'), 'notice');
          }
        }
        /** 转向原页 */
        $this->response->goBack();
    }

    /**
     * 入口函数,绑定事件
     *
     * @access public
     * @return void
     * @throws Exception
     */
    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=publish'))->publishSay();
        $this->on($this->request->is('do=delete'))->deleteSay();
        $this->on($this->request->is('do=mark'))->markSay();
        $this->on($this->request->is('do=merge'))->mergeSay();
        
        $this->response->redirect($this->options->adminUrl);
    }
}
