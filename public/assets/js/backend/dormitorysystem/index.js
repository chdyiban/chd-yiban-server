define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'echarts', 'echarts-theme', 'template'], function ($, undefined, Backend, Datatable, Table, Echarts, undefined, Template) {

    var Controller = {
        index: function () {
            // console.log($(":input[id^='detail']").attr('id'));
            // $(":input[id^='detail']").on('click',function () {

            //     //Fast.api.open("./dormitorysystem/index/buildingdetail?LH=1", "宿舍详情",{'area':['800px','400px']},);
            //     Fast.api.open("./dormitorysystem/index/buildingdetail?LH=1", "宿舍详情");
            // });
        }
    };

    return Controller;
});