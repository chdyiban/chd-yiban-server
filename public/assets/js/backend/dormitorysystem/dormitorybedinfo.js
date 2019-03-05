define(['jquery', 'bootstrap', 'backend', 'table', 'form','bootstrap-daterangepicker','bootstrap-select'], function ($, undefined, Backend, Table, Form) {
    
    var Controller = {
        index: function () {
            // console.log(param);

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'dormitorysystem/dormitorybedinfo/index',
                    import_my_url: 'dormitorysystem/dormitorybedinfo/import',
                    //临时展示插入数据地址
                    show_temp_url: 'dormitorysystem/dormitorybedinfo/showtemp',
                    //edit_url: 'bx/repairlist/edit',
                    // edit_url: '0',
                    // del_url: '0',
                    // multi_url: 'dormitorysystem/dormitorylist/multi',
                    // free_bed_url: 'dormitorysystem/dormitorylist/freebed',
                    //table: 'dormitory_list',
                }
            });

            var table = $("#table");
            
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                //pk: 'ID',
                //sortName: 'ID',
                columns: [
                    [
                        {checkbox: true},
                        //{field: 'ID', title: __('ID'),sortable:true,width:50},
                        {field: 'getstuname.XM', title: __('姓名')},
                        {field: 'getstuname.XH', title: __('学号'),width:60},                   
                        {field: 'getstuname.BJDM', title: __('班级'),width:60},                   
                        {field: 'getcollege.YXJC', title: __('学院'),width:80},                   
                        {field: 'getrooms.XQ', title: __('校区'),width:60},
                        {field: 'getrooms.LH', title: __('楼号')},
                        {field: 'getrooms.LC', title: __('楼层'),width:80},
                        {field: 'getrooms.LD', title: __('楼段'),width:80},
                        {field: 'getrooms.SSH', title: __('宿舍号'),width:80},
                        {field: 'CH', title: __('床位号'),width:80},
                        {field: 'getstuname.XBDM', title: __('性别'),searchList: {"1":__('男'),"2":__('女')},formatter:function(value){
                            if(value == 1) 
                                return "男";
                            if(value == 2) 
                                return "女";
                        }},
                        {field: 'operate', width: "160px", title: __('Operate'), table: table, events: Table.api.events.operate,  
                        
                        buttons: [
                                {name: 'dormitoryinfo', title: __('查看宿舍信息'), classname: 'btn btn-xs btn-primary btn-success btn-dormitory  btn-dialog', icon: 'fa fa-gear', url: 'dormitorysystem/dormitorylist/dormitoryinfo?LH={getrooms.LH}&SSH={getrooms.SSH}',text: __('操作'), callback: function (data){}},      
                            ],     
                        formatter: Table.api.formatter.operate,               
                    }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            //取消双击编辑
            table.off('dbl-click-row.bs.table');
            if ($('.btn-my-import', ".toolbar").size() > 0) {
                require(['upload'], function (Upload) {
                    Upload.api.plupload($(".btn-my-import", ".toolbar"), function (data, ret) {
                        $.ajax({
                            type: 'POST',
                            url: $.fn.bootstrapTable.defaults.extend.import_my_url,
                            data: {file: data.url},
                            success: function(data) {
                                if (data.code == 0) {
                                    Fast.events.onAjaxError(data);
                                }
                                if (data.status == true) {
                                    Fast.api.open($.fn.bootstrapTable.defaults.extend.show_temp_url,'核查导入数据',);
                                } else {
                                    Fast.api.open($.fn.bootstrapTable.defaults.extend.import_my_url,'',{'type': 1,'content': data});
                                }
                            },
                        });
                    });
                });
            }
        },
        showtemp: function () {
            Table.api.init({
                extend: {
                    index_url: 'dormitorysystem/dormitorybedinfo/showtemp',
                }
            });
            var table = $("#table");
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                search: false,      
                showExport: false,
                showToggle: false,
                showColumns: false,
                columns: [
                    [
                        {field: 'getstuname.XM', title: __('姓名')},
                        {field: 'getstuname.XH', title: __('学号'),},                   
                        {field: 'getstuname.BJDM', title: __('班级'),},                   
                        {field: 'getcollege.YXJC', title: __('学院'),},                   
                        {field: 'getrooms.XQ', title: __('校区'),},
                        {field: 'getrooms.LH', title: __('楼号')},
                        {field: 'getrooms.LC', title: __('楼层'),},
                        {field: 'getrooms.LD', title: __('楼段'),},
                        {field: 'getrooms.SSH', title: __('宿舍号'),},
                        {field: 'CH', title: __('床位号')},
                        {field: 'getstuname.XBDM', title: __('性别'),searchList: {"1":__('男'),"2":__('女')},formatter:function(value){
                            if(value == 1) 
                                return "男";
                            if(value == 2) 
                                return "女";
                        }},
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            //取消双击编辑
            table.off('dbl-click-row.bs.table');
            //确定导入
            $('#confirminsert').on('click',function () {
                postdata = {
                    'type' : "insert",
                }
                $.ajax({
                    type: 'POST',
                    url: './dormitorysystem/Dormitorybedinfo/confirmInsert',
                    data: postdata,
                    success: function(data) {
                        if (data.status == true && data.type == "insert") {
                            alert(data.msg);
                            Fast.api.close();
                            window.parent.location.reload();
                        } else{
                            alert(data.msg);
                        }
                    }
                });
            });
            //取消导入
            $('#confirmcancel').on('click',function () {
                postdata = {
                    'type' : "cancel",
                }
                $.ajax({
                    type: 'POST',
                    url: './dormitorysystem/Dormitorybedinfo/confirmInsert',
                    data: postdata,
                    success: function(data) {
                        if (data.status == true && data.type == "cancel") {
                            alert(data.msg);
                            Fast.api.close();
                            window.parent.location.reload();
                        } else{
                            alert(data.msg);
                        }
                    }
                });
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