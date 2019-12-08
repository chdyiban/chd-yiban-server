define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'form/formconfiglist/index',
                    add_url: 'form/formconfiglist/add',
                    edit_url: 'form/formconfiglist/edit',
                    del_url: 'form/formconfiglist/del',
                    multi_url: 'form/formconfiglist/multi',
                    table: 'form_config',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'ID',
                sortName: 'ID',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'ID', title: __('Id')},
                        {field: 'form_id', title: __('Form_id'),visible:false,operate:false,},
                        {field: 'getform.title', title: __('问卷名称')},
                        {field: 'getform.desc', title: __('问卷描述')},
                        {field: 'start_time', title: __('Start_time'),width:50, operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'end_time', title: __('End_time'),width:50,operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'getcollege.YXMC', title: __('学院')},
                        {field: 'NJDM', title: __('年级'),formatter:function(value,row){ 
                            if (value == 0) {
                                return "-";
                            }   return value;
                        }},
                        {field: 'BJDM', title: __('班级'),formatter:function(value,row){ 
                            if (value == 0) {
                                return "-";
                            }   return value;
                        }},
                        {field: 'status', title: __('Status'), width:50,visible:false, searchList: {"0":__('关闭'),"1":__('启用')}},
                        {field: 'status_text', title: __('Status'), operate:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                        buttons: [
                            {name: 'detail',text:"填写情况", classname: 'btn btn-xs btn-primary btn-success btn-detail', icon: 'fa fa-hand-stop-o', url: 'form/index/index?form={form_id}', callback: function (data){}},      
                        ], 
                    
                    }
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