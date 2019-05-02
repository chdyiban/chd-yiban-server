define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'conversationrecord/count/index',
                    add_url: 'conversationrecord/count/add',
                    edit_url: 'conversationrecord/count/edit',
                    del_url: 'conversationrecord/count/del',
                    multi_url: 'conversationrecord/count/multi',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                showToggle: false,
                showColumns: false,
                commonSearch: false,
                search: false,
                showExport: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'THSJ', title: __('谈话时间'),width:100,formatter:function(value,row){ 
                            if (value == 0) {
                                return "无";
                            } else{
                                var now = new Date(value*1000);
                                var time = now.getFullYear() + "-" +((now.getMonth()+1)<10?"0":"")+(now.getMonth()+1)+"-"+(now.getDate()<10?"0":"")+now.getDate();
                                return time; 
                            }
                        }},
                        {field: 'XH', title: __('谈话人学号'),width:60},
                        {field: 'XM', title: __('谈话人姓名'),width:40},
                        {field: 'THNR', title: __('谈话内容')},
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