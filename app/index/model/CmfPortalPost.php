<?php
namespace app\index\model;

use think\Model;

class CmfPortalPost extends Model
{
    public function getPost($id){
        return $this->where(['id'=>$id])->find()->toArray();
    }

    public function savePost($id,$data){
        return $this->where(['id'=>$id])->update($data);
    }

    /**
     * 发布文章
     * @param $id
     * @param string $time
     * @return $this
     */
    public function publish($id, $time=''){
        $time = empty($time)?time():$time;
        $data['published_time'] = $time;
        return $this->where(['id'=>$id,'spider_status'=>100])->update($data);
    }

    /**
     * 获取未格式化的文章id
     * @param $num    每次修改数量
     * @param $time   失败后尝试次数
     * @return array
     */
    public function getUnFormatSpiderPostIds($time=3){
    //    return $this->where(['spider_status'=>['<', $time], 'spider_id'=>['>', 0] ])->order('spider_status ,id desc')->limit(1,$num)->column('id');
        $ids =  $this->alias('p')->join(['cmf_portal_category_post'=>'m'],'p.id=m.post_id','left')->where(['p.spider_status'=>['<', $time], 'p.spider_id'=>['>', 0], 'p.published_time'=>0])->order('p.spider_status, p.id desc')->group('m.category_id')->column('p.id');

        return $ids;

    }

    /**
     * 获取要发布的文章id
     * @param $cateId
     * @return mixed
     */
    public function getPublishId(){
       $ids =  $this->alias('p')->join(['cmf_portal_category_post'=>'m'],'p.id=m.post_id','left')->where(['p.spider_status'=>100, 'p.spider_id'=>['>', 0], 'p.published_time'=>0])->order('p.id desc')->group('m.category_id')->column('p.id');
       if(empty($ids)){
           return false;
       }
       $length = count($ids);
       $rand   = rand(0, $length-1);
       return $ids[$rand];
    }

}
