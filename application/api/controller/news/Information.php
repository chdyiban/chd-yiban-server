<?php

namespace app\api\controller\news;

use addons\cms\model\Archives as ArchivesModel;
use addons\cms\model\Tags as TagsModel;
use app\api\model\News as NewsModel;
use addons\cms\model\Channel;
use addons\cms\model\Comment;
use addons\cms\model\Modelx;
use app\common\controller\Api;
use think\Db;
use app\api\model\Wxuser as WxuserModel;
/**
 * 资讯栏目控制器
 */
class Information extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $page = (int) $this->request->get('page');
        $openid = $this->request->get('openid');

        //通过学号判断是老师还是学生
        $user = new WxuserModel;
        $userId = $user->where('open_id',$openid)->value('portal_id');
        $params = [];

        if(strlen($userId) == 6){
            //$params['power'] = 'teacher';
        } else {
            $params['power'] = 'all';
        }

        $model = (int) $this->request->request('model');
        $channel = (int) $this->request->request('channel');
        $tag = $this->request->request('tag');
        //判断是通过标签搜索返回结果
        if (!empty($tag)) {
            $list = $this->getListByTags($tag);
        } else {
            if ($model) {
                $params['model'] = $model;
            }
            if ($channel) {
                $params['channel'] = $channel;
            }
            $page = max(1, $page);
            $params['limit'] = ($page - 1) * 10 . ',10';
            $params['orderby'] = 'id';
            if ($channel == 47) {
                $params['channel'] = [3, 4, 5, 7];
                $params['flag'] = 'recommend';
            }
            $list = ArchivesModel::getArchivesList($params);
            //$list = ArchivesModel::getWeAppArchivesList($params);
            foreach ($list as $key => &$value) {
                $style_id = Db::name('cms_addonnews')->where('id', $value['id'])->field('style')->find()['style'];
                $value['style_id'] = $style_id;
                $value['create_date'] = date("Y-m-d", $value['createtime']);
                // if ($value['power'] != $params['power']) {
                //     unset($list[$key]);
                // }
            }
        }
        
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => $list,
        ];

        return json($info);
    }
/**
     * 根据标签获取文章内容
     * 参考cms模块cms\controller\Tags.php\index方法
     * @time 2019/5/19
     */

    private function getListByTags($name)
    {
        // $name = "长大官网--学校要闻";
        // $name = $this->request->param('name');
        if ($name) {
            $tags = TagsModel::getByName($name);
        }
        if (!$tags) {
            $this->error(__('No specified tags found'));
        }

        $filterlist = [];
        $orderlist = [];
        $page = (int) $this->request->get('page');
        $page = max(1, $page);
        $limit = ($page - 1) * 10 . ',10';

        // $orderby = $this->request->get('orderby', '');
        $orderby = "id";
        // $orderway = $this->request->get('orderway', '', 'strtolower');
        $orderway = "desc";
        $params = [];
        if ($orderby)
            $params['orderby'] = $orderby;
        if ($orderway)
            $params['orderway'] = $orderway;
        if ($tags) {
            $sortrank = [
                ['name' => 'id', 'field' => 'id', 'title' => __('Post date')],
                ['name' => 'default', 'field' => 'weigh', 'title' => __('Default')],
                ['name' => 'views', 'field' => 'views', 'title' => __('Views')],
            ];

            $orderby = $orderby && in_array($orderby, ['default', 'id', 'views']) ? $orderby : 'default';
            $orderway = $orderway ? $orderway : 'desc';
            foreach ($sortrank as $k => $v) {
                $url = '?' . http_build_query(array_merge($params, ['orderby' => $v['name'], 'orderway' => ($orderway == 'desc' ? 'asc' : 'desc')]));
                $v['active'] = $orderby == $v['name'] ? true : false;
                $v['orderby'] = $orderway;
                $v['url'] = $url;
                $orderlist[] = $v;
            }
            $orderby = $orderby == 'default' ? 'weigh' : $orderby;
        }
        $pagelist = ArchivesModel::where('status', 'normal')
                    ->where('id', 'in', explode(',', $tags['archives']))
                    ->order($orderby, $orderway)
                    ->limit($limit)
                    ->select();
        foreach ($pagelist as $key => &$value) {
            $style_id = Db::name('cms_addonnews')->where('id', $value['id'])->field('style')->find()['style'];
            $value['style_id'] = $style_id;
            $value['create_date'] = date("Y-m-d", $value['createtime']);
            // if ($value['power'] != $params['power']) {
            //     unset($list[$key]);
            // }
        }
        // dump($pagelist);
        return $pagelist;
    }
    //对应CMS模块下的新闻详情
    public function detail()
    {
        // $action = $this->request->post("action");
        // if ($action && $this->request->isPost()) {
        //     return $this->$action();
        // }

        //获取文章id以及用户open_id,写入cms_archives_log
        $article_id = $this->request->param('id');
        $open_id = $this->request->param('openid');
        $this->insertLog($article_id,$open_id);
        $diyname = $this->request->param('diyname');
        if ($diyname && !is_numeric($diyname)) {
            $archives = ArchivesModel::getByDiyname($diyname);
        } else {
            $id = $diyname ? $diyname : $this->request->request('id', '');
            $archives = ArchivesModel::get($id);
        }
        if (!$archives || $archives['status'] == 'hidden' || $archives['deletetime']) {
            $this->error(__('No specified article found'));
        }
        $channel = Channel::get($archives['channel_id']);
        if (!$channel) {
            $this->error(__('No specified channel found'));
        }
        $model = Modelx::get($channel['model_id']);
        if (!$model) {
            $this->error(__('No specified model found'));
        }
        $archives->setInc("views", 1);
        $addon = db($model['table'])->where('id', $archives['id'])->find();
        if ($addon) {
            $archives = array_merge($archives->toArray(), $addon);
        }

        $commentList = Comment::getCommentList(['aid' => $archives['id']]);

        $list = ['archivesInfo' => $archives, 'channelInfo' => $channel, 'commentList' => $commentList->getCollection()];
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => $list,
        ];

        return json($info);
    }
    /**
     * 判断数据库中用户是否自定义标签修改
     * @time 2019/5/18
     */
    public function nav()
    {
        $key = json_decode(base64_decode($this->request->post('key')),true);
        if (empty($key["openid"])) {
            $XH = "";
            $list = $this->getNav($XH);
        } else {
            $user = new WxuserModel;
            $XH = $user->where('open_id',$key["openid"])->value('portal_id');
            $res = Db::name("cms_user_tags") -> where("XH",$XH) -> find();
            if (empty($res)) {
                $XH = "";
                $list = $this->getNav($XH);
            } else {
                $list = $this->getNav($XH);
            }
        }
        // $list = [
        //     [
        //         'id' => 0,
        //         'type' => 'all',
        //         'name' => '🔥头条',
        //         'storage' => [],
        //         'channel'=> 0,
        //         'enabled' => [
        //             'guest' => true,
        //             'student' => true,
        //             'teacher' => true,
        //         ]
        //     ],[
        //         'id' => 1,
        //         'type' => 'yiban',
        //         'name' => '门户新闻',
        //         'storage' => [],
        //         'channel'=> 7,
        //         'enabled' => [
        //             'guest' => true,
        //             'student' => true,
        //             'teacher' => true,
        //         ]
        //     ]
        // ];
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => $list,
        ];

        return json($info);

    }

    private function getNav($XH)
    {   
        $list = array();
        if (empty($XH)) {
            $all = collection(Channel::order("weigh desc,id desc")->select())->toArray();
            $i = 0;
            foreach ($all as $k => $v) {
                // $id_array = [3, 4, 5, 7];
                // if(in_array($v['id'], $id_array)){
                if ($v["parent_id"] == 1) {
                    $list[] = [ 
                        'id'    => $i,
                        // 'type'   => 'all',之前用来在前端修改样式，此时只有白色无需该字段
                        'name'   => $v['name'],
                        'storage' => [],
                        'type_id' => 0,
                        'channel' => $v['id'],
                        'enabled' => [
                            'guest' => true,
                            'student' => true,
                            'teacher' => true,
                        ]
                    ];
                    $i = $i + 1;
                }
            }
        } else {
            $userTags = Db::name("cms_user_tags") -> where("XH",$XH)->find();
            // $channelList = json_decode($userTags["channel"],true);
            $tagsList = json_decode($userTags["tag"],true);
            $i = 0;
            // foreach ($channelList as $key => &$value) {
            //     $value["id"] = $i;
            //     $value["storage"] = [];
            //     $i = $i+1;
            //     $list[] = $value;
            // }
            foreach ($tagsList as $key => &$value) {
                $value["id"] = $i;
                $value["storage"] = [];
                $i = $i+1;
                $list[] = $value;
            }
        }
        return $list;
    }

    private function insertLog($article_id,$open_id)
    {
        $res = Db::name('cms_archives_log') -> insert([
            'open_id'     => $open_id,
            'archives_id' => $article_id,
            'timestamp'   => time(),
        ]);
    }
    /**
     * 保存用户自定义的标签
     */
    public function setnav()
    {
        // $key = json_decode($this->request->post('key'),true);
        $key = json_decode(base64_decode($this->request->post('key')),true);
        $XH = $key["id"];
        if (empty($key["openid"]) || empty($XH)) {
            $info = [
                'status' => 500,
                'message' => 'param error',
                'data' => '',
            ];
        } else {
            $userTags = array();
            // $userChannel = array();
            // $mynav = $key["mynav"];
            // foreach ($mynav as $value) {
            //     if (!empty($value["channel"])) {
            //         $userChannel[] =  $value;
            //     }
            //     if (!empty($value["tag"])) {
            //         $userTags[] = $value;
            //     }
            // }
            $userTags = json_encode($key["mynav"]);
            // $userChannel = json_encode($userChannel);
            $isExit = Db::name("cms_user_tags") -> where("XH",$XH)->find();
            if (!empty($isExit)) {
                $res = Db::name("cms_user_tags")->where("XH",$XH) -> update(["tag" => $userTags]);
                //没有更新
                if ($res) {
                    $info = [
                        'status' => 200,
                        'message' => 'success',
                        'data' => '',
                    ];
                } else {
                    $info = [
                        'status' => 200,
                        'message' => '未更新标签',
                        'data' => '',
                    ];
                }
            } else {
                $res = Db::name("cms_user_tags")-> insert(["XH" => $XH,"tag" => $userTags]);
                if ($res) {
                    $info = [
                        'status' => 200,
                        'message' => 'success',
                        'data' => '',
                    ];
                } else {
                    $info = [
                        'status' => 500,
                        'message' => 'data error',
                        'data' => '',
                    ];
                }
            }
           
        }
        return json($info);
    }

    public function tags()
    {
        $list = Db::name("config")
                -> where("name","tagsShow") 
                -> field("value")
                -> find();
        $tagsList = json_decode($list["value"] ,true);
        $i = 0;
        foreach ($tagsList as $key => &$value) {
            $value["id"] = $i;
            $value["storage"] = [];
            $i = $i+1;
        }
        // $tagsList = $list["value"];
        $info = [
            "status" => 200,
            "message" => "success",
            "data"    => $tagsList,
        ];
        return json($info);

    }
}