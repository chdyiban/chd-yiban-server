define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bx/repairstar/index',
                }
            });

            var table = $("#table");
            
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {field: 'id', title: __('ID'),operate:false,visible: false},
                        {field: 'stu_id', title: __('学号')},
                        {field: 'stu_name', title: __('学生姓名')},
                        {field: 'title', title: __('标题'),operate:false},
                        {field: 'content', title: __('维修内容'),operate:false},
                        {field: 'admin.nickname', title: __('分配单位')},
                        {field: 'repair_worker.name', title: __('工人姓名')},
                        {field: 'finished_time', title: __('完工时间'),operate:false},
                        {field: 'star', title: __('评价星级')},
                        {field: 'message', title: __('评价信息'),operate:false},
                        {field: 'operate', title: __('操作'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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