define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
           // console.log(param);

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'dormitorysystem/confirmdistribute/index',
                    add_url: 'dormitorysystem/confirmdistribute/add',
                    //edit_url: 'bx/repairlist/edit',
                    edit_url: '0',
                    del_url: '0',
                    multi_url: 'dormitorysystem/confirmdistribute/multi',
                    table: 'dormitory_list',
                }
            });

            var table = $("#table");

            // 初始化表格
             table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                // pk: 'ID',
                // sortName: 'ID',
                //关闭快速搜索模式
                search:false,
                //取消浏览模式
                showToggle: false,
                //取消显示隐藏列
                showColumns: false,
                //取消导出
                showExport: false,
                searchFormVisible: true,
                searchFormTemplate: 'customformtpl',
                columns: [
                    [                    
                        // {checkbox: true},
                        {field: 'XH', title: __('学号'),sortable:true},
                        {field: 'XM', title: __('姓名')},
                        {field: 'XB', title: __('性别')},
                        {field: 'YXJC', title: __('学院')},                   
                        {field: 'YXDM', title: __('学院'), visible: false},                   
                        {field: 'NJ', title: __('年级'),sortable:true},
                        {field: 'operate', width: "160px", title: __('Operate'), table: table, events: Table.api.events.operate,  
                        
                        buttons: [
                                {
                                    name: 'conifrmdistribute', 
                                    title: __('确定分配'), 
                                    classname: 'btn  btn-success btn-confirmdistribute', 
                                    icon: 'fa fa-hand-stop-o',
                                    hidden:function(row){
                                        //console.log(row.length);
                                        if (row.length === 0) {
                                            return true;
                                        }
                                    },
                                },      
                            ],
                        //参考材料https://forum.fastadmin.net/thread/1762
                        //以及参考https://forum.fastadmin.net/thread/6658
                        //进行自定义按钮方法
                        events: Controller.api.events.operate,

                        formatter: Controller.api.formatter.operate,               
                    }
                    ]
                ]
            });
            
            // 为表格绑定事件
            Table.api.bindevent(table);
            //取消双击编辑
            table.off('dbl-click-row.bs.table');
    
        },

        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },

            events:{
                operate:{
                    'click .btn-confirmdistribute':function (e,value,row,index) {
                        var message = confirm("是否确认将此学生分配至该床位？");
                        if (message) {
                            var LH = $('#LH').text();
                            var CH = $('#CH').text();
                            var SSH = $('#SSH').text();
                            var XH = row.XH;
                            var XB = row.XB;
                            var YXDM = row.YXDM;
                            $.ajax({
                                type: 'POST',
                                url: './dormitorysystem/Dormitorylist/addStuRecord',
                                data: {
                                    'XH':XH,
                                    'LH':LH,
                                    'SSH':SSH,
                                    'CH':CH,
                                    'XB':XB,
                                    'YXDM':YXDM,
                                },
                                success: function(data) {
                                    if (data.status == true) {
                                        alert(data.msg);
                                        Fast.api.close();
                                        window.parent.location.reload();
                                    } else{
                                        alert(data.msg);
                                    }
                                }
                            });
                        }
                    }
                }
            },

            formatter:{
                operate:function (value,row,index) {
                    var table = this.table;
                    //操作配置
                    var options = table ? table.bootstrapTable('getOptions') : {};
                    //默认按钮组
                    var buttons = $.extend([], this.buttons || []);

                    return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                }
            }
        },
    };   
    return Controller;
});