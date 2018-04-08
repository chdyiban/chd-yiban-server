define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            var param = $('#fuck-params').text();
           // console.log(param);

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bx/repairlist/index/status/'+param,
                    add_url: 'bx/repairlist/add',
                    //edit_url: 'bx/repairlist/edit',
                    edit_url: '0',
                    del_url: 'bx/repairlist/del',
                    multi_url: 'bx/repairlist/multi',
                    table: 'repair_list',
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
                        {field: 'stu_name', title: __('Stu_name')},
                        {field: 'stu_id', title: __('Stu_id')},                   
                        {field: 'title', title: __('Title')},
                        {field: 'gettype.name', title: __('Service_id')},
                        {field: 'specfic_id', title: __('服务项目')},
                        {field: 'getaddress.name', title: __('Address_id')},
                        {field: 'address', title: __('Address')},
                        // {field: 'content', title: __('Content')},
                        {field: 'getname.nickname', title: __('Admin_id')},
                        // {field: 'accepted_time', title: __('Accepted_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'getworker.name', title: __('Dispatched_id')},
                        // {field: 'dispatched_time', title: __('Dispatched_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'finished_time', title: __('Finished_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'refused_time', title: __('Refused_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'refused_content', title: __('Refused_content')},
                        {field: 'status', title: __('Status'), visible:false, searchList: {"waited":__('status waited'),"accepted":__('status accepted'),"dispatched":__('status dispatched'),"finished":__('status finished'),"refused":__('status refused')}},
                        {field: 'status_text', title: __('Status'), operate:false},
                        {field: 'operate', width: "160px", title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,    
                        
                        buttons: [
                                // {name: 'accept', title: __('受理'), classname: 'btn btn-xs btn-primary btn-info btn-accept', icon: 'fa fa-hand-stop-o', url: 'bx/Repairlist/accept', callback: function (){}},
                                // {name: 'dispatch', title: __('指派工人'), classname: 'btn btn-xs btn-primary btn-dager btn-dialog', icon: 'fa fa-legal', url: 'bx/Repairlist/dispatch', callback: function () {}},
                                // {name: 'finish', title: __('完工'), classname: 'btn btn-xs btn-primary btn-success btn-finish', icon: 'fa fa-hand-peace-o', url: 'bx/Repairlist/finish', callback: function (){}},
                                // {name: 'refuse', title: __('驳回'), classname: 'btn btn-xs btn-primary btn-danger btn-refuse  btn-dialog', icon: 'fa fa-reply-all', url: 'bx/Repairlist/refuse', callback: function (data){}},
                                {name: 'detail', title: __('工单详情'), classname: 'btn btn-xs btn-primary btn-success btn-detail  btn-dialog', icon: 'fa fa-hand-stop-o', url: 'bx/Repairlist/detail', callback: function (data){}},      
                            ],                    
                    }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

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
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },

        detail: function () {
            var ids = $("#id").val();
           //受理方法
            $(document).on('click', '.btn-accept', function () {
                window.location.href="../../accept/ids/"+ids;
                Fast.api.close();
                alert("受理成功，请尽快分配人员！")
                window.parent.location.reload();  
            });    
            //分配人员
            $(document).on('click', '.btn-dispatch', function () {
                window.location.href="../../dispatch/ids/"+ids;
            });   
            //完成任务
            $(document).on('click', '.btn-finish', function () {
                window.location.href="../../finish/ids/"+ids;
                Fast.api.close();
                alert("该任务已经完成！")
                window.parent.location.reload();  
            }); 

            //驳回任务
            //分配人员
            $(document).on('click', '.btn-refuse', function () {
                window.location.href="../../refuse/ids/"+ids;
            }); 
        },

        refuse: function () {
            $(document).on('click', '.btn-refuse', function () {
                Fast.api.close();
                window.parent.location.reload();  
            });          
        },

        dispatch: function () {
            $(document).on('click', '.btn-dispatch', function () {
                Fast.api.close();
                window.parent.location.reload();  
            });          
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
    };   
    return Controller;
});