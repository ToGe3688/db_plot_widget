// ----------------------------------------------------------
// 
// SmartVISU widget for database plots with highcharts
// (c) Tobias Geier 2015
// 
// Version: 0.2
// License: GNU General Public License v2.0
// 
// Manual: 
// 
// ----------------------------------------------------------
$(document).delegate('div[data-widget="dbPlot.linePlot"]', {
    // Get the data via POST Request from the php DB Handler
    'init': function (event) {

        // Set unit for y Axis
        var unit = $(this).attr('data-unit-y-axis');
        var containerId = $(this).attr('id');

        // Check if y Axis Options are set. If not use default setting with values from widget data fields
        var yAxisOptions = JSON.parse($(this).attr('data-y-axis-options'));
        if (yAxisOptions !== '') {
            // Set custom yAxisData
            var yAxisData = yAxisOptions;
        } else {
            // Set default yAxisData
            var yAxisData = {
                title: {
                    text: $(this).attr('data-text-y-axis')
                },
                labels: {
                    format: '{value} ' + $(this).attr('data-unit-y-axis')
                }
            };
        }

        // Check if y Axis Options are set. If not use default setting with values from widget data fields
        var legendOptions = JSON.parse($(this).attr('data-legend-options'));
        if (legendOptions !== '') {
            // Set custom legendOptions
            var legendData = legendOptions;
        } else {
            // Set default legendOptions
            var legendData = {
                align: 'right',
                verticalAlign: 'top',
                borderWidth: 0
            };
        }

        var options = {
            // Options for chart
            chart: {
                renderTo: $(this).attr('id'),
                type: $(this).attr('data-type'),
                zoomType: 'x'
            },
            // Options for plot title
            title: {
                text: $(this).attr('data-title'),
                align: 'left'
            },
            // Options for xAxis
            xAxis: {
                type: 'datetime'
            },
            // Options for yAxis
            yAxis: yAxisData,
            // Options for legend
            legend: legendData,
            series: [],
            // Tooltip for SmartVISU Style
            tooltip: {
                formatter: function () {
                    if (this.series.options.unit !== null) {
                        // If unit is set in the series data use this
                        return this.series.name + ' <b>' + this.y + ' ' + this.series.options.unit + '</b>';
                    } else {
                        // If unit is not set use the unit value from data fields
                        return this.series.name + ' <b>' + this.y + ' ' + unit + '</b>';
                    }
                }
            }
        };

        // Get Query for POST-Request
        var postData = {
            query: $(this).attr('data-query'),
            maxRows: $(this).attr('data-max-rows'),
            timeRangeStart: Math.round((Date.now()/1000)-($(this).attr('data-range')*60)),
            timeRangeEnd: Math.round(Date.now()/1000)
        };

        // Make POST-Request to get the data series for the plot
        $.post("widgets/widget_dbplot.php", postData).done(function (data) {
            if (!data.error) {
                options.series = data;
                new Highcharts.Chart(options);
            } else {
                console.log(data.error);
                $('#' + containerId ).hide();
                $('#' + containerId + '-error-container').show().html('<h3>dbPlot Error (Initial)</h3>' + data.error);
            }
        });
        $(this).attr('data-last-update', Date.now());   
    },
    // Update event is called when a GAD changes
    'update': function (event, response) {

        // The chart which will be updated
        var chart = $('#' + this.id).highcharts();
        var containerId = $(this).attr('id');

        // Get Query for POST-Request
        var postData = {
            query: $(this).attr('data-query'),
            timeRangeStart: Math.round($(this).attr('data-last-update')/1000),
            timeRangeEnd: Math.round(Date.now()/1000)
        };

        // Make POST-Request and update the Plot
        $.post("widgets/widget_dbplot.php", postData).done(function (data) {
            if (!data.error) {
                for (i = 0; i < data.length; i++) {
                    if (data[i].data.length !== 0) {
                        for (a = 0; a < data[i].data.length; a++) {
                            chart.series[i].addPoint(data[i].data[a], true);
                        }
                        $('#' + containerId ).attr('data-last-update', Date.now()); 
                    }
                }
                
                // Redraw chart
                chart.redraw();
            } else {
                console.log(data.error);
                $('#' + containerId ).hide();
                $('#' + containerId + '-error-container').show().html('<h3>dbPlot Error (Update)</h3>' + data.error);
            }
        });
        
        
        // Fix for display problems with too wide Chart container
        $(window).resize();
    }

});
