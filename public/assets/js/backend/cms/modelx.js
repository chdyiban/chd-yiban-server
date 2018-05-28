define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cms/modelx/index',
                    add_url: 'cms/modelx/add',
                    edit_url: 'cms/modelx/edit',
                    del_url: 'cms/modelx/del',
                    multi_url: 'cms/modelx/multi',
                    table: 'model',
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
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name')},
                        {field: 'table', title: __('Table')},
                        {field: 'channeltpl', title: __('Channeltpl')},
                        {field: 'listtpl', title: __('Listtpl')},
                        {field: 'showtpl', title: __('Showtpl')},
                        {field: 'createtime', sortable: true, title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', sortable: true, title: __('Updatetime'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, 
                            buttons:[
                                {name: 'fields', text: __('Fields'), classname: 'btn btn-xs btn-info btn-fields btn-addtabs', icon: 'fa fa-list', url: 'cms/fields/index/model_id/{ids}'},
                            ],
                            events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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