define(['jquery', 'bootstrap', 'backend', 'table', 'form','bootstrap-daterangepicker','bootstrap-select'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
             // 初始化表格参数配置
             Table.api.init({
                extend: {
                    index_url: 'conversationrecord/score/index',
                    table: '',
                }
            });

            var table = $("#table");


            var XH = $("#XH").val();
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url+"?XH="+XH,
                pk: 'XN',
                sortName: 'XN',
                showToggle: false,
                showColumns: false,
                // commonSearch: false,
                search: false,
                showExport: false,
                columns: [
                    [
                        // {checkbox: true},
                        // {field: 'JLID', title: __('ID'),width:20,visible:false,},
                        // {field: 'XH', title: __('学号'),operate:false},
                        {field: 'XN', title: __('学年'),operate: 'LIKE %...%'},
                        {field: 'XQ', title: __('学期'),formatter:function(value){ 
                            if (value ==2) {
                                return "下期"
                            } 
                            return "上期"
                        },searchList: {"1": __('下期'), "2": __('上期')}},
                        {field: 'KCM', title: __('课程名'),operate: 'LIKE %...%'},
                        {field: 'XF', title: __('学分')},
                        {field: 'FSLKSCJ', title: __('分数类考试成绩'),operate:false},
                        {field: 'DJLKSCJ', title: __('等级类考试成绩'),operate:false},
                        {field: 'SFTG', title: __('是否通过'),formatter:function(value){ 
                            if (value == "否") {
                                return "<span class=\"badge bg-red\">"+value+"</span>"
                            } 
                            return value
                        },searchList: {"是": __('是'), "否": __('否')}},
                        {field: 'JD', title: __('绩点'),operate:false},
                    ]
                ],
               
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
            
        },
      
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});