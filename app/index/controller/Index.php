<?php
namespace app\index\controller;

use app\index\model\CmfPortalPost;


class Index
{
    public $domain     = '127.0.0.1';
    private $uploadRoot = '/home/www/site/public/upload/';

   
    /**
     * 文章格式自动转化
     * @param $postId
     */
    public function format($cronId=0){
        if($cronId){
            $postId = $cronId;
        }else{
            $postId  = input('id');
        }
        if(!$postId){
            exit('ERROR : id is must!');
        }
        $model   = new CmfPortalPost();
        $rs      = $model->getPost($postId);
        $data    = [];
        $id      = $rs['id'];
        $status  = $rs['spider_status'];
        $host = $rs['post_source'];
        $host = explode('/', $host );

        //加锁
        $model->savePost($id, ['spider_status'=>101]);

        if(empty($host[2])){
            return false;
        }
        $host = $host[2];

        $data['post_title']    = toTradition($rs['post_title']);
        $data['post_content']  = toTradition($rs['post_content']);
        $data['post_keywords'] = toTradition($rs['post_keywords']);
        $data['post_excerpt']  = toTradition($rs['post_excerpt']);
        $data['spider_status'] = 100;

        $data['more']           = $this->formatMore($host, $rs['more']);
        sleep(1);
        $data['post_content']        = $this->formatContent($host, $data['post_content']);
        if(!empty($data['more']) &&!empty($data['post_content'])){

        }else{
            unset($data);
            $data['spider_status'] = $status + 1;
        }
        return $model->savePost($id, $data);

    }

    public function formatMore($host, $json){
        $arr = json_decode($json, true);
        if(empty($arr['thumbnail'])){
            return false;
        }
        $url            = $arr['thumbnail'];

        $path =  $this->loadPicture($host, $url);
        if($path){
            $more = [];
            $more['thumbnail'] = $path;
            $more['template']  = '';
            return json_encode($more);
        }

        return false;

    }

    /**
     * 文章远程图片转换为本地
     * @param $host
     * @param $content
     * @return bool
     */
    public function formatContent($host, $content){
        include_once ROOT_PATH.'public/simplehtmldom/simple_html_dom.php';
        $content = htmlspecialchars_decode($content);
        $html = str_get_html($content);
        foreach($html->find('img') as $v){
            $url = $v->src;
            if(strpos($url, $this->domain)){
                continue;
            }
            $path = $this->loadPicture($host, $url, 'img');
            if(!$path){
                return false;
            }
            $v->outertext = "<img src='http://{$this->domain}/upload/$path'>";
        }
        return htmlspecialchars($html);
    }

    /**
     * 下载图片
     * @param $postId
     */
    public function loadPicture($host, $url, $path=''){
        if(!is_dir($this->uploadRoot.'third')){
            mkdir($this->uploadRoot.'third');
        }
        if(empty($path)){
            $path = 'third/'.time().rand(100,999).'.jpg';
        }else{
            if(!is_dir($this->uploadRoot.'third/'.$path)){
                mkdir($this->uploadRoot.'third/'.$path);
            }
            $path = 'third/'.$path.'/'.time().rand(100,999).'.jpg';
        }
        $arr['url']    = $url;
        $arr['header'] = [
            'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language:zh-CN,zh;q=0.8',
            'Connection:keep-alive',
            'Upgrade-Insecure-Requests:1',
            'User-Agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.79 Safari/537.36',
            'Referrer:'.$host
        ];
        $pic = send($arr);
        if(empty($pic)){
            return false;
        }
        if(file_put_contents($this->uploadRoot.$path, $pic)){
            return $path;
        };

        return false;
    }
    /**
     * 发布文章
     * @param $postId
     */
    public function publish($id=0)
    {
        if ($id) {

        } else {
        $id = input('id');
        }
        if(!$id){
            exit('ERROR : id is must!');
        }
        $model  = new CmfPortalPost();
        $rs     = $model->publish($id);

    }

    public function cronFormat(){
        $model   = new CmfPortalPost();
        $rs      = $model->getUnFormatSpiderPostIds();
        if(empty($rs)){
            echo PHP_EOL;
            echo' finnish '.date('Y-m-d H:i:s', time());
        }
        foreach($rs as $v){
            $this->format($v);
            echo PHP_EOL.$v.' query ok '.date('Y-m-d H:i:s', time()).PHP_EOL;
        }

        exit(PHP_EOL.PHP_EOL);
    }

    public function cronPublish($id=0){
        if(!$id){
            sleep(rand(1, 500));

            $model   = new CmfPortalPost();
            $id      = $model->getPublishId();

        }
        if(empty($id)){
            echo PHP_EOL;
            echo' finnish '.date('Y-m-d H:i:s', time());
            exit(PHP_EOL.PHP_EOL);
        }
        $this->publish($id);
        echo PHP_EOL;
        echo $id.' published '.date('Y-m-d H:i:s', time());
        exit(PHP_EOL.PHP_EOL);
    }


}
