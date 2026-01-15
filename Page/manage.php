<?php
include 'common.php';
include 'header.php';
include 'menu.php';

\TypechoPlugin\Say\Widget\Contents\Admin::alloc()->to($says);
$writeUrl = Utils\Helper::url("Say/Page/write.php");
$saysUrl = Utils\Helper::url("Say/Page/manage.php");
?>
<style>
.comment-content.link img,
.comment-content.textpic img,
.comment-content.image img{
height: 64px;
width: 64px;
margin-right: .5em;
}
.comment-content.location{
position: relative;
}
.comment-content.location img{
display: block;
}
.comment-content.location a{
bottom: 0;
left: 0;
background: rgba(41,45,51,0.75);
color: #FFF;
position: absolute;
line-height: 1.875;
padding: 0px 8px;
text-decoration: none;
-moz-transition: all .2s ease-in-out;
-o-transition: all .2s ease-in-out;
-webkit-transition: all .2s ease-in-out;
-ms-transition: all .2s ease-in-out;
transition: all .2s ease-in-out
}
.comment-content.location a:hover{
background: rgba(41,45,51,1);
}
.comment-content.link a,
.comment-content.music a{
  display: flex;
  margin: 1em 0;
  text-decoration: none;
}
.comment-content.music a{
  max-width: 320px;
}
.comment-content.link div.cover,
.comment-content.music div.cover{
  display: flex;
  margin-right: 8px;
  position: relative;
}
.comment-content.link div.cover img:not(icon){
  width: 64px;
  height: 64px;
}
.comment-content.music div.cover img:not(icon){
  width: 48px;
  height: 48px;
}
.comment-content.music div.cover span{
  position: absolute;
  width: 16px;
  height: 16px;
  bottom: 1px;
  right: 1px;
}
.comment-content.music div.cover span.netease{
  background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAAXNSR0IArs4c6QAAAYtJREFUOE+lkz9WwkAQh78NnRbGDkkhvKQXbwAnEE4g3gBPIJ4AOIHxBMoJ0BOAPXmhIdpJY0nGN5BFwp9H4Vb7dne++c3Mbw3/XOZY/ASvZaALuCBjh7RZ4Wtq43YAMa6bctIVcAOSZoQ3B5mDeQVagrQCEt0vVw4Q41VTGGo2QQYBSSPCE7vfp3YNiCmWUwojhTosGhW+3lZnzhCMC4QOi4Geb4LWgAml0GBuHRZ1fZTV/rSbVUKf5C5Xwqru028rNVMTAx8CPQMK6gtSNpgbkEefpLPuQUyxllIYQnrv89mLKHXAPFg12kiBccCsFuGNgSuHn/MK8/myBAv4C7AArivMxhO8Zd0KsKXZtzmAlZZNY6RzB6PBbUGeA5LWdrJ1EzOZo4BZXbNtNlHgvcBPQyVHXLTB6eYUrAJWUxC4C5iF+2aemWw5ap+knDNSNgm16BmkbZ/P/iYk88QLmKogTevGfU7Ums9AFKb7qUDVYBoK3FZ46C+01fdgLq0KbWKBNDzoxGO/8tD9Lw4cxBEx+tVjAAAAAElFTkSuQmCC");
}
.comment-content.music div.cover span.qq{
  background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAAXNSR0IArs4c6QAAAR1JREFUOE+lU01OwkAU/l5pXfcIcgKGpTEWOAH1BHIMI1100Rp3egPxBOIJinXhdjyBeAPcWtrPQFJi2mmBOLuZvPe99/2M4J9H9vWrt2AIcgygq73Yr9YbAVQSuuhk9wKZlA0En7QX7+7luxkgnc4EcrWbRn7T5lCf3+rDNkgDX4Bngi8AFsidmR6FKxPdRg1UOp2D6OlB3G3TqRkgCV2xsiUhD3oQhU0grS5sHJCCc57kSp/dLY+iUBar1yCEoKe96PIoAPV+fYofewwLrhAhBSN9ES0OdOFmIrAe/xaT1HoQ9/cCbCZLZn+a1mWn6FezUBNxKxyRGAEMNOoAjRvwi7mjqoFqiHJNgw86a99kZWuQYK8VrGJl+gMlxV86kHMRdEfXDQAAAABJRU5ErkJggg==");
}
.comment-content.link div.content,
.comment-content.music div.content{
  display: flex;
  flex-direction: column;
}
.comment-content.link div.content span.title,
.comment-content.music div.content span.title {
  flex: 1;
  display: flex;
  align-items: center;
}
.comment-content.link div.content span.description,
.comment-content.music div.content span.description{
  flex: 1;
  color: #999;
  font-size: .92857em;
  display: flex;
  align-items: center;
}
.comment-content.link div.content span.description {
  flex: 2;
}
</style>
<div class="main">
    <div class="body container">
        <div class="typecho-page-title">
            <h2><?php echo $menu->title; ?>
              <a href="<?php _e($writeUrl); ?>"><?php _e("新增") ?></a>
            </h2>
        </div>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12 typecho-list">
                <div class="clearfix">
                    <!-- <ul class="typecho-option-tabs right">
                        <li class="current"><a href="#"><?php _e('所有'); ?></a></li>
                    </ul> -->
                    <ul class="typecho-option-tabs">
                        <li<?php if(!isset($request->status) || 'publish' == $request->get('status')): ?> class="current"<?php endif; ?>><a 
                        href="<?php _e($saysUrl); ?>"><?php _e('公开'); ?></a></li>
                        <li<?php if('private' == $request->get('status')): ?> class="current"<?php endif; ?>><a 
                        href="<?php _e($saysUrl . '&status=private'); ?>"><?php _e('私密'); ?></a></li>
                        <li<?php if('hidden' == $request->get('status')): ?> class="current"<?php endif; ?>><a 
                        href="<?php _e($saysUrl . '&status=hidden'); ?>"><?php _e('隐藏'); ?></a></li>
                    </ul>
                </div>

                <div class="typecho-list-operate clearfix">
                    <form method="get">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                            <div class="btn-group btn-drop">
                            <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                            <ul class="dropdown-menu">
                              <li><a href="<?php $security->index('/action/Say?say&do=mark&status=publish'); ?>"><?php _e('标记为公开'); ?></a></li>
                              <li><a href="<?php $security->index('/action/Say?say&do=mark&status=private'); ?>"><?php _e('标记为私密'); ?></a></li>
                              <li><a href="<?php $security->index('/action/Say?say&do=mark&status=hidden'); ?>"><?php _e('标记为隐藏'); ?></a></li>
                              <li><a lang="<?php _e('你确认要合并这些碎语吗?'); ?>" href="<?php $security->index('/action/Say?say&do=merge'); ?>"><?php _e('合并'); ?></a></li>
                              <li><a lang="<?php _e('你确认要删除这些碎语吗?'); ?>" href="<?php $security->index('/action/Say?say&do=delete'); ?>"><?php _e('删除'); ?></a></li>
                            </ul>
                            </div>
                        </div>
                        <div class="search" role="search">
                            <?php if ('' != $request->keywords): ?>
                                <a href="<?php _e($saysUrl . (isset($request->status) ? '&status=' . htmlspecialchars($request->get('status')) : '')); ?>"><?php _e('&laquo; 取消筛选'); ?></a>
                            <?php endif; ?>
                            <input type="text" class="text-s" placeholder="<?php _e('请输入关键字'); ?>" value="<?php echo htmlspecialchars($request->keywords ?? ''); ?>"<?php if ('' == $request->keywords): ?> onclick="value='';name='keywords';" <?php else: ?> name="keywords"<?php endif; ?>/>
                            <?php if(isset($request->status)): ?>
                                <input type="hidden" value="<?php echo htmlspecialchars($request->get('status')); ?>" name="status" />
                            <?php endif; ?>
                            <input type="hidden" value="<?php _e('Say/Page/manage.php'); ?>" name="panel" />
                            <button type="submit" class="btn btn-s"><?php _e('筛选'); ?></button>
                        </div>
                    </form>
                </div><!-- end .typecho-list-operate -->
                
                <form method="post" name="manage_comments" class="operate-form">
                <div class="typecho-table-wrap">
                    <table class="typecho-list-table">
                        <colgroup>
                            <col width="3%" class="kit-hidden-mb"/>
                            <col />
                        </colgroup>
                        <thead>
                            <tr class="nodrag">
                                <th class="kit-hidden-mb"> </th>
                                <th><?php _e('内容'); ?></th>
                            </tr>
                        </thead>
                        <tbody>

                        <?php if($says->have()): ?>
                        <?php while($says->next()): ?>
                        <tr id="<?php $says->theId(); ?>" class="nodrag" data-comment="">
                            <td valign="top" class="kit-hidden-mb">
                                <input type="checkbox" value="<?php $says->cid(); ?>" name="cid[]"/>
                            </td>
                            <td valign="top" class="comment-body">
                                <div class="comment-date"><?php $says->dateWord(); ?> 来源于 <?php $says->source(); ?></div>
                                <div class="comment-content <?php $says->title() ?>">
                                  <?php if(isset($says->text) && $says->text) : ?>
                                    <p><?php $says->text(); ?></p>
                                  <?php endif; ?>
                                  <?php if($says->title == 'textpic' || $says->title == 'image') : ?>
                                    <p>
                                      <?php foreach ($says->images as $image): ?>
                                          <img alt="" src="<?php echo $says->domain . $image; ?>" />
                                      <?php endforeach; ?>
                                    </p>
                                  <?php elseif($says->title == 'voice') : ?>
                                    <p>
                                      <audio controls>
                                        <source src="<?php echo $says->domain . $says->voice['mp3']; ?>" type="audio/mp3">
                                        <source src="<?php echo $says->domain . $says->voice['amr']; ?>" type="audio/amr">
                                        您的浏览器不支持 audio 元素。
                                      </audio>
                                    </p>
                                  <?php elseif($says->title == 'location') : ?>
                                    <p>
                                      <img src="<?php $says->src() ?>&size=480*160" title="<?php $says->label() ?>" style="max-height: 240px">
                                      <a href="//uri.amap.com/search?keyword=<?php $says->label() ?>&center=<?php $says->y() ?>,<?php $says->x() ?>&view=map&src=digu.plus&callnative=0" target="_blank"><?php $says->label() ?></a>
                                    </p>
                                  <?php elseif($says->title == 'link') : ?>
                                      <a target="_blank" href="<?php $says->url(); ?>">
                                        <div class="cover">
                                          <img alt="<?php $says->name(); ?>" src="<?php echo $says->domain . $says->cover; ?>" />
                                        </div>
                                        <div class="content">
                                          <span class="title"><?php $says->name(); ?></span>
                                          <span class="description"><?php $says->description(); ?></span>
                                        </div>
                                      </a>
                                  <?php elseif($says->title == 'music') : ?>
                                      <a target="_blank" href="<?php $says->url(); ?>">
                                        <div class="cover">
                                          <img alt="<?php $says->name(); ?>" src="<?php $says->cover(); ?>" />
                                          <span class="<?php $says->form(); ?>"></span>
                                        </div>
                                        <div class="content">
                                          <span class="title"><?php $says->name(); ?></span>
                                          <span class="description"><?php $says->artist(); ?></span>
                                        </div>
                                      </a>
                                  <?php endif; ?>
                                </div>
                                <div class="comment-action hidden-by-mouse">
                                    <?php if('publish' == $says->status): ?>
                                    <span class="weak"><?php _e('公开'); ?></span>
                                    <?php else: ?>
                                    <a href="<?php $security->index('/action/Say?say&do=mark&status=publish&cid=' . $says->cid); ?>" class="operate-public"><?php _e('公开'); ?></a>
                                    <?php endif; ?>
                                    
                                    <?php if('private' == $says->status): ?>
                                    <span class="weak"><?php _e('私密'); ?></span>
                                    <?php else: ?>
                                    <a href="<?php $security->index('/action/Say?say&do=mark&status=private&cid=' . $says->cid); ?>" class="operate-private"><?php _e('私密'); ?></a>
                                    <?php endif; ?>
                                    
                                    <?php if('hidden' == $says->status): ?>
                                    <span class="weak"><?php _e('隐藏'); ?></span>
                                    <?php else: ?>
                                    <a href="<?php $security->index('/action/Say?say&do=mark&status=hidden&cid=' . $says->cid); ?>" class="operate-hidden"><?php _e('隐藏'); ?></a>
                                    <?php endif; ?>
                                    
                                    <!-- <a href="#<?php $says->theId(); ?>" rel="<?php $security->index('/action/comments-edit?do=edit&coid='); ?>" class="operate-edit"><?php _e('编辑'); ?></a> -->

                                    <a lang="<?php _e('你确认要删除这条碎语吗?'); ?>" href="<?php $security->index('/action/Say?say&do=delete&cid=' . $says->cid); ?>" class="operate-delete"><?php _e('删除'); ?></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="4"><h6 class="typecho-list-table-title"><?php _e('没有碎语') ?></h6></td>
                        </tr>
                        <?php endif; ?>
                        </tbody>
                    </table><!-- end .typecho-list-table -->
                </div><!-- end .typecho-table-wrap -->

                <?php if(isset($request->cid)): ?>
                <input type="hidden" value="<?php echo htmlspecialchars($request->get('cid')); ?>" name="cid" />
                <?php endif; ?>
                </form><!-- end .operate-form -->

                <div class="typecho-list-operate clearfix">
                    <form method="get">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                            <div class="btn-group btn-drop">
                            <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                            <ul class="dropdown-menu">
                              <li><a href="<?php $security->index('/action/Say?say&do=mark&status=publish'); ?>"><?php _e('标记为公开'); ?></a></li>
                              <li><a href="<?php $security->index('/action/Say?say&do=mark&status=private'); ?>"><?php _e('标记为私密'); ?></a></li>
                              <li><a href="<?php $security->index('/action/Say?say&do=mark&status=hidden'); ?>"><?php _e('标记为隐藏'); ?></a></li>
                              <li><a lang="<?php _e('你确认要合并这些碎语吗?'); ?>" href="<?php $security->index('/action/Say?say&do=merge'); ?>"><?php _e('合并'); ?></a></li>
                              <li><a lang="<?php _e('你确认要删除这些碎语吗?'); ?>" href="<?php $security->index('/action/Say?say&do=delete'); ?>"><?php _e('删除'); ?></a></li>
                            </ul>
                            </div>
                        </div>
                        <?php if($says->have()): ?>
                        <ul class="typecho-pager">
                            <?php $says->pageNav(); ?>
                        </ul>
                        <?php endif; ?>
                    </form>
                </div><!-- end .typecho-list-operate -->
            </div><!-- end .typecho-list -->
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'table-js.php';
?>
<script type="text/javascript">
$(document).ready(function () {
  // 记住滚动条
  function rememberScroll () {
      $(window).bind('beforeunload', function () {
          $.cookie('__typecho_comments_scroll', $('body').scrollTop());
      });
  }
  $('.operate-delete').click(function () {
      var t = $(this), href = t.attr('href'), tr = t.parents('tr');

      if (confirm(t.attr('lang'))) {
          tr.fadeOut(function () {
              rememberScroll();
              window.location.href = href;
          });
      }
      return false;
  });
  

  $('.operate-approved, .operate-waiting, .operate-spam').click(function () {
      rememberScroll();
      window.location.href = $(this).attr('href');
      return false;
  });
});
</script>
<?php include 'footer.php'; ?>

