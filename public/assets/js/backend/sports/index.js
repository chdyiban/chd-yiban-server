define(['jquery', 'bootstrap', 'backend', 'table', 'form','bootstrap-daterangepicker','bootstrap-select'], function ($, undefined, Backend, Table, Form) {
    
    var Controller = {
        index: function () {
            // console.log(param);

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'sports/index/index',
                    college_url: 'sports/index/getcollegejson',
                    add_url: 'sports/index/add',
                    add_multi_url: 'sports/index/addmulti',
                    edit_url: 'sports/index/edit',
                    del_url: 'sports/index/del',
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
                        //{field: 'id', title: __('ID'),sortable:true},
                        {field: 'geteventname.event_name', title: __('项目名称'),sortable:true},
                        {field: 'getcollege.YXJC', title: __('院系'), searchList: $.getJSON("sports/index/getcollegejson")},
                        {field: 'ranking', title: __('排名')},                   
                        {field: 'score', title: __('得分')},                   
                        {field: 'remark', title: __('备注')},                   
                        {field: 'operate', width: "160px", title: __('Operate'), table: table, events: Table.api.events.operate,  
                        
                        buttons: [
                                //{name: 'dormitoryinfo', title: __('查看宿舍信息'), classname: 'btn btn-xs btn-primary btn-success btn-dormitory  btn-dialog', icon: 'fa fa-gear', url: 'dormitorysystem/dormitorylist/dormitoryinfo?LH={getrooms.LH}&SSH={getrooms.SSH}',text: __('操作'), callback: function (data){}},      
                            ],     
                        formatter: Table.api.formatter.operate,               
                    }]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            //取消双击编辑
            table.off('dbl-click-row.bs.table');
            $(document).on('click','.btn-add-multi', function () {
                Fast.api.open($.fn.bootstrapTable.defaults.extend.add_multi_url,'多重添加');
            });
            //打开多次添加界面
        },

        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        addmulti: function () {
            $('#get-score').click(function () {
                var baseValue = $('#base-score').val();
                for (let index = 0; index < 8; index++) {
                    var rank = $('#c-ranking-'+index).val();
                    if (rank == '') {
                        $('#c-score-'+index).val();                        
                    } else if (rank == 1) {
                        $('#c-score-'+index).val(baseValue *  (10-rank));
                    } else {
                        $('#c-score-'+index).val(baseValue *  (9-rank));
                    }
                    
                }
            })
            Controller.api.bindevent();
        },


        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },

    };   
    return Controller;
});