<?php
/**
* 点赞model
* @date: 2015-10-23
* @author: efan
* @version:1.0.0
*/
class DiggModel extends Model {
	
	/**
	 * 表前缀，如Wish心愿，Feed动态, Note笔记
	 * @var string
	 */
	private $pre='';
	
	//字段
	private $field;
	
	//M的实例
	private $m;
	
	/**
	 * @param  $table 评论表名,驼峰命名
	 * @param  $field_pre 字段前缀，如给feed_comment的feed_id,加入评论数据，则：FeedComment,feed
	 */
	public function __construct($table){
		$this->pre 	= $table;
		$this->m	= M($table.'Digg');
		$this->field= $table.'_id';
	}
	/**
	 * 动态点赞
	 * @param int $id 动态id
	 * @param int $digg_user_id 点赞的用户id
	 * @return 
	 */
	public function diggFeed($id,$digg_user_id) {
		//取发动态的原用户id
		$ori_user_id=M($this->pre)->where($this->field.'='.$id.' and is_delete=0')->getField('user_id');
		if (empty($ori_user_id)) {
			return $this->return_info(false, 'delete');
		}
		$digg_id=$this->isDigg($id, $digg_user_id);
		if($digg_id){
			return $this->return_info(true, 'cancel');
		}else{
			$data[$this->field]=$id;
			$data[$this->pre.'_user_id']=$ori_user_id;
			$data['digg_user_id']=$digg_user_id;
			$data['create_time']=time();
			$add_res=$this->m->add($data);
			
			if($add_res){
				//增加赞数量
				CommonModel::addFieldCount($this->pre, $this->field.'='.$id, DIGG_COUNT);
				if ($digg_user_id != $ori_user_id) {
					$notify=new NotifyModel();
					$notify->digg_notify($id, $digg_user_id, $ori_user_id, $this->pre);
					$notify->save_notify($digg_user_id, $ori_user_id, 'digg');
				}
				return $this->return_info(true, 'digg');
			}else{
				return $this->return_info(false, 'faild');
			}
		}
	}
	/**
	 * 是否点过赞
	 * @param  $id_str 			动态id字符串
	 * @param  $digg_user_id 	点赞的用户id
	 * @return $digg_id/null
	 */
	public function isDigg($id_str,$digg_user_id) {
		$digg_list=$this->m->where($this->field.' in ('.$id_str.') and digg_user_id='.$digg_user_id)->select();
		foreach ($digg_list as $v) {
			$list[$v[$this->field]]='1';
		}
		return $list;
	}
	
	//返回信息
    private function return_info($bool, $info) {
        return array(
            0 => $bool,
            1 => $info
        );
    }
}
