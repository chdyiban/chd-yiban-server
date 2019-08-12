define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
           // console.log(param);

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'dormitorysystem/dormitoryspecial/index',
                    add_url: '0',
                    //edit_url: 'bx/repairlist/edit',
                    edit_url: '0',
                    del_url: '0',
                    multi_url: '0',
                    table: 'dormitory_special',
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
                        {field: 'ID', title: __('ID'),},
                        {field: 'XH', title: __('学号'),},
                        {field: 'getstuname.XM', title: __('姓名'),},
                        {field: 'old_class', title: __('原班级'),},                   
                        {field: 'new_class', title: __('新班级'),},
                        {field: 'old_dormitory', title: __('原住宿')},
                        {field: 'new_dormitory', title: __('新住宿'),},
                        {field: 'handle_time', title: __('办理时间'),},
                        {field: 'getadminname.nickname', title: __('操作人姓名'),},
                        {field: 'operate_time', title: __('操作时间')},
                        {field: 'remark', title: __('备注'), operate:false},
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
        },
       
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
    };   
    return Controller;
});