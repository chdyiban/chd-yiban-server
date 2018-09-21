define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'dormitory/infolist/index',
                    add_url: 'dormitory/infolist/add',
                    edit_url: 'dormitory/infolist/edit',
                    del_url: 'dormitory/infolist/del',
                    multi_url: 'dormitory/infolist/multi',
                    table: 'fresh_list',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'XH',
                sortName: 'XH',
                // searchFormVisible: true,
                // searchFormTemplate: 'customformtpl',
                columns: [
                    [
                        // {checkbox: true},                       
                        {field: 'XH', title: __('学号')},
                        {field: 'BRXM', title: __('姓名')},
                        {field: 'YXJC', title: __('院系简称')},
                        // {field: 'SFGC', title: __('是否孤残')},
                        // {field: 'RXQHK', title: __('入学前户口')},
                        // {field: 'JTRKS', title: __('家庭人口数')},
                        // {field: 'YZBM', title: __('邮政编码')},
                        // {field: 'BRDH', title: __('本人电话')},
                        // {field: 'BRQQ', title: __('本人QQ')},
                        // {field: 'SZDQ', title: __('所在地区')},
                        // {field: 'XXDZ', title: __('详细地址')},
                        // {field: 'ZP', title: __('照片')},
                        // {field: 'ZSR', title: __('总收入')},
                        // {field: 'RJSR', title: __('人均收入')},
                        // {field: 'FQZY', title: __('父亲职业')},
                        // {field: 'MQZY', title: __('母亲职业')},
                        // {field: 'FQLDNL', title: __('母亲劳动能力')},
                        // {field: 'MQLDNL', title: __('父亲劳动能力')},
                        // {field: 'YLZC', title: __('医疗支出')},
                        // {field: 'SZQK', title: __('受灾情况')},
                        // {field: 'JTBG', title: __('家庭变故')},
                        // {field: 'ZCYF', title: __('政策优抚')},
                        {field: 'XM', title: __('姓名')},
                        {field: 'GX', title: __('关系')},
                        {field: 'NL', title: __('年龄')},
                        {field: 'ZY', title: __('职业')},
                        {field: 'GZDW', title: __('工作单位')},
                        {field: 'NSR', title: __('年收入')},
                        {field: 'LXDH', title: __('联系电话')},
                        {field: 'JKZK', title: __('健康状况')},
                        // {field: 'family1.XM', title: __('姓名')},
                        // {field: 'family1.GX', title: __('关系')},
                        // {field: 'family1.NL', title: __('年龄')},
                        // {field: 'family1.ZY', title: __('职业')},
                        // {field: 'family1.GZDW', title: __('工作单位')},
                        // {field: 'family1.NSR', title: __('年收入')},
                        // {field: 'family1.LXDH', title: __('联系电话')},
                        // {field: 'family1.JKZK', title: __('健康状况')},
                        // {field: 'family2.XM', title: __('姓名')},
                        // {field: 'family2.GX', title: __('关系')},
                        // {field: 'family2.NL', title: __('年龄')},
                        // {field: 'family2.ZY', title: __('职业')},
                        // {field: 'family2.GZDW', title: __('工作单位')},
                        // {field: 'family2.NSR', title: __('年收入')},
                        // {field: 'family2.LXDH', title: __('联系电话')},
                        // {field: 'family2.JKZK', title: __('健康状况')},
                        // {field: 'family3.XM', title: __('姓名')},
                        // {field: 'family3.GX', title: __('关系')},
                        // {field: 'family3.NL', title: __('年龄')},
                        // {field: 'family3.ZY', title: __('职业')},
                        // {field: 'family3.GZDW', title: __('工作单位')},
                        // {field: 'family3.NSR', title: __('年收入')},
                        // {field: 'family3.LXDH', title: __('联系电话')},
                        // {field: 'family3.JKZK', title: __('健康状况')},
                        // {field: 'family4.XM', title: __('姓名')},
                        // {field: 'family4.GX', title: __('关系')},
                        // {field: 'family4.NL', title: __('年龄')},
                        // {field: 'family4.ZY', title: __('职业')},
                        // {field: 'family4.GZDW', title: __('工作单位')},
                        // {field: 'family4.NSR', title: __('年收入')},
                        // {field: 'family4.LXDH', title: __('联系电话')},
                        // {field: 'family4.JKZK', title: __('健康状况')},
                        // {field: 'family5.XM', title: __('姓名')},
                        // {field: 'family5.GX', title: __('关系')},
                        // {field: 'family5.NL', title: __('年龄')},
                        // {field: 'family5.ZY', title: __('职业')},
                        // {field: 'family5.GZDW', title: __('工作单位')},
                        // {field: 'family5.NSR', title: __('年收入')},
                        // {field: 'family5.LXDH', title: __('联系电话')},
                        // {field: 'family5.JKZK', title: __('健康状况')},
                        // {field: 'family6.XM', title: __('姓名')},
                        // {field: 'family6.GX', title: __('关系')},
                        // {field: 'family6.NL', title: __('年龄')},
                        // {field: 'family6.ZY', title: __('职业')},
                        // {field: 'family6.GZDW', title: __('工作单位')},
                        // {field: 'family6.NSR', title: __('年收入')},
                        // {field: 'family6.LXDH', title: __('联系电话')},
                        // {field: 'family6.JKZK', title: __('健康状况')},
                        // {field: 'family7.XM', title: __('姓名')},
                        // {field: 'family7.GX', title: __('关系')},
                        // {field: 'family7.NL', title: __('年龄')},
                        // {field: 'family7.ZY', title: __('职业')},
                        // {field: 'family7.GZDW', title: __('工作单位')},
                        // {field: 'family7.NSR', title: __('年收入')},
                        // {field: 'family7.LXDH', title: __('联系电话')},
                        // {field: 'family7.JKZK', title: __('健康状况')},
                        // {field: 'family8.XM', title: __('姓名')},
                        // {field: 'family8.GX', title: __('关系')},
                        // {field: 'family8.NL', title: __('年龄')},
                        // {field: 'family8.ZY', title: __('职业')},
                        // {field: 'family8.GZDW', title: __('工作单位')},
                        // {field: 'family8.NSR', title: __('年收入')},
                        // {field: 'family8.LXDH', title: __('联系电话')},
                        // {field: 'family8.JKZK', title: __('健康状况')},
                        // {field: '', title: __('学院')},
                        // {field: 'rest_num', title: __('剩余床铺数量')},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});