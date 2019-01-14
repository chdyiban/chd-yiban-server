define(['jquery', 'bootstrap', 'backend', 'table', 'form','bootstrap-daterangepicker','bootstrap-select'], function ($, undefined, Backend, Table, Form) {
    
    var Controller = {
        index: function () {
            // console.log(param);

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'dormitorysystem/dormitorypubliclist/index',
                    add_url: 'dormitorysystem/dormitorypubliclist/add',
                    //edit_url: 'bx/repairlist/edit',
                    edit_url: 'dormitorysystem/dormitorypubliclist/edit',
                    del_url: '',
                    multi_url: 'dormitorysystem/dormitorylist/multi',
                    free_bed_url: 'dormitorysystem/dormitorylist/freebed',
                    table: 'dormitory_list',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'ID',
                //sortName: 'ID',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'ID', title: __('ID'),visible:false },
                        {field: 'XQ', title: __('校区')},
                        {field: 'LH', title: __('楼号'),sortable:true,width:60},                   
                        {field: 'LC', title: __('楼层'),sortable:true,width:60},
                        {field: 'LD', title: __('楼段')},
                        {field: 'SSH', title: __('宿舍号'),sortable:true,width:80},
                        // {field: 'RZQK', title: __('入住情况'),operate:false,formatter:function(value,row,index){}},
                        // {field: 'RZBL', title: __('入住比例(入住/总床位)'),operate:false,formatter:function(value,row,index){}},
                        {field: 'GYFMC', title: __('公用房名称')},
                        {field: 'operate', width: "160px", title: __('Operate'), table: table, events: Table.api.events.operate,  
                        
                            buttons: [
                                    {name: 'dormitoryinfo', title: __('查看宿舍信息'), classname: 'btn btn-xs btn-primary btn-success btn-dormitory  btn-dialog', icon: 'fa fa-gear', url: 'dormitorysystem/dormitorylist/dormitoryinfo?LH={LH}&SSH={SSH}',text: __('操作'), callback: function (data){}},      
                                    {name: 'delete', title: __('删除'), classname: 'btn  btn-xs btn-primary btn-danger  btn-ajax', icon: 'fa fa-trash', url: 'dormitorysystem/dormitorypubliclist/delete',text: __('删除'),confirm:"确定删除", success: function (data){
                                        $(".btn-refresh").trigger("click");
                                    }},      
                                ],     
                            formatter: Table.api.formatter.operate,               
                        }
                    ]
                ]
            });
            $('.btn-delete').click(function () {
                
            })
            // 为表格绑定事件
            Table.api.bindevent(table);
            //取消双击编辑
            table.off('dbl-click-row.bs.table');

        },

        add: function () {
            $('#roomStatus').change(function(){
                var roomStatus = $('#roomStatus').val();
                if  (roomStatus == '1'){
                    $('.roomName').addClass('hidden');
                    $('.stuRoom').removeClass('hidden');
                    //为相应表单开启不验证
                    $('#c-roomName').attr('novalidate','true');
                    $('#c-XB').removeAttr('novalidate');
                    $('#c-CWS').removeAttr('novalidate');
                } else if(roomStatus == '2'){
                    $('.roomName').removeClass('hidden');
                    $('.stuRoom').addClass('hidden');
                    $('#c-roomName').removeAttr('novalidate');
                    $('#c-XB').attr('novalidate','true');
                    $('#c-CWS').attr('novalidate','true');
                } else if(roomStatus == '0') {
                    $('.roomName').addClass('hidden');
                    $('.stuRoom').addClass('hidden');
                    $('#c-XB').removeAttr('novalidate');
                    $('#c-CWS').removeAttr('novalidate');
                    $('#c-roomName').removeAttr('novalidate');
                }
            });
            Controller.api.bindevent();
        },

        edit: function () {
            $('#roomStatus').change(function(){
                var roomStatus = $('#roomStatus').val();
                if  (roomStatus == '1'){
                    $('.roomName').addClass('hidden');
                    $('.stuRoom').removeClass('hidden');
                    //为相应表单开启不验证
                    $('#c-roomName').attr('novalidate','true');
                    $('#c-XB').removeAttr('novalidate');
                    $('#c-CWS').removeAttr('novalidate');
                } else if(roomStatus == '2'){
                    $('.roomName').removeClass('hidden');
                    $('.stuRoom').addClass('hidden');
                    $('#c-roomName').removeAttr('novalidate');
                    $('#c-XB').attr('novalidate','true');
                    $('#c-CWS').attr('novalidate','true');
                } else if(roomStatus == '0') {
                    $('.roomName').addClass('hidden');
                    $('.stuRoom').addClass('hidden');
                    $('#c-XB').removeAttr('novalidate');
                    $('#c-CWS').removeAttr('novalidate');
                    $('#c-roomName').removeAttr('novalidate');
                }
            });
            Controller.api.bindevent();
        },
        
        api: {
            //不验证被隐藏的字段参考https://forum.fastadmin.net/thread/1203
            bindevent: function () {
                // $('form[role=form]').validator({
                //     ignore: ':hidden'
                // });
                Form.api.bindevent($("form[role=form]"));
            }
        },
    };   
    return Controller;
});