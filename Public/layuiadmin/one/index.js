$(function () {
    $.ajax({
        type: "GET",
        url: "/Api/Index/ajax_test",
        success: function (result) {
            echart_1(result.one);
            echart_2(result.two);
            echart_3(result.three);
            echart_4(result.four);
        }
    });

    function echart_1(data) {
        // 基于准备好的dom，初始化echarts实例
        var myChart = echarts.init(document.getElementById('chart_1'));
        option = {
            title: {
                text: '设备状态统计',
                top: 35,
                left: 20,
                textStyle: {
                    fontSize: 18,
                    color: '#fff'
                }
            },
            tooltip: {
                trigger: 'item',
                formatter: "{a} <br/>{b}: {c} ({d}%)",

            },
            legend: {
                right: 20,
                top: 35,
                data: ['故障', '正常'],
                textStyle: {
                    color: '#fff'
                }
            },
            series: [{
                name: '设备状态',
                type: 'pie',
                radius: ['0', '60%'],
                center: ['50%', '60%'],
                color: ['#e72325', '#98e002', '#2ca3fd'],
                label: {
                    normal: {
                        formatter: '{b}\n{d}%'
                    },
                },
                data: data
            }]
        };
        // 使用刚指定的配置项和数据显示图表。
        myChart.setOption(option);
        window.addEventListener("resize", function () {
            myChart.resize();
        });
    }

    function echart_2() {
        // 基于准备好的dom，初始化echarts实例
        var myChart = echarts.init(document.getElementById('chart_2'));
        var data = {
            id: 'multipleBarsLines',
            title: '年检测统计',
            legendBar: ['正面占比', '中立占比', '负面占比'],
            symbol: '', //数值是否带百分号        --默认为空 ''
            legendLine: ['同期对比'],
            xAxis: ['1月', '2月', '3月', '4月', '5月', '6月','7月', '8月', '9月', '10月', '11月', '12月'],
            yAxis: [
                [8, 7, 10, 12, 4, 6,3, 9, 10, 13, 4, 1]
            ],
            lines: [
                [8, 7, 10, 12, 4, 6,3, 9, 10, 13, 4, 1]
            ],
            barColor: ['#3FA7DC', '#7091C4', '#5170A2'], //柱子颜色 必填参数
            lineColor: ['#D9523F'], // 折线颜色

        };
        /////////////end/////////

        var myData = (function test() {
            var yAxis = data.yAxis || [];
            var lines = data.lines || [];
            var legendBar = data.legendBar || [];
            var legendLine = data.legendLine || [];
            var symbol = data.symbol || ' ';
            var seriesArr = [];
            var legendArr = [];
            yAxis && yAxis.forEach((item, index) => {
                legendArr.push({
                    name: legendBar && legendBar.length > 0 && legendBar[index]
                });
                seriesArr.push({
                    name: legendBar && legendBar.length > 0 && legendBar[index],
                    type: 'bar',
                    barGap: '0.5px',
                    data: item,
                    barWidth: data.barWidth || 12,
                    label: {
                        normal: {
                            show: true,
                            formatter: '{c}' + symbol,
                            position: 'top',
                            textStyle: {
                                color: '#fff',
                                fontStyle: 'normal',
                                fontFamily: '微软雅黑',
                                textAlign: 'left',
                                fontSize: 11,
                            },
                        },
                    },
                    itemStyle: { //图形样式
                        normal: {
                            barBorderRadius: 4,
                            color: data.barColor[index]
                        },
                    }
                });
            });

            lines && lines.forEach((item, index) => {
                legendArr.push({
                    name: legendLine && legendLine.length > 0 && legendLine[index]
                })
                seriesArr.push({
                    name: legendLine && legendLine.length > 0 && legendLine[index],
                    type: 'line',
                    data: item,
                    itemStyle: {
                        normal: {
                            color: data.lineColor[index],
                            lineStyle: {
                                width: 3,
                                type: 'solid',
                            }
                        }
                    },
                    label: {
                        normal: {
                            show: false, //折线上方label控制显示隐藏
                            position: 'top',
                        }
                    },
                    symbol: 'circle',
                    symbolSize: 10
                });
            });

            return {
                seriesArr,
                legendArr
            };
        })();


        option = {
            title: {
                show: true,
                top: '10%',
                left: '3%',
                text: data.title,
                textStyle: {
                    fontSize: 18,
                    color: '#fff'
                },
                subtext: data.subTitle,
                link: ''
            },
            tooltip: {
                trigger: 'axis',
                formatter: function (params) {
                    var time = '';
                    var str = '';
                    for (var i of params) {
                        time = i.name.replace(/\n/g, '') + '<br/>';
                        if (i.data == 'null' || i.data == null) {
                            str += i.seriesName + '：无数据' + '<br/>'
                        } else {
                            str += i.seriesName + '：' + i.data + symbol + '%<br/>'
                        }

                    }
                    return time + str;
                },
                axisPointer: {
                    type: 'none'
                },
            },
            legend: {
                right: data.legendRight || '30%',
                top: '12%',
                right: '5%',
                itemGap: 16,
                itemWidth: 10,
                itemHeight: 10,
                data: myData.legendArr,
                textStyle: {
                    color: '#fff',
                    fontStyle: 'normal',
                    fontFamily: '微软雅黑',
                    fontSize: 12,
                }
            },
            grid: {
                x: 30,
                y: 80,
                x2: 30,
                y2: 60,
            },
            xAxis: {
                type: 'category',
                data: data.xAxis,
                axisTick: {
                    show: false,
                },

                axisLine: {
                    show: true,
                    lineStyle: {
                        color: '#1AA1FD',
                    },
                    symbol: ['none', 'arrow']
                },
                axisLabel: {
                    show: true,
                    interval: '0',
                    textStyle: {
                        lineHeight: 16,
                        padding: [2, 2, 0, 2],
                        height: 50,
                        fontSize: 12,
                    },
                    rich: {
                        Sunny: {
                            height: 50,
                            // width: 60,
                            padding: [0, 5, 0, 5],
                            align: 'center',
                        },
                    },
                    formatter: function (params, index) {
                        var newParamsName = "";
                        var splitNumber = 5;
                        var paramsNameNumber = params && params.length;
                        if (paramsNameNumber && paramsNameNumber <= 4) {
                            splitNumber = 4;
                        } else if (paramsNameNumber >= 5 && paramsNameNumber <= 7) {
                            splitNumber = 4;
                        } else if (paramsNameNumber >= 8 && paramsNameNumber <= 9) {
                            splitNumber = 5;
                        } else if (paramsNameNumber >= 10 && paramsNameNumber <= 14) {
                            splitNumber = 5;
                        } else {
                            params = params && params.slice(0, 15);
                        }

                        var provideNumber = splitNumber; //一行显示几个字
                        var rowNumber = Math.ceil(paramsNameNumber / provideNumber) || 0;
                        if (paramsNameNumber > provideNumber) {
                            for (var p = 0; p < rowNumber; p++) {
                                var tempStr = "";
                                var start = p * provideNumber;
                                var end = start + provideNumber;
                                if (p == rowNumber - 1) {
                                    tempStr = params.substring(start, paramsNameNumber);
                                } else {
                                    tempStr = params.substring(start, end) + "\n";
                                }
                                newParamsName += tempStr;
                            }

                        } else {
                            newParamsName = params;
                        }
                        params = newParamsName;
                        return '{Sunny|' + params + '}';
                    },
                    color: '#1AA1FD',
                },

            },
            yAxis: {
                axisLine: {
                    show: true,
                    lineStyle: {
                        color: '#1AA1FD',
                    },
                    symbol: ['none', 'arrow']
                },
                type: 'value',
                axisTick: {
                    show: false
                },
                axisLabel: {
                    show: false
                },
                splitLine: {
                    show: false,
                    lineStyle: {
                        color: '#1AA1FD',
                        type: 'solid'
                    },
                }
            },
            series: myData.seriesArr
        }
        // 使用刚指定的配置项和数据显示图表。
        myChart.setOption(option);
        window.addEventListener("resize", function () {
            myChart.resize();
        });
    }

    function echart_3() {
        // 基于准备好的dom，初始化echarts实例
        var myChart = echarts.init(document.getElementById('chart_3'));
        var mapName = 'china'
        var data = []
        var toolTipData = [];

        /*获取地图数据*/
        myChart.showLoading();
        var mapFeatures = echarts.getMap(mapName).geoJson.features;
        myChart.hideLoading();
        var geoCoordMap = {
            '福州': [119.4543, 25.9222],
            '长春': [125.8154, 44.2584],
            '重庆': [107.7539, 30.1904],
            '西安': [109.1162, 34.2004],
            '成都': [103.9526, 30.7617],
            '常州': [119.4543, 31.5582],
            '北京': [116.4551, 40.2539],
            '天津': [117.1993, 39.0851],
            '郑州': [113.6249, 34.7472],
            '海口': [110.3893, 19.8516],
            '长沙': [113.0194, 28.2001],
            '上海': [121.4000, 31.7300],
            '内蒙古': [106.82, 39.67]
        };

        var GZData = [
            [{
                name: '天津'
            }, {
                name: '福州',
                value: 95
            }],
            [{
                name: '天津'
            }, {
                name: '长春',
                value: 80
            }],
            [{
                name: '天津'
            }, {
                name: '重庆',
                value: 70
            }],
            [{
                name: '天津'
            }, {
                name: '西安',
                value: 60
            }],
            [{
                name: '天津'
            }, {
                name: '成都',
                value: 50
            }],
            [{
                name: '天津'
            }, {
                name: '常州',
                value: 40
            }],
            [{
                name: '天津'
            }, {
                name: '北京',
                value: 30
            }],
            [{
                name: '天津'
            }, {
                name: '郑州',
                value: 20
            }],
            [{
                name: '天津'
            }, {
                name: '海口',
                value: 10
            }],
            [{
                name: '天津'
            }, {
                name: '上海',
                value: 80
            }],
            [{
                name: '天津'
            }, {
                name: '内蒙古',
                value: 80
            }]
        ];

        var convertData = function (data) {
            var res = [];
            for (var i = 0; i < data.length; i++) {
                var dataItem = data[i];
                var fromCoord = geoCoordMap[dataItem[0].name];
                var toCoord = geoCoordMap[dataItem[1].name];
                if (fromCoord && toCoord) {
                    res.push({
                        fromName: dataItem[0].name,
                        toName: dataItem[1].name,
                        coords: [fromCoord, toCoord]
                    });
                }
            }
            return res;
        };

        var color = ['#c5f80e'];
        var series = [];
        [
            ['', GZData]
        ].forEach(function (item, i) {
            series.push({
                name: item[0],
                type: 'lines',
                zlevel: 2,
                symbol: ['none', 'arrow'],
                symbolSize: 10,
                effect: {
                    show: true,
                    period: 6,
                    trailLength: 0,
                    symbol: 'arrow',
                    symbolSize: 5
                },
                lineStyle: {
                    normal: {
                        color: color[i],
                        width: 1,
                        opacity: 0.6,
                        curveness: 0.2
                    }
                },
                data: convertData(item[1])
            }, {
                name: item[0],
                type: 'effectScatter',
                coordinateSystem: 'geo',
                zlevel: 2,
                rippleEffect: {
                    brushType: 'stroke'
                },
                label: {
                    normal: {
                        show: true,
                        position: 'right',
                        formatter: '{b}'
                    }
                },
                symbolSize: function (val) {
                    return val[2] / 8;
                },
                itemStyle: {
                    normal: {
                        color: color[i]
                    }
                },
                data: item[1].map(function (dataItem) {
                    return {
                        name: dataItem[1].name,
                        value: geoCoordMap[dataItem[1].name].concat([dataItem[1].value])
                    };
                })
            });
        });

        option = {
            tooltip: {
                trigger: 'item'
            },
            geo: {
                map: 'china',
                label: {
                    emphasis: {
                        show: false
                    }
                },
                roam: true,
                itemStyle: {
                    normal: {
                        borderColor: 'rgba(147, 235, 248, 1)',
                        borderWidth: 1,
                        areaColor: {
                            type: 'radial',
                            x: 0.5,
                            y: 0.5,
                            r: 0.8,
                            colorStops: [{
                                offset: 0,
                                color: 'rgba(175,238,238, 0)' // 0% 处的颜色
                            }, {
                                offset: 1,
                                color: 'rgba(47,79,79, .1)' // 100% 处的颜色
                            }],
                            globalCoord: false // 缺省为 false
                        },
                        shadowColor: 'rgba(128, 217, 248, 1)',
                        // shadowColor: 'rgba(255, 255, 255, 1)',
                        shadowOffsetX: -2,
                        shadowOffsetY: 2,
                        shadowBlur: 10
                    },
                    emphasis: {
                        areaColor: '#389BB7',
                        borderWidth: 0
                    }
                }
            },
            series: series
        };

        // 使用刚指定的配置项和数据显示图表。
        myChart.setOption(option);
        window.addEventListener("resize", function () {
            myChart.resize();
        });

    }

    function echart_4() {
        // 基于准备好的dom，初始化echarts实例
        var myChart = echarts.init(document.getElementById('chart_4'));
        var data = [70, 34, 60, 78, 69];
        var titlename = ['1号机', '2号机', '3号机', '4号机', '5号机'];
        var valdata = [702, 406, 664, 793, 505];
        var myColor = ['#1089E7', '#F57474', '#56D0E3', '#F8B448', '#8B78F6'];
        option = {
            title: {
                text: '设备使用频率',
                x: 'center',
                textStyle: {
                    color: '#FFF'
                },
                left: '6%',
                top: '10%'
            },
            //图标位置
            grid: {
                top: '20%',
                left: '32%'
            },
            xAxis: {
                show: false
            },
            yAxis: [{
                show: true,
                data: titlename,
                inverse: true,
                axisLine: {
                    show: false
                },
                splitLine: {
                    show: false
                },
                axisTick: {
                    show: false
                },
                axisLabel: {
                    color: '#fff',
                    formatter: (value, index) => {
                        return [

                            `{lg|${index+1}}  ` + '{title|' + value + '} '
                        ].join('\n')
                    },
                    rich: {
                        lg: {
                            backgroundColor: '#339911',
                            color: '#fff',
                            borderRadius: 15,
                            // padding: 5,
                            align: 'center',
                            width: 15,
                            height: 15
                        },
                    }
                },


            }, {
                show: true,
                inverse: true,
                data: valdata,
                axisLabel: {
                    textStyle: {
                        fontSize: 12,
                        color: '#fff',
                    },
                },
                axisLine: {
                    show: false
                },
                splitLine: {
                    show: false
                },
                axisTick: {
                    show: false
                },

            }],
            series: [{
                name: '条',
                type: 'bar',
                yAxisIndex: 0,
                data: data,
                barWidth: 10,
                itemStyle: {
                    normal: {
                        barBorderRadius: 20,
                        color: function(params) {
                            var num = myColor.length;
                            return myColor[params.dataIndex % num]
                        },
                    }
                },
                label: {
                    normal: {
                        show: true,
                        position: 'inside',
                        formatter: '{c}%'
                    }
                },
            }, {
                name: '框',
                type: 'bar',
                yAxisIndex: 1,
                barGap: '-100%',
                data: [100, 100, 100, 100, 100],
                barWidth: 15,
                itemStyle: {
                    normal: {
                        color: 'none',
                        borderColor: '#00c1de',
                        borderWidth: 3,
                        barBorderRadius: 15,
                    }
                }
            }, ]
        };
        // 使用刚指定的配置项和数据显示图表。
        myChart.setOption(option);
        // window.addEventListener("resize", function () {
        //     myChart.resize();
        // });
    }

});