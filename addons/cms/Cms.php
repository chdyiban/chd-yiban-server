<?php

namespace addons\cms;

use app\common\library\Menu;
use think\Addons;
use think\Log;

/**
 * CMS插件
 */
class Cms extends Addons
{

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        $menu = [
            [
                'name'    => 'cms',
                'title'   => 'CMS管理',
                'sublist' => [
                    [
                        'name'    => 'cms/archives',
                        'title'   => '内容管理',
                        'icon'    => 'fa fa-file-text-o',
                        'sublist' => [
                            ['name' => 'cms/archives/index', 'title' => '查看'],
                            ['name' => 'cms/archives/add', 'title' => '添加'],
                            ['name' => 'cms/archives/edit', 'title' => '修改'],
                            ['name' => 'cms/archives/del', 'title' => '删除'],
                            ['name' => 'cms/archives/multi', 'title' => '批量更新'],
                        ]
                    ],
                    [
                        'name'    => 'cms/channel',
                        'title'   => '栏目管理',
                        'icon'    => 'fa fa-list',
                        'sublist' => [
                            ['name' => 'cms/channel/index', 'title' => '查看'],
                            ['name' => 'cms/channel/add', 'title' => '添加'],
                            ['name' => 'cms/channel/edit', 'title' => '修改'],
                            ['name' => 'cms/channel/del', 'title' => '删除'],
                            ['name' => 'cms/channel/multi', 'title' => '批量更新'],
                        ]
                    ],
                    [
                        'name'    => 'cms/modelx',
                        'title'   => '模型管理',
                        'icon'    => 'fa fa-th',
                        'sublist' => [
                            ['name' => 'cms/modelx/index', 'title' => '查看'],
                            ['name' => 'cms/modelx/add', 'title' => '添加'],
                            ['name' => 'cms/modelx/edit', 'title' => '修改'],
                            ['name' => 'cms/modelx/del', 'title' => '删除'],
                            ['name' => 'cms/modelx/multi', 'title' => '批量更新'],
                            [
                                'name'    => 'cms/fields',
                                'title'   => '字段管理',
                                'icon'    => 'fa fa-fields',
                                'ismenu'  => 0,
                                'sublist' => [
                                    ['name' => 'cms/fields/index', 'title' => '查看'],
                                    ['name' => 'cms/fields/add', 'title' => '添加'],
                                    ['name' => 'cms/fields/edit', 'title' => '修改'],
                                    ['name' => 'cms/fields/del', 'title' => '删除'],
                                    ['name' => 'cms/fields/multi', 'title' => '批量更新'],
                                ]
                            ]
                        ]
                    ],
                    [
                        'name'    => 'cms/tags',
                        'title'   => '标签管理',
                        'icon'    => 'fa fa-tags',
                        'sublist' => [
                            ['name' => 'cms/tags/index', 'title' => '查看'],
                            ['name' => 'cms/tags/add', 'title' => '添加'],
                            ['name' => 'cms/tags/edit', 'title' => '修改'],
                            ['name' => 'cms/tags/del', 'title' => '删除'],
                            ['name' => 'cms/tags/multi', 'title' => '批量更新'],
                        ]
                    ],
                    [
                        'name'    => 'cms/block',
                        'title'   => '区块管理',
                        'icon'    => 'fa fa-th-large',
                        'sublist' => [
                            ['name' => 'cms/block/index', 'title' => '查看'],
                            ['name' => 'cms/block/add', 'title' => '添加'],
                            ['name' => 'cms/block/edit', 'title' => '修改'],
                            ['name' => 'cms/block/del', 'title' => '删除'],
                            ['name' => 'cms/block/multi', 'title' => '批量更新'],
                        ]
                    ],
                    [
                        'name'    => 'cms/page',
                        'title'   => '单页管理',
                        'icon'    => 'fa fa-file',
                        'sublist' => [
                            ['name' => 'cms/page/index', 'title' => '查看'],
                            ['name' => 'cms/page/add', 'title' => '添加'],
                            ['name' => 'cms/page/edit', 'title' => '修改'],
                            ['name' => 'cms/page/del', 'title' => '删除'],
                            ['name' => 'cms/page/multi', 'title' => '批量更新'],
                        ]
                    ],
                    [
                        'name'    => 'cms/comment',
                        'title'   => '评论管理',
                        'icon'    => 'fa fa-comment',
                        'sublist' => [
                            ['name' => 'cms/comment/index', 'title' => '查看'],
                            ['name' => 'cms/comment/add', 'title' => '添加'],
                            ['name' => 'cms/comment/edit', 'title' => '修改'],
                            ['name' => 'cms/comment/del', 'title' => '删除'],
                            ['name' => 'cms/comment/multi', 'title' => '批量更新'],
                        ]
                    ]
                ]
            ]
        ];
        Menu::create($menu);
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        Menu::delete('cms');
        return true;
    }

    /**
     * 插件启用方法
     */
    public function enable()
    {
        Menu::enable('cms');
    }

    /**
     * 插件禁用方法
     */
    public function disable()
    {
        Menu::disable('cms');
    }

    public function addonAfterUpgrade()
    {
        $sql = '
        ALTER TABLE `__PREFIX__cms_comment` ADD COLUMN `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT \'会员ID\' AFTER `id`;
        ALTER TABLE `__PREFIX__cms_channel` ADD COLUMN `flag` set(\'hot\',\'new\',\'recommend\') DEFAULT \'\' COMMENT \'标志\' AFTER `image`;
        ';
    }

}
