define(['jquery', 'bootstrap', 'backend', 'table', 'form','chart'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'conversationrecord/count/index',
                    add_url: 'conversationrecord/count/add',
                    edit_url: 'conversationrecord/count/edit',
                    del_url: 'conversationrecord/count/del',
                    multi_url: 'conversationrecord/count/multi',
                }
            });

            var table = $("#table");

            table.on('load-success.bs.table', function (e,value,data) {
                valueList = {}
                $.each(value.rows,function (i,v) {
                    valueList[i] = v["THNR"];
                    // $(this).find("td:eq(2)").html(1);
                })
                $("#table tbody tr").each(function(i,v){
                    $(this).find("td:eq(4)").html(valueList[i]);
                    $(this).find("td:eq(4)").attr("style","text-align:left");
                });
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                showToggle: false,
                showColumns: false,
                commonSearch: false,
                search: false,
                showExport: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'THSJ', title: __('谈话时间'),width:100,formatter:function(value,row){ 
                            if (value == 0) {
                                return "无";
                            } else{
                                var now = new Date(value*1000);
                                var time = now.getFullYear() + "-" +((now.getMonth()+1)<10?"0":"")+(now.getMonth()+1)+"-"+(now.getDate()<10?"0":"")+now.getDate();
                                return time; 
                            }
                        }},
                        {field: 'XH', title: __('谈话人学号'),width:60},
                        {field: 'XM', title: __('谈话人姓名'),width:40},
                        {field: 'THNR', title: __('谈话内容')},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            
            $(function () {
                $.ajax({
                    type: 'GET',
                    url: './conversationrecord/count/getChartData',
                    // data: postData,
                    success: function(data) {                  
                        /* ChartJS
                        * -------
                        * Here we will create a few charts using ChartJS
                        */
                    
                        // Get context with jQuery - using jQuery's .get() method.
                        var areaChartCanvas = $("#areaChart").get(0).getContext("2d");

                        // This will get the first returned node in the jQuery collection.
                        var areaChart = new Chart(areaChartCanvas);
                    
                        var areaChartData = {
                            labels: data.label,
                            datasets: [
                                {
                                    label: "谈话学生数",
                                    fillColor: "rgba(220,220,220,0.2)",
                                    strokeColor: "rgba(220,220,220,1)",
                                    pointColor: "rgba(220,220,220,1)",
                                    pointStrokeColor: "#fff",
                                    pointHighlightFill: "#fff",
                                    pointHighlightStroke: "rgba(220,220,220,1)",
                                    data: data.stuCount,
                                },
                                {
                                    label: "谈话次数",
                                    fillColor: "rgba(151,187,205,0.2)",
                                    strokeColor: "rgba(151,187,205,1)",
                                    pointColor: "rgba(151,187,205,1)",
                                    pointStrokeColor: "#fff",
                                    pointHighlightFill: "#fff",
                                    pointHighlightStroke: "rgba(151,187,205,1)",
                                    data: data.numCount,
                                }
                            ]
                        };
                    
                        var areaChartOptions = {
                            //Boolean - If we should show the scale at all横纵坐标轴
                            showScale: true,
                            //Boolean - Whether grid lines are shown across the chart
                            scaleShowGridLines: false,
                            //String - Colour of the grid lines
                            scaleGridLineColor: "rgba(0,10,251,1.5)",
                            //Number - Width of the grid lines
                            scaleGridLineWidth: 1,
                            //Boolean - Whether to show horizontal lines (except X axis)
                            scaleShowHorizontalLines: true,
                            //Boolean - Whether to show vertical lines (except Y axis)
                            scaleShowVerticalLines: true,
                            //Boolean - Whether the line is curved between points
                            bezierCurve: true,
                            //Number - Tension of the bezier curve between points
                            bezierCurveTension: 0.3,
                            //Boolean - Whether to show a dot for each point
                            pointDot: true,
                            //Number - Radius of each point dot in pixels
                            pointDotRadius: 4,
                            //Number - Pixel width of point dot stroke
                            pointDotStrokeWidth: 1,
                            //Number - amount extra to add to the radius to cater for hit detection outside the drawn point
                            pointHitDetectionRadius: 20,
                            //Boolean - Whether to show a stroke for datasets
                            datasetStroke: true,
                            //Number - Pixel width of dataset stroke
                            datasetStrokeWidth: 2,
                            //Boolean - Whether to fill the dataset with a color
                            datasetFill: true,
                            //String - A legend template
                            legendTemplate: "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].lineColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>",
                            //Boolean - whether to maintain the starting aspect ratio or not when responsive, if set to false, will take up entire container
                            maintainAspectRatio: true,
                            //Boolean - whether to make the chart responsive to window resizing
                            responsive: true
                        };
                    
                        //Create the line chart
                        areaChart.Line(areaChartData, areaChartOptions);
                    }
                });
            })
            
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