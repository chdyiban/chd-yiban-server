define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
           // console.log(param);

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'dormitorysystem/dormitory/index',
                    add_url: 'dormitorysystem/dormitory/add',
                    //edit_url: 'bx/repairlist/edit',
                    edit_url: '0',
                    del_url: '0',
                    multi_url: 'dormitorysystem/dormitory/multi',
                    table: 'dormitory_list',
                }
            });

            var table = $("#table");

            // 初始化表格
             // 初始化表格
             table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'ID',
                sortName: 'ID',
                columns: [
                    [
                        // {checkbox: true},
                        {field: 'ID', title: __('ID'),sortable:true,width:50},
                        {field: 'XQ', title: __('校区')},
                        {field: 'LH', title: __('楼号'),sortable:true,width:60},                   
                        {field: 'LC', title: __('楼层'),sortable:true,width:60},
                        {field: 'LD', title: __('楼段')},
                        {field: 'SSH', title: __('宿舍号'),sortable:true,width:80},
                        {field: 'XH', title: __('学号'),sortable:true},
                        {field: 'getstuname.XM', title: __('姓名'),operate: 'LIKE %...%',},
                        {field: 'getstuname.BJDM', title: __('班级'),operate: 'LIKE %...%',},
                        {field: 'getcollege.YXJC', title: __('院系'), searchList: $.getJSON('dormitorysystem/dormitory/getCollegeJson')},
                        {field: 'NJ', title: __('年级'),sortable:true,width:80},
                        {field: 'XBDM', title: __('性别'),searchList: {"1":__('男'),"2":__('女')},formatter:function(value){
                            if(value == 1) 
                                return "男";
                            if(value == 2) 
                                return "女";
                        }},
                        {field: 'CH', title: __('床号')},
                        {field: 'status', title: __('Status'), visible:false, searchList: {"0":__('空床'),"1":__('正常'),"2":__('损坏')}},
                        {field: 'status_text', title: __('Status'), operate:false},
                    //     {field: 'operate', width: "160px", title: __('Operate'), table: table, events: Table.api.events.operate,  
                        
                    //     buttons: [
                    //             {name: 'stuinfo', title: __('查看学生信息'), classname: 'btn btn-xs btn-primary btn-success btn-stuinfo  btn-dialog', icon: 'fa fa-hand-stop-o', url: 'dormitorysystem/dormitory/getStuInfo?stuid={XH}', callback: function (data){}},      
                    //         ],     
                    //     formatter: Table.api.formatter.operate,               
                    // }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            //取消双击编辑
            table.off('dbl-click-row.bs.table');

            //获取选中项
            $(document).on("click", ".btn-selected", function () {
                Layer.alert(JSON.stringify(table.bootstrapTable('getSelections')));
            });

            // 受理按钮
            $(document).on("click", ".btn-accept", function () {
                //在table外不可以使用添加.btn-change的方法
                //只能自己调用Table.api.multi实现
                //如果操作全部则ids可以置为空
                var ids = Table.api.selectedids(table);
                Table.api.multi("changestatus", ids.join(","), table, this);
            });
        },
        stuinfo:function () {
            
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
    };   
    return Controller;
});