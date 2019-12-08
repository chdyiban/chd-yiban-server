define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'form/index/index',
                    get_column_url: 'form/index/getColumn',
                    add_url: 'form/index/add',
                    edit_url: 'form/index/edit',
                    del_url: 'form/index/del',
                    multi_url: 'form/index/multi',
                    // table: 'fresh_list',
                }
            });
            var formId = $("#formId").val();
            var fieldList = [{checkbox:true},{field:"user_id",title:"学号"},{field:"XM",title:"姓名"}];
            var table = $("#table");
            $.ajaxSettings.async = false;
            $.get($.fn.bootstrapTable.defaults.extend.get_column_url+"?form="+formId,function (data) {
                if (data.status==true) {
                    for (let index = 0; index < data.data.list.length; index++) {
                        var temp = {field:data.data.list[index].title,title:data.data.list[index].title};
                        // var temp = {field:"1",titile:"demo"};
                        fieldList.push(temp)
                    }
                } 

            });
            $.ajaxSettings.async = true;
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url+"?form="+formId,
                pk: 'ID',
                sortName: 'ID',
                visible: false,
                columns: [
                    fieldList
                    // [
                    //     {checkbox: true},
                    //     {field: 'user_id', title: __('学号')},
                    //     {field: 'title', title: __('标题')},
                    //     {field: 'value', title: __('答案')},
                    //     // {field: 'rest_num', title: __('剩余床位数量')},
                    //     // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    // ]
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