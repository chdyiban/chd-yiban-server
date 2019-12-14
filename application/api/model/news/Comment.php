<?php

namespace app\api\model\news;

use Exception;
use think\Model;
use think\Validate;
use think\Db;
use addons\cms\model\Archives;
use addons\cms\model\Page;
/**
 * 评论模型
 */
class Comment Extends Model
{

    protected $name = "cms_comment";
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
        'create_date',
    ];

    //自定义初始化
    protected static function init()
    {

    }

    public function getCreateDateAttr($value, $data)
    {
        return human_date($data['createtime']);
    }

    /**
     * 发表评论
     * @param array $params
     * @param openid,aid,content,type
     * @return array
     */
    public static function postComment($params = [])
    {
        $request = request();
        $request->filter('strip_tags');
        $useragent = $request->server('HTTP_USER_AGENT', '');
        $ip = $request->ip();

        $open_id = $params["openid"];
        $safe = Db::name('wx_user') -> where('open_id',$open_id)-> find();
        if (empty($safe) || empty($safe["portal_id"])) {
            // throw new Exception("请绑定账号后进行评论");
            return ["status" => false, "msg" => "请绑定账号后进行评论"];
        }
        if (empty($safe["mobile"]) || $safe["iswxbind"] == 0 ) {
            return ["status" => false, "msg" => "请前往个人信息界面绑定手机后进行评论"];
        }
        if (!isset($params['aid']) || !isset($params['content'])) {
            // throw new Exception("内容不能为空");
            return ["status" => false, "msg" => "内容不能为空"];

        }

        // $params['user_id'] = $safe['portal_id'];
        $params['user_id'] = $safe['id'];
        $params['type'] = isset($params['type']) ? $params['type'] : 'archives';
        $params['content'] = nl2br($params['content']);
        $params['content'] = trim($params['content']);

        $archives = $params['type'] == 'archives' ? Archives::get($params['aid']) : Page::get($params['aid']);
        if (!$archives || $archives['status'] == 'hidden') {
            return ["status" => false, "msg" => "文档未找到"];
            // throw new Exception("文档未找到");
            
        }

        $rule = [
            'type'      => 'require|in:archives,page',
            'pid'       => 'require|number',
            'user_id'   => 'require|number',
            'content'   => 'require|length:3,250',
            // '__token__' => 'token',
        ];
        $validate = new Validate($rule);
        $result = $validate->check($params);
        if (!$result) {
            // throw new Exception($validate->getError());
            return ["status" => false, "msg" => $validate->getError()];

        }

        //查找最后评论
        // $lastComment = self::where(['type' => $params['type'], 'aid' => $params['aid'], 'ip' => $ip])->order('id', 'desc')->find();
        // if ($lastComment && time() - $lastComment['createtime'] < 30) {
        //     throw new Exception("对不起！您发表评论的速度过快！请稍微休息一下，喝杯咖啡");
        // }
        // if ($lastComment && $lastComment['content'] == $params['content']) {
        //     throw new Exception("您可能连续了相同的评论，请不要重复提交");
        // }
        $params['ip'] = $ip;
        $params['useragent'] = $useragent;
        $params['status'] = 'normal';
        (new static())->allowField(true)->save($params);

        $archives->setInc('comments');
        // return true;
        return ["status" => true, "msg" => "success"];

    }

    /**
     * 获取评论列表
     * @param $params
     * @return \think\Paginator
     */
    public static function getCommentList($params)
    {
        $type = empty($params['type']) ? 'archives' : $params['type'];
        $aid = empty($params['aid']) ? 0 : $params['aid'];
        $pid = empty($params['pid']) ? 0 : $params['pid'];
        $condition = empty($params['condition']) ? '' : $params['condition'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $fragment = empty($params['fragment']) ? 'comments' : $params['fragment'];
        $row = empty($params['row']) ? 10 : (int)$params['row'];
        $orderby = empty($params['orderby']) ? 'nums' : $params['orderby'];
        $orderway = empty($params['orderway']) ? 'desc' : strtolower($params['orderway']);
        $pagesize = empty($params['pagesize']) ? $row : $params['pagesize'];
        $cache = !isset($params['cache']) ? false : (int)$params['cache'];
        $orderway = in_array($orderway, ['asc', 'desc']) ? $orderway : 'desc';

        $where = [];
        if ($type) {
            $where['type'] = $type;
        }
        if ($aid !== '') {
            $where['aid'] = $aid;
        }
        if ($pid !== '') {
            $where['pid'] = $pid;
        }
        $order = $orderby == 'rand' ? 'rand()' : (in_array($orderby, ['pid', 'id', 'createtime', 'updatetime']) ? "{$orderby} {$orderway}" : "id {$orderway}");

        $list = self::with('user')
            ->where($where)
            ->where($condition)
            ->field($field)
            ->order($order)
            ->cache($cache)
            ->paginate($pagesize, false, ['type' => '\\addons\\cms\\library\\Bootstrap', 'var_page' => 'cp', 'fragment' => $fragment]);
        self::render($list);
        return $list;
    }

    public static function render(&$list)
    {
        foreach ($list as $k => &$v) {
            $commentTree = self::with('user')
                    ->where('pid',$v['id'])
                    ->field("id,content,createtime,user_id,pid,aid")
                    ->limit(3)
                    ->select(); 
            $v["commentTree"] = $commentTree;
        }
        return $list;
    }

    /**
     * 关联会员模型
     */
    public function user()
    {
        return $this->belongsTo("app\api\model\Wxuser","user_id")->field('id,nickname,avatar')->setEagerlyType(1);
    }

    /**
     * 关联文章模型
     */
    public function archives()
    {
        return $this->belongsTo("addons\cms\model\Archives", 'aid')->field('id,title,image,diyname,model_id,channel_id,likes,dislikes,tags,createtime')->setEagerlyType(1);
    }

}
