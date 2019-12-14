<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\admin\model\CalendarEvent;
use app\common\controller\Backend;
use think\exception\PDOException;

/**
 * 日历管理
 *
 * @icon calendar
 */
class Calendar extends Backend
{

    /**
     * Calendar模型对象
     */
    protected $model = null;
    protected $childrenAdminIds = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Calendar');
        $this->childrenAdminIds = $this->auth->getChildrenAdminIds(true);
    }

    /**
     * 查看
     */
    public function index()
    {
        $adminList = Admin::where('id', 'in', $this->childrenAdminIds)->field('id,username as name')->select();
        $this->assignconfig('admins', $adminList);
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $start = $this->request->get('start');
            $end = $this->request->get('end');
            $type = $this->request->get('type');
            $admin_id = $this->request->get('admin_id');
            if (!$admin_id) {
                $admin_id = $type == 'my' ? $this->auth->id : 0;
            }
            $adminIds = $admin_id ? [$admin_id] : $this->childrenAdminIds;

            $list = $this->model
                ->where('admin_id', 'in', $adminIds)
                ->where(function ($query) use ($start, $end) {
                    $startTime = strtotime($start);
                    $endTime = strtotime($end);
                    $query->where("`starttime` BETWEEN {$startTime} AND {$endTime}");
                    $query->whereOr("`endtime` BETWEEN {$startTime} AND {$endTime}");
                    $query->whereOr("`starttime` < {$startTime} AND `endtime` > {$endTime}");
                })
                ->order('id desc')
                ->select();
            $result = [];
            $time = time();
            foreach ($list as $k => $v) {
                $result[] = $v['render'];
            }
            return json($result);
        }
        $eventList = CalendarEvent::where('admin_id', $this->auth->id)->order('id desc')->select();
        $this->view->assign("eventList", $eventList);
        return $this->view->fetch();
    }

    /**
     * 添加事件
     */
    public function addevent()
    {
        $params = $this->request->post("row/a");
        if ($params) {
            if (!isset($params['title']) || !$params['title']) {
                $this->error("请输入事件标题");
            }
            $params['admin_id'] = $this->auth->id;
            $calendarEvent = new CalendarEvent();
            $result = $calendarEvent->allowField(true)->save($params);
            if ($result) {
                $this->success('', null, ['id' => $calendarEvent->id, 'title' => $calendarEvent->title, 'background' => $calendarEvent->background]);
            } else {
                $this->error();
            }
        }

        $this->error(__('Parameter %s can not be empty', ''));
    }

    /**
     * 删除事件
     */
    public function delevent($ids = NULL)
    {
        if ($ids) {
            $count = model('CalendarEvent')->destroy($ids);
            if ($count) {
                $this->success();
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 添加日历
     */
    public function add($ids = NULL)
    {
        if ($this->request->isPost()) {
            if ($ids) {
                $params = CalendarEvent::get($ids);
                if ($params) {
                    $params = $params->toArray();
                    unset($params['id']);
                    $params = array_merge($params, $this->request->post("row/a"));
                }
            } else {
                $params = $this->request->post("row/a");
            }
            if ($params) {
                foreach ($params as $k => &$v) {
                    $v = is_array($v) ? implode(',', $v) : $v;
                }
                $params['status'] = 'normal';
                try {
                    $params['admin_id'] = $this->auth->id;
                    $row = model('Calendar');
                    $row->allowField(true)->save($params);
                    $data = $row->render;
                    $this->success('', null, $data);
                } catch (PDOException $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 编辑日历
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost()) {
            if (!in_array($row->admin_id, $this->childrenAdminIds)) {
                $this->error(__('You have no permission'));
            }
            $params = $this->request->post("row/a");
            if ($params) {
                foreach ($params as $k => &$v) {
                    $v = is_array($v) ? implode(',', $v) : $v;
                }
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $result = $row->save($params);
                    if ($result !== false) {
                        $data = $row->render;
                        $this->success('', null, $data);
                    } else {
                        $this->error($row->getError());
                    }
                } catch (PDOException $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除日历
     */
    public function del($ids = "")
    {
        if ($ids) {
            $count = $this->model->where('admin_id', 'in', $this->childrenAdminIds)->where('id', 'in', $ids)->delete();
            if ($count) {
                $this->success();
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 批量操作
     */
    public function multi($ids = "")
    {
        $ids = $ids ? $ids : $this->request->param("ids");
        if ($ids) {
            if ($this->request->has('params')) {
                parse_str($this->request->post("params"), $values);
                $values = array_intersect_key($values, array_flip(is_array($this->multiFields) ? $this->multiFields : explode(',', $this->multiFields)));
                if ($values) {
                    $count = $this->model->where($this->model->getPk(), 'in', $ids)->where('admin_id', 'in', $this->childrenAdminIds)->update($values);
                    $this->success();
                } else {
                    $this->error(__('You have no permission'));
                }
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

}
