# SmartVISU Database Plot Widget
A SmartVISU widget for database plots with highcharts.

(C) Tobias Geier 2015
Stand: v0.3

![alt text](http://i.imgur.com/RFT4CrR.png "SmartVISU Database Lineplots")

This widget makes it possible to add fancy zoomable live updated line plots from the FHEM DbLog module (or any other mysql or sqlite log table) to the SmartVISU Frontend. 

## Install
NOTE: The widget is based on a php script which will fetch the required data for the plot from a SQLite or MySQL database. The connection to the database is made via PDO. So before you start make sure to install the required php db extensions if they are not installed already.

1. 	Copy widget_dbplot.php file to SmartVISU widget folder (/widgets)
2. 	Copy and widget_dbplot.html file to your SmartVISU page ( /pages/MyPage )
3. 	Edit the widget_dbplot.php file and set your database connection settings.
4. 	Copy visu.js to the folder of your SmartVISU page (/pages/myPage) or if you already have a visu.js file just append the content to the file.
5. 	Edit the .html-file in your SmartVISU page folder where the widget will be displayed
	- put {% import "widget_dbplot.html" as dbPlot %} right before your widgetCode

## Creating Plots
### Accepted parameters:
```
* @param id unique id for this widget
* @param title a title for the plot
* @param timeRange timerange that will be displayed in the chart | default: 60
* @param maxRows max row count that will be selected from the database | default: 300
* @param textYAxis text that is displayed for the y axis, won't be used if yAxisOptions are defined
* @param unitYAxis unit for the y axis, won't be used if yAxisOptions are defined
* @param plotOptions multidimensional array with options for plot series | See manual for detailed description and examples
* @param yAxisOptions multidimensional array with options for plot y axis | See manual for detailed description and examples
* @param legendOptions multidimensional array with options for plot legend | See manual for detailed description and examples
* @param height pixel height of the plot | default: 300

{% macro linePlot(id, title, timeRange, maxRows, textYAxis, unitYAxis, plotOptions, yAxisOptions, legendOptions, height) %}
```

### Adding a plot widget
Adding plots with this widget is a bit more complex as adding other SmartVISU widgets but it gives you a really flexible solution to fit your needs. The widget accepts a series of parameters from which 3 are config arrays that need to be set. As the other parameters are pretty self-explanatory we'll jump straight to the config arrays.

#### plotOptions
The plot options array needs to be defined every time you want to create a plot. It holds the basic information on what data the widget gathers from the database. You can also define multiple line plots, see the examples below.
```
{% set plotOptions = 
 [
	{
		'update_trigger_gad': '',
		'device': '',
		'reading': '',
		'config': {
			'name': '',
			'type': ''
		}
	}
]%}
```
| Tables        | Are           |  
| ------------- |:-------------|
| update_trigger_gad     | Defines a gad that is used to trigger the update of the plot and should be connected to the device reading via fronthem |
| device    | The name of the device from which we want to get log entries  |
| reading | The reading of the device  |
| config | An array with highcharts series options for the series of data. Name and Type should allways be provided. For more available options see the [Highcharts API Reference](http://api.highcharts.com/highcharts#series) **Note: There are many of plot types available in highchart, feel free to experiment with the options** |

#### yAxisOptions (optional)
Sometimes you want to show combined plots of two readings with different units or just want to edit the look of the yAxis. Thats when you need to define the yAxis options array. When you use the yAxis options array the set parameters for the widget are ignored. See the [Highcharts API Reference](http://api.highcharts.com/highcharts#yAxis) for more available yAxis options.

#### legendOptions (optional)
If you don't like the style how the legend is rendered just change it to your needs! See the [Highcharts API Reference](http://api.highcharts.com/highcharts#legend) for more available yAxis options.


## Examples
### Example 1: Simple lineplot from one reading value
![alt text](http://i.imgur.com/vVCpmS5.png "Simple lineplot from one reading value")
```
{% set plotOptions = 
 [
	{
		'update_trigger_gad': 'HeatingTemperatureTrigger',
		'device': 'MyHeatingDevice',
		'reading': 'DeviceReading',
		'config': {
			'name': 'Temperature',
			'type': 'spline'
		}
	}
]%}
{{ dbPlot.linePlot('HeatingPlot', 'Temperatures', '', '', 'Temperatures', '°C', plotOptions, '', '', 300) }}
```
This will render a lineplot from the reading `DeviceReading` of `MyHeatingDevice`. The defined `update_trigger_gad` is a GAD used to trigger the update of the plot when the reading changes. The config array holds information on the name which will be used in the legend and the type of the plot. (See below for further available options)

### Example 2: Lineplot with areaplot from two reading values
![alt text](http://i.imgur.com/twYEUZk.png "Lineplot with areaplot from two reading values")
```
{% set plotOptions = 
 [
 {
		'update_trigger_gad': 'HeatingDesiredTemperatureTrigger',
		'device': 'MyHeatingDevice',
		'reading': 'DesiredTemperature',
		'config': {
			'name': 'Desired Temp.',
			'type': 'area'
	},
	{
		'update_trigger_gad': 'HeatingTemperatureTrigger',
		'device': 'MyHeatingDevice',
		'reading': 'Temperature',
		'config': {
			'name': 'Temperature',
			'type': 'line'
		}
	}
]%}
{{ dbPlot.linePlot('HeatingPlot', 'Temperatures', '', '', 'Temperaturen', '°C', plotOptions, '', '', 300) }}
```
This will render a chart with an area plot for the reading `DesiredTemperature` and a line plot for `Temperature`.

### Example 3: Lineplot from two readings with multiple y-Axis
![alt text](http://i.imgur.com/0lzjG1F.png "Lineplot with areaplot from two reading values")

```
{% set plotOptions = [
	{
		'update_trigger_gad': 'speedtest_ping.trigger',
		'device': 'SpeedTest',
		'reading': 'ping',
		'config': {
			'name': 'Ping',
			'type': 'spline',
			'unit': 'ms'
		}
	},
	{
		'update_trigger_gad': 'speedtest_download.trigger',
		'device': 'SpeedTest',
		'reading': 'download',
		'config': {
			'name': 'Bandwidth',
			'type': 'spline',
			'yAxis': 1,
			'unit': 'MBit'
		}
	}
]%}
{% set yAxisOptions = [
	{ 
		title: {
			text: 'Response Time',
		},
		labels: {
			format: '{value} ms',
		},
		opposite: true
    },
	{ 
		title: {
			text: 'Available Bandwidth',
		},
		labels: {
			format: '{value} MBit',
		}
    }
]%}
{{ dbPlot.linePlot('pingBandwithPlot', 'Antwortzeit / Bandbreite', '', '', '', '', plotOptions, yAxisOptions, '', 300) }} 
```
This will render a chart with two lineplots and two corresponding y-Axis.

### Example 4: Line plot with custom legend
```
{% set plotOptions = 
 [
	{
		'update_trigger_gad': 'HeatingTemperatureTrigger',
		'device': 'MyHeatingDevice',
		'reading': 'DeviceReading',
		'config': {
			'name': 'Temperature',
			'type': 'line'
		}
	}
]%}
{% set legendOptions = {
			align: 'right',
            verticalAlign: 'bottom',
			layout: 'vertical',
			backgroundColor: '#333333',
			x: -90,
			y: -30,
			title: {
				text: 'Legende',
				style: {
					color:'#fff'
				}
			},
            borderWidth: 1
    }
%}
{{ dbPlot.linePlot('HeatingPlot', 'Temperatures', '', '', 'Temperaturen', '°C', plotOptions, '', legendOptions, 300) }}
```
This will render a line plot with a custom legend.


