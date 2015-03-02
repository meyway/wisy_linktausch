<!DOCTYPE html>
<meta charset="utf-8">
<html>
    <head>
        <title>Auswertung</title>
        <link rel="stylesheet" href="eventdrops/css/style.css" />
        <script src="eventdrops/d3.min.js"></script>
        <script src="eventdrops/eventDrops.js"></script>
        <style type="text/css">
        body, html {
            font-family: verdana, sans-serif;
            width: 100%;
        }
        
        #timeline {
            margin-left: auto;
            margin-right: auto;
            display: block;
            max-width: 1300px;
        }
        
        text {
                font-size: 11px;
                font-weight: bold;
        }
        
        #zugriff {
            color:#333;
            margin-left: -100px;
        }

        </style>
    </head>
    <body>

        <div style="text-align: center;" id="legende"><small>Datum &Auml;ndern:<br/>Mit der Maus &uuml;ber die Tabelle positionieren, dann scrollen.<br/>Oder Tabelle nach links/rechts schieben.<br/><br/>
            Hinweis:<br/>Die Anzahl der Zugriffe (in den Klammern) links, bezieht sich ausschlie&szlig;lich auf den gezeigten Ausschnitt.
            </small></div>
        <br/><br/>
        <div id="timeline"></div>        
        
        <script type="text/javascript">
            
            var chartPlaceholder = document.getElementById('timeline');
            
            var data = [];
            
            var sevenDays = 14 * 24 * 60 * 60 * 1000;
            var endTime = Date.now()+sevenDays;
            var twoMonth = 120 * 24 * 60 * 60 * 1000;
            var startTime = endTime - twoMonth;

            var color = d3.scale.category20();
            
            
            var data = [<?php echo($graph_data); ?>];
            
            //var data = [{ name: "abc.de", dates: [Date.now()]}];
            
             
            var locale = d3.locale({
                "decimal": ",",
                "thousands": ".",
                "grouping": [3],
                "currency": ["EUR", ""],
                "dateTime": "%A %e %B %Y, %X",
                "date": "%d/%m/%Y",
                "time": "%H:%M:%S",
                "periods": ["AM", "PM"],
                "days": ["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag"],
                "shortDays": ["So.", "Mo.", "Di.", "Mi.", "Do.", "Fr.", "Sa."],
                "months": ["Januar", "Februar", "Maerz", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember"],
                "shortMonths": ["Jan.", "Feb.", "Maer.", "Apr.", "Mai.", "Jun.", "Jul.", "Aug.", "Sep.", "Okt.", "Nov.", "Dez."]
             });
              
            
            var graph = d3.chart.eventDrops()
                .start(new Date(startTime))
                .end(new Date(endTime))
                .locale(locale)
                .eventHover(function(el) {
                    var series = el.parentNode.firstChild.innerHTML;
                    var timestamp = d3.select(el).data()[0];
                    var myDate = (new Date(timestamp));
                    document.getElementById('legende').innerHTML = "<div id='zugriff'>Zugriff: "+myDate.getDate()+"."+myDate.getMonth()+"."+myDate.getFullYear()+"</div>";
                })
                .eventColor(function (d, i) {
                    return color(i);
                })
                .width(1000)
                .margin({ top: 100, left: 200, bottom: 0, right: 0 })
                .axisFormat(function(xAxis) {
                    xAxis.ticks(8);
                });

                
            var element = d3.select(chartPlaceholder).append('div').datum(data);

            graph(element);
            
            

        </script>
        
    </body>
</html>