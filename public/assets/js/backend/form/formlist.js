define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'form/formlist/index',
                    add_url: 'form/formlist/add',
                    edit_url: 'form/formlist/edit',
                    del_url: 'form/formlist/del',
                    multi_url: 'form/formlist/multi',
                    // table: 'fresh_list',
                }
            });
            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'ID',
                sortName: 'ID',
                visible: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'ID', title: __('ID')},
                        {field: 'title', title: __('标题')},
                        {field: 'desc', title: __('说明')},
                        {field: 'create_time', title: __('创建时间'),formatter:  Table.api.formatter.datetime},
                        // {field: 'rest_num', title: __('剩余床位数量')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                        buttons: [
                            {name: 'detail',text:"问卷内容配置", classname: 'btn btn-xs btn-primary btn-success', icon: 'fa fa-hand-stop-o', url: 'form/questionnairelist/index?form={ID}', callback: function (data){}},      
                        ], 
                    }
                    ]
                ]
            });
            // console.log(fieldList);
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