<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>ETF Search</title>
    <link href="style.css" rel="stylesheet" type="text/css">
    <script src="/amcharts/amcharts.js" type="text/javascript">
    </script>
    <script src="/amcharts/serial.js" type="text/javascript">
    </script>
</head>
<body>
    ETF Symbol:
    <form action="/index.php" method="get">
        <input name="name" type="text"><br>
        <input type="submit" value="Submit">
    </form>
    <p></p>
    <div id="tblDisplay"></div>
    <form action="ETFs.csv" method="get">
        <button type="submit">Download ETFs.csv</button>
    </form>
    <?php
            $server = 'localhost';
            $user = 'orbis201_index';
            $pass = 'php';
            $db = 'orbis201_etfsdb';
			$json;
            
            $link = mysqli_connect($server, $user, $pass);
            if (!$link) {
              die('Not connected : ' . mysql_error());
            }

            // create DB if it doesn't exist
            $db_selected = mysqli_select_db($link, $db);
            if (!$db_selected) {
                // Create database
                $sql = "CREATE DATABASE etfsdb";
                if (mysqli_query($link, $sql)) {
                    echo "Database created successfully";
                }
                else {
                    echo "Error creating database";
                }
            }
            mysqli_close($link);
            
            $conn = mysqli_connect($server, $user, $pass, $db);
            
            // Select 1 from table_name will return false if the table does not exist.
            $val = mysqli_query($conn, 'SELECT 1 FROM ETF');

            if($val === FALSE) { //create table if doesn't exist
                $sql = "CREATE TABLE ETFs (
                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
                etfsymbol VARCHAR(5) NOT NULL,
                holdings VARCHAR(1000) NOT NULL,
                weights VARCHAR(1000)
                )";
                mysqli_query($conn, $sql);   //create the table
            }
            if(!empty($_GET)) {
				echo "<html><body><table border=\"1\" style=\"width:100%\">\n\n";
				echo "<tr>\n<th>ETF Symbol</th>\n<th>Top 10 Holdings</th>\n<th>Weights</th>\n</tr>\n";
				$success = searchDB($conn);
				if ($success) {
					$json = toJSON(HOLDINGS, WEIGHTS); //creates the JSON object
				}
				//var_dump($json);
			}
            
            //converts holdings and weight into JSON for AmCharts
            function toJSON($x, $y) {
                if (empty($x) || empty($y)) {
                    return 0;
                }
                $hash = [];
                $weights = [];
                $holdings = preg_split("/[1-9]\)|10\)/", HOLDINGS); //split using regex
                $weights_str = preg_split("/[1-9]\)|10\)/", WEIGHTS); //split using regex
                
                $holdings = array_filter(array_map('trim', $holdings)); //trim whitespace from holdings
                
                foreach ($weights_str as $weight_str) {
                    $replace = str_replace("%", "", $weight_str); //remove %s from weights
                    array_push($weights, floatval($replace));
                }
                
				for ($c = 1; $c < 11; $c ++) {
					$hash[$c - 1] = array	(
								"holding" => $holdings[$c],
								"weight" => $weights[$c]
							);
				//}
                /*foreach ($holdings as $holding) {
                    $hash[$holding] = $weights[$i];
                    $i++;
                }*/
				
				}
				echo "<br>";
                return json_encode($hash);
			}
            
            function searchweb($conn) {
                $f = fopen("ETFs.csv", "r");
                while (($line = fgetcsv($f)) !== false) {
                    if (strtoupper($_GET["name"]) == $line[0]) { //match was found
                        echo "<tr>";
                        $i = 0;
                        $sym;
                        $hold;
                        $w;
                        foreach ($line as $cell) {
                            //echo "$cell<br>";
                            echo "<td>" . htmlspecialchars($cell) . "</td>";
                            if ($i === 0) {
                                $sym = $cell;
                            }
                            if ($i === 1) {
                                $hold = $cell;
                                define("HOLDINGS", $cell);
                            }
                            if ($i === 2) {
                                $w = $cell;
                                define("WEIGHTS", $cell);
                            }
                            $i++;
                        }
                        echo "</tr>\n";
                        
                        //insert into ETF table
                        $sql = "INSERT INTO ETFs (etfsymbol, holdings, weights)
                                    VALUES (\"{$sym}\", \"{$hold}\", \"{$w}\")";
                        mysqli_query($conn, $sql);
                        echo "inserted into the database";                  
                        fclose($f);
                        return true; //the line was printed, exit loop
                    }
                }
                fclose($f);
                return false; //line not found
            }
            
            function searchDB($conn) {
                $symbol  = $_GET["name"];
                $sql = "SELECT * FROM ETFs WHERE etfsymbol = '{$symbol}'";
                $result = mysqli_query($conn, $sql);

                if (mysqli_num_rows($result) > 0) {
                    echo "match found in database";
                    echo "<tr>";
                    while($row = mysqli_fetch_assoc($result)) {
                        define("HOLDINGS", $row["holdings"]);
                        define("WEIGHTS", $row["weights"]);
                        echo "<td>". $row["etfsymbol"]."</td>".
                                 "<td>" . $row["holdings"]. "</td>".
                                 "<td>". $row["weights"]. "<tr>";
                    }
                    echo "</tr>\n";
                    return true;
                } else {
                    echo "not found in database, searching CSV";
                    return searchweb($conn);                
                }
            }
            
            echo "\n</table></body></html>";
        ?>
    <script>
            var chart;

			
			
            var chartData = <?php echo $json?>;

            AmCharts.ready(function () {
                // SERIAL CHART
                chart = new AmCharts.AmSerialChart();
                chart.dataProvider = chartData;
                chart.categoryField = "holding";
                // this single line makes the chart a bar chart,
                // try to set it to false - your bars will turn to columns
                chart.rotate = true;
                // the following two lines makes chart 3D

                // AXES
                // Category
                var categoryAxis = chart.categoryAxis;
                categoryAxis.gridPosition = "start";
                categoryAxis.axisColor = "#DADADA";
                categoryAxis.fillAlpha = 1;
                categoryAxis.gridAlpha = 0;
                categoryAxis.fillColor = "#FAFAFA";

                // value
                var valueAxis = new AmCharts.ValueAxis();
                valueAxis.axisColor = "#DADADA";
                valueAxis.title = "Weight in %";
                valueAxis.gridAlpha = 0.1;
                chart.addValueAxis(valueAxis);

                // GRAPH
                var graph = new AmCharts.AmGraph();
                graph.title = "Income";
                graph.valueField = "weight";
                graph.type = "column";
                graph.balloonText = "Weight in % of [[category]]:[[value]]";
                graph.lineAlpha = 0;
                graph.fillColors = "#bf1c25";
                graph.fillAlphas = 1;
                chart.addGraph(graph);

                chart.creditsPosition = "top-right";

                // WRITE
                chart.write("chartdiv");
            });
    </script>
    <div id="chartdiv" style="width: 500px; height: 600px;"></div>
</body>
</html>