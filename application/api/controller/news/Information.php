<?php

namespace app\api\controller\news;

use addons\cms\model\Archives as ArchivesModel;
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
        foreach ($list as $key => $value) {
            $style_id = Db::name('cms_addonnews')->where('id', $value['id'])->field('style')->find()['style'];
            $list[$key]['style_id'] = $style_id;
            $list[$key]['create_date'] = date("Y-m-d", $value['createtime']);
            // if ($value['power'] != $params['power']) {
            //     unset($list[$key]);
            // }
        }
        $info = [
            'status' => 200,
            'message' => 'success',
            'data' => $list,
        ];

        return json($info);
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

    public function nav()
    {
        $all = collection(Channel::order("weigh desc,id desc")->select())->toArray();
        $i = 0;
        $list = array();
        foreach ($all as $k => $v) {
            // $id_array = [3, 4, 5, 7];
            // if(in_array($v['id'], $id_array)){
            if ($v["parent_id"] == 1) {
                $list[] = [ 
                    'id'    => $i,
                    // 'type'   => 'all',之前用来在前端修改样式，此时只有白色无需该字段
                    'name'   => $v['name'],
                    // 'storage' => [],
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
        if (empty($key["openid"])) {
            $info = [
                'status' => 500,
                'message' => 'param error',
                'data' => '',
            ];
        } else {
            $userTags = array();
            $userChannel = array();
            $mynav = $key["mynav"];
            foreach ($mynav as $value) {
                if (!empty($value["channel"])) {
                    $userChannel[] =  $value;
                }
                if (!empty($value["tag"])) {
                    $userTags[] = $value;
                }
            }
            $userTags = json_encode($userTags);
            $userChannel = json_encode($userChannel);
            $res = Db::name("cms_user_tags")-> insert(["channel" => $userChannel,"tag" => $userTags]);
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
        foreach ($tagsList as $key => $value) {
            $tagsList[$key]["id"] = $key;
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