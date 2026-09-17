(function($) {
    'use strict';
    
    $(function() {

    // basic line Chart
    // --------------------------------------------------------------------
    const lineChartEl = document.querySelector('#basicLineChart'),
        lineChartConfig = {
        chart: {
            height: 400,
            type: 'line',
            parentHeightOffset: 0,
            toolbar: {
            show: false
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        legend: {
            show: true,
            position: 'top',
            horizontalAlign: 'start',
            labels: {
            colors: 'var(--clr-text-custom-body',
            useSeriesColors: false
            }
        },
        grid: {
            borderColor: 'var(--bs-border-color)',
            xaxis: {
            lines: {
                show: true
            }
            }
        },
        colors: ['#008FFB', '#00E396', '#FF4560'],
        series: [
            {
            name: 'High - 2018',
            data: [28, 29, 33, 36, 32, 32, 33]
            },
            {
            name: 'Low - 2018',
            data: [12, 11, 14, 18, 17, 13, 13]
            },
            {
            name: 'High - 2019',
            data: [40, 40, 42, 40, 39, 48, 50]
            }
        ],
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
            axisBorder: {
            show: false
            },
            axisTicks: {
            show: false
            },
            labels: {
            style: {
                colors: '#000',
                fontSize: '13px'
            }
            }
        },
        yaxis: {
            labels: {
            style: {
                colors: '#000',
                fontSize: '13px'
            }
            }
        },
        markers: {
            size: 5,
            colors: ['#00E396', '#008FFB', '#FF4560'],
            strokeColors: '#fff',
            strokeWidth: 2
        },
        tooltip: {
            shared: false,
            intersect: true
        }
        };
    if (typeof lineChartEl !== undefined && lineChartEl !== null) {
        const lineChart = new ApexCharts(lineChartEl, lineChartConfig);
        lineChart.render();
    }

    // line chart with data labels
    // --------------------------------------------------------------------
    const lineDataLabelChartEl = document.querySelector('#lineDataLabelChart'),
        lineDataLabelChartConfig = {
        chart: {
            height: 400,
            type: 'line',
            parentHeightOffset: 0,
            toolbar: {
            show: false
            }
        },
        dataLabels: {
            enabled: true
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        legend: {
            show: true,
            position: 'top',
            horizontalAlign: 'start',
            labels: {
            colors: '#bfc9d4',
            useSeriesColors: false
            }
        },
        grid: {
            borderColor: '#f1f3fa',
            xaxis: {
            lines: {
                show: true
            }
            }
        },
        colors: ['#008FFB', '#00E396', '#FF4560'],
        series: [
            {
            name: 'High - 2018',
            data: [28, 29, 33, 36, 32, 32, 33]
            },
            {
            name: 'Low - 2018',
            data: [12, 11, 14, 18, 17, 13, 13]
            },
            {
            name: 'High - 2019',
            data: [40, 40, 42, 40, 39, 48, 50]
            }
        ],
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
            axisBorder: {
            show: false
            },
            axisTicks: {
            show: false
            },
            labels: {
            style: {
                colors: '#000',
                fontSize: '13px'
            }
            }
        },
        yaxis: {
            labels: {
            style: {
                colors: '#000',
                fontSize: '13px'
            }
            }
        },
        markers: {
            size: 5,
            colors: ['#00E396', '#008FFB', '#FF4560'],
            strokeColors: '#fff',
            strokeWidth: 2
        },
        tooltip: {
            shared: false,
            intersect: true
        }
        };

    if (typeof lineDataLabelChartEl !== undefined && lineDataLabelChartEl !== null) {
        const lineDataLabelChart = new ApexCharts(lineDataLabelChartEl, lineDataLabelChartConfig);
        lineDataLabelChart.render();
    }


    // Line Chart with Zoomable Time Series
    // --------------------------------------------------------------------
    var dates = [
        { x: new Date('2024-01-01').getTime(), y: 1000000 },
        { x: new Date('2024-01-02').getTime(), y: 1100000 },
        { x: new Date('2024-01-03').getTime(), y: 1050000 },
        { x: new Date('2024-01-04').getTime(), y: 1200000 },
        { x: new Date('2024-01-05').getTime(), y: 1150000 },
        { x: new Date('2024-01-06').getTime(), y: 1300000 },
        { x: new Date('2024-01-07').getTime(), y: 1250000 },
        { x: new Date('2024-01-08').getTime(), y: 1400000 },
        { x: new Date('2024-01-09').getTime(), y: 1450000 },
        { x: new Date('2024-01-10').getTime(), y: 1500000 }
    ];

    var zoomOptions = {
        series: [{
            name: 'XYZ MOTORS',
            data: dates
        }],
        chart: {
            type: 'area',
            stacked: false,
            height: 350,
            zoom: {
                type: 'x',
                enabled: true,
                autoScaleYaxis: true
            },
            toolbar: {
                autoSelected: 'zoom'
            }
        },
        dataLabels: {
            enabled: false
        },
        markers: {
            size: 0,
        },
        title: {
            text: 'Stock Price Movement',
            align: 'left'
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                inverseColors: false,
                opacityFrom: 0.5,
                opacityTo: 0,
                stops: [0, 90, 100]
            },
        },
        yaxis: {
            labels: {
                formatter: function (val) {
                    return (val / 1000000).toFixed(0);
                },
            },
            title: {
                text: 'Price'
            },
        },
        xaxis: {
            type: 'datetime',
        },
        tooltip: {
            shared: false,
            y: {
                formatter: function (val) {
                    return (val / 1000000).toFixed(0);
                }
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#zoomableChart"), zoomOptions);
    chart.render();


    // Candlestick Chart
    // --------------------------------------------------------------------
    var candleOptions = {
        series: [{
            data: [
                { x: new Date(1538778600000), y: [6629.81, 6650.5, 6623.04, 6633.33] },
                { x: new Date(1538780400000), y: [6632.01, 6643.59, 6620, 6630.11] },
                { x: new Date(1538782200000), y: [6630.71, 6648.95, 6623.34, 6635.65] },
                { x: new Date(1538784000000), y: [6635.65, 6651, 6629.67, 6638.24] },
                { x: new Date(1538785800000), y: [6638.24, 6640, 6620, 6624.47] },
                { x: new Date(1538787600000), y: [6624.53, 6636.03, 6621.68, 6624.31] },
                { x: new Date(1538789400000), y: [6624.61, 6632.2, 6617, 6626.02] },
                { x: new Date(1538791200000), y: [6627, 6627.62, 6584.22, 6603.02] },
                { x: new Date(1538793000000), y: [6605, 6608.03, 6598.95, 6604.01] },
                { x: new Date(1538794800000), y: [6604.5, 6614.4, 6602.26, 6608.02] },
                { x: new Date(1538796600000), y: [6608.02, 6610.68, 6601.99, 6608.91] },
                { x: new Date(1538798400000), y: [6608.91, 6618.99, 6608.01, 6612] },
                { x: new Date(1538800200000), y: [6612, 6615.13, 6605.09, 6612] },
                { x: new Date(1538802000000), y: [6612, 6624.12, 6608.43, 6622.95] },
                { x: new Date(1538803800000), y: [6623.91, 6623.91, 6615, 6615.67] },
                { x: new Date(1538805600000), y: [6618.69, 6618.74, 6610, 6610.4] },
                { x: new Date(1538807400000), y: [6611, 6622.78, 6610.4, 6614.9] },
                { x: new Date(1538809200000), y: [6614.9, 6626.2, 6613.33, 6623.45] },
                { x: new Date(1538811000000), y: [6623.48, 6627, 6618.38, 6620.35] },
                { x: new Date(1538812800000), y: [6619.43, 6620.35, 6610.05, 6615.53] },
                { x: new Date(1538814600000), y: [6615.53, 6617.93, 6610, 6615.19] },
                { x: new Date(1538816400000), y: [6615.19, 6621.6, 6608.2, 6620] },
                { x: new Date(1538818200000), y: [6619.54, 6625.17, 6614.15, 6620] },
                { x: new Date(1538820000000), y: [6620.33, 6634.15, 6617.24, 6624.61] },
                { x: new Date(1538821800000), y: [6625.95, 6626, 6611.66, 6617.58] },
                { x: new Date(1538823600000), y: [6619, 6625.97, 6595.27, 6598.86] },
                { x: new Date(1538825400000), y: [6598.86, 6598.88, 6570, 6587.16] },
                { x: new Date(1538827200000), y: [6588.86, 6600, 6580, 6593.4] },
                { x: new Date(1538829000000), y: [6593.99, 6598.89, 6585, 6587.81] },
                { x: new Date(1538830800000), y: [6587.81, 6592.73, 6567.14, 6578] },
                { x: new Date(1538832600000), y: [6578.35, 6581.72, 6567.39, 6579] },
                { x: new Date(1538834400000), y: [6579.38, 6580.92, 6566.77, 6575.96] },
                { x: new Date(1538836200000), y: [6575.96, 6589, 6571.77, 6588.92] },
                { x: new Date(1538838000000), y: [6588.92, 6594, 6577.55, 6589.22] },
                { x: new Date(1538839800000), y: [6589.3, 6598.89, 6589.1, 6596.08] },
                { x: new Date(1538841600000), y: [6597.5, 6600, 6588.39, 6596.25] },
                { x: new Date(1538843400000), y: [6598.03, 6600, 6588.73, 6595.97] },
                { x: new Date(1538845200000), y: [6595.97, 6602.01, 6588.17, 6602] },
                { x: new Date(1538847000000), y: [6602, 6607, 6596.51, 6599.95] },
                { x: new Date(1538848800000), y: [6600.63, 6601.21, 6590.39, 6591.02] },
                { x: new Date(1538850600000), y: [6591.02, 6603.08, 6591, 6591] },
                { x: new Date(1538852400000), y: [6591, 6601.32, 6585, 6592] },
                { x: new Date(1538854200000), y: [6593.13, 6596.01, 6590, 6593.34] },
                { x: new Date(1538856000000), y: [6593.34, 6604.76, 6582.63, 6593.86] },
                { x: new Date(1538857800000), y: [6593.86, 6604.28, 6586.57, 6600.01] },
                { x: new Date(1538859600000), y: [6601.81, 6603.21, 6592.78, 6596.25] },
                { x: new Date(1538861400000), y: [6596.25, 6604.2, 6590, 6602.99] },
                { x: new Date(1538863200000), y: [6602.99, 6606, 6584.99, 6587.81] },
                { x: new Date(1538865000000), y: [6587.81, 6595, 6583.27, 6591.96] },
                { x: new Date(1538866800000), y: [6591.97, 6596.07, 6585, 6588.39] },
                { x: new Date(1538868600000), y: [6587.6, 6598.21, 6587.6, 6594.27] },
                { x: new Date(1538870400000), y: [6596.44, 6601, 6590, 6596.55] },
                { x: new Date(1538872200000), y: [6598.91, 6605, 6596.61, 6600.02] },
                { x: new Date(1538874000000), y: [6600.55, 6605, 6589.14, 6593.01] },
                { x: new Date(1538875800000), y: [6593.15, 6605, 6592, 6603.06] },
                { x: new Date(1538877600000), y: [6603.07, 6604.5, 6599.09, 6603.89] },
                { x: new Date(1538879400000), y: [6604.44, 6604.44, 6600, 6603.5] },
                { x: new Date(1538881200000), y: [6603.5, 6603.99, 6597.5, 6603.86] },
                { x: new Date(1538883000000), y: [6603.85, 6605, 6600, 6604.07] },
                { x: new Date(1538884800000), y: [6604.98, 6606, 6604.07, 6606] }
            ]
        }],
        chart: {
            type: 'candlestick',
            height: 350
        },
        title: {
            text: 'CandleStick Chart',
            align: 'left'
        },
        xaxis: {
            type: 'datetime'
        },
        yaxis: {
            tooltip: {
                enabled: true
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#candleStickChart"), candleOptions);
    chart.render();
                



    // Line Area Chart
    // --------------------------------------------------------------------
    const areaChartEl = document.querySelector('#barChart'),
        areaChartConfig = {
        chart: {
            height: 400,
            type: 'area',
            parentHeightOffset: 0,
            toolbar: {
            show: false
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: false,
            curve: 'straight'
        },
        legend: {
            show: true,
            position: 'top',
            horizontalAlign: 'start',
            labels: {
            colors: '#bfc9d4',
            useSeriesColors: false
            }
        },
        grid: {
            borderColor: '#f1f3fa',
            xaxis: {
            lines: {
                show: true
            }
            }
        },
        colors: ['#008FFB', '#FF4560', '#00E396'],
        series: [
            {
            name: 'Page Views',
            data: [150, 200, 180, 220, 210, 250, 240, 300, 290, 310, 350, 400, 450]
            },
            {
            name: 'Engagements',
            data: [80, 100, 90, 150, 120, 140, 130, 220, 200, 230, 250, 280, 320]
            },
            {
            name: 'Sign-ups',
            data: [30, 50, 40, 60, 70, 80, 90, 130, 120, 140, 160, 180, 200]
            }
        ],
        xaxis: {
            categories: [
            '1/1',
            '2/1',
            '3/1',
            '4/1',
            '5/1',
            '6/1',
            '7/1',
            '8/1',
            '9/1',
            '10/1',
            '11/1',
            '12/1',
            '13/1',
            '14/1'
            ],
            axisBorder: {
            show: false
            },
            axisTicks: {
            show: false
            },
            labels: {
            style: {
                colors: '#000',
                fontSize: '13px'
            }
            }
        },
        yaxis: {
            labels: {
            style: {
                colors: '#000',
                fontSize: '13px'
            }
            }
        },
        fill: {
            opacity: 1,
            type: 'solid'
        },
        tooltip: {
            shared: false
        }
        };
    if (typeof areaChartEl !== undefined && areaChartEl !== null) {
        const areaChart = new ApexCharts(areaChartEl, areaChartConfig);
        areaChart.render();
    }

})
    
}(jQuery));
