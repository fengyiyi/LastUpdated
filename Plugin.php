<?php
/**
 * 列出最近更新的文章
 * 
 * @package LastUpdated 
 * @author mufeng
 * @version 1.0.0
 * @update: 2012.08.22
 * @link http://mufeng.me
 */
class LastUpdated_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
		Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write = array('LastUpdated_Plugin', 'lastEdit');
		Typecho_Plugin::factory('Widget_Archive') ->header = array('LastUpdated_Plugin', 'headerScript');
		
		$db = Typecho_Db::get();
		$last = $db->fetchRow($db->select()->from('table.options')->where('`name` = ?', 'lastUpdated'));
		if($last==null){
			$args = array();
			$args = serialize($args);
			$db->query($db->insert('table.options')->rows(array('name' => 'lastUpdated', 'user' => 0, 'value' => $args)));
		}
		return "插件激活成功";
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
        $lastcss = new Typecho_Widget_Helper_Form_Element_Radio(
          'lastcss', array(0 => '自己处理', 1 => '随着本插件载入'), 1,
          '最近更新文章列表.CSS样式', '若选择 "随着本插件载入", 会自动加载插件内 lastUpdated.css 到 header().');
        $form->addInput($lastcss);
		
        $lastnumber = new Typecho_Widget_Helper_Form_Element_Text('lastnumber', NULL, '10', _t('文章数量, 默认最多10篇文章'));
        $form->addInput($lastnumber);
	}

    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 加入 header
     *
     * @access public
     * @return void
     */
    public static function headerScript()
	{
		$options = Typecho_Widget::widget('Widget_Options');
		
        /** 加入 lastUpdated.css **/
		if ($options->plugin('LastUpdated')->lastcss)
			echo "<link rel='stylesheet' type='text/css' href='", $options->pluginUrl, "/LastUpdated/lastUpdated.css' />\n";
	}
	
    /**
     * 更新数据
     *
     * 
     * @return void
     */
	public static function lastEdit($content)
	{	
		$db = Typecho_Db::get();
		Typecho_Widget::widget('Widget_Contents_Post_Edit')->to($post);
		$number = $options->plugin('LastUpdated')->lastnumber;
		$N = ($number!=null && $number>0) ? $number : 10;
		$cid = $post->cid;
		$options = Typecho_Widget::widget('Widget_Options');
		if($cid!=null && $cid>0){
			$lastUpdated = unserialize($options->lastUpdated);
			$new = array();
			$new['title'] = $post->title;
			$new['link'] = $post->permalink;
			$new['time'] = date('Y/m/d H:i:s',time()+60*60*8);
			$n = 0;
			foreach($lastUpdated as $val){
				if($new['title'] == $val['title']){
					unset($lastUpdated[$n]); 
					break;
				}
				$n++;
			}
			array_push($lastUpdated, $new);
			if(count($lastUpdated)>$N) unset($lastUpdated[0]);  
			$args = serialize($lastUpdated);
			$db->query($db->update('table.options')->rows(array('value' => $args))->where('name = ?', 'lastUpdated'));
		}
		return $content;
	}
	/**
     * 输出最新更新的文章
     *
     * 语法: LastUpdated_Plugin::lastUpdated();
     *
     * @access public
     * @return void
     */
    public function lastUpdated()
	{
		$options = Typecho_Widget::widget('Widget_Options');
		$number = $options->plugin('LastUpdated')->lastnumber;
		$N = ($number!=null && $number>0) ? $number : 10;
		$last = $options->lastUpdated;
		$last = unserialize($last);
		$cnt = count($last);
		if($cnt>0){
			$n=0;
			echo '<div id="lastUpdated"><h3>最近更新</h3><ul class="updateList">';
			foreach(array_reverse($last) AS $value){
				$n++;
				if($n>$N) break;
				echo '<li><a href="'.$value['link'].'" title="'.$value['title'].'">'.$value['title'].'</a><span class="modified">修改时间:'.$value['time'].'</span></li>';
			}
			echo '</ul></div>';
		}
	}
}
