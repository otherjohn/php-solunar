<?php
include_once 'solunar.php';
$today = getdate();  //defualt to todays date
?>


<h2>The Open Source Solunar Tables Calculator:</h2>
<hr>


<?php
/*********************************************************************/	
/* Here is an example of how to get the users inputs from a form
 * that is pre-loaded with todays date
 */

if ($_POST['pyear']  == FALSE) {
//	no post yet so use default values
	$year = $today[year];
	$month = $today[mon];
	$day = $today[mday];
	$tz = -5;
	$lat = 40.5;
	$underlong = -80.5;
	}
	else {
//	use post values
	$year = (int)$_POST['pyear'];
	$month = (int)$_POST['pmonth'];
	$day = (int)$_POST['pday'];
	$tz = (int)$_POST['tz'];
	$lat = (float)$_POST['lat'];
	$underlong = (float)$_POST['long'];
	}
	$UT = 0.0;
?>
<h3>Enter date and position</h3>
<form action="index.php" method = "post">
<p>Year (yyyy) : <input type="int" name="pyear" value = <?php echo $year;?> /></p>
<p>Month (mm) : <input type="text" name="pmonth" value = <?php echo $month;?> /></p>
<p>Day (dd) : <input type="text" name="pday" value = <?php echo $day;?> /></p>
<p>Longitude ( - is west ) : <input type="text" name="long" value = <?php echo $underlong;?> /></p>
<p>Latitude ( - is south ) : <input type="text" name="lat" value = <?php echo $lat;?> /></p>
<p>Time zone offset : <input type="text" name="tz" value = "-5" /></p>
<p><input type="submit" /></p>
</form><hr>


<?php
/*********************************************************************/	
/* Here is an example results header
 */
echo"<h3>Results</h3>";
echo "Solunar Data for $year / $month / $day, position: ";
if ($lat < 0){
$lat1 = 0 - $lat;
echo round($lat1,2);
echo "S/";
}else{
echo round($lat,2);
echo "N/";
}
if ($underlong < 0){
$long1 = 0 - $underlong;
echo round($long1,2);
echo "W";
}else{
echo round($long,2);
echo "E";
}
?>

	
<?php
/*********************************************************************/
/* HERE IS WHAT YOU WANT!!!!
 * 
 * The folowing function calls are all you need to get the RAW data
 * These function calls require the following variables to already be
 * set: $year, $month, $day, $tz, $lat, $underlong, $UT
 * 
 * $year -> year part of date we will calculate for in yyyy format. example 2008
 * &month -> month part of date we will calculate for in mm format. example 2 or 02
 * $day -> day part of date we will calculate for in dd format. example 2 or 02
 * $tz -> timezone offset to calculate results in. example -5 for EST
 * $lat -> latitude (NEGATIVE NUMBERS ARE WEST)
 * $underlong -> longitude  (NEGATIVE NUMBERS ARE SOUTH)
 * $UT = 0.0, Universal time, keep this set at zero, its for the julian
 * date calculations, for our purposes we only need the julian date at
 * the start of the day, however  I might change that in later versions.
 * 
/*********************************************************************/
//get dates	
	$JD = get_Julian_Date ($year, $month, $day, $UT);
	$date = ($JD - 2400000.5 - ($tz/24.0));
/*********************************************************************/	
//get rise, set and transit times for moon and sun
	get_rst  (1, $date, 0.0 - $underlong , $lat, $sunrise, $sunset, $suntransit);
	get_rst  (0, $date, 0.0 - $underlong, $lat, $moonrise, $moonset, $moontransit);
	$moonunder = get_underfoot($date, $underlong);
/*********************************************************************/
//get solunar minor periods
	sol_get_minor1($minorstart1, $minorstop1, $moonrise);
	sol_get_minor2($minorstart2, $minorstop2, $moonset);
/*********************************************************************/
//get solunar major periods
	sol_get_major1 ($majorstart1, $majorstop1, $moontransit);
	sol_get_major2 ($majorstart2, $majorstop2, $moonunder);
/*********************************************************************/
//get moon phase 
	$moonage = get_moon_phase ($JD, $PhaseName, $illumin);
/*********************************************************************/
//get day scale
	$phasedayscale = phase_day_scale ($moonage);
	$soldayscale = sol_get_dayscale ($moonrise, $moonset, $moontransit, $sunrise, $sunset);
/*********************************************************************/
/* at this point we have raw data times in decimal
 * time format. 
*/
/*********************************************************************/
/*
 * Here is an example on how to convert the results to 
 * a human readable format and display using 
 * functions:  convert_time_to_string() and 
 * display_event_time()
 * 
 * scroll down further to the end of this source to see all our raw data variables
*/
	echo "<h4>Moon</h4>";
	//set the event title:
	$event = sprintf("rise =");
	//call function to display event and time
	display_event_time($moonrise, $event);
	$event = sprintf("transit =");
	display_event_time($moontransit, $event);
	$event = sprintf("set =");
	display_event_time($moonset, $event);
	echo "<br>Phase is $PhaseName, ";
	$illumin = $illumin*100;
	echo round($illumin, 1);
	echo "% illuminated, ";
	echo round($moonage, 1);
	echo " days since new.";
	echo "<h4>Sun</h4>";
	$event = sprintf("rise = ");
	display_event_time($sunrise, $event);
	$event = sprintf("transit =");
	display_event_time($suntransit, $event);
	$event = sprintf("set =");
	display_event_time($sunset, $event);
	
	echo "<h4> Minor Periods</h4>";
	//display earlier minor time first, minor 1 is based on moonset, minor2 on moonrise
	if (moonrise > moonset){
		
		$event = sprintf("");
		display_event_time($minorstart1, $event);
		$event = sprintf(" -");
		display_event_time($minorstop1, $event);
		echo "<br>";
		$event = sprintf("");
		display_event_time($minorstart2, $event);
		$event = sprintf(" -");
		display_event_time($minorstop2, $event);
		}
	else {
		
		$event = sprintf("");
		display_event_time($minorstart2, $event);
		$event = sprintf(" -");
		display_event_time($minorstop2, $event);
		echo "<br>";
		$event = sprintf("");
		display_event_time($minorstart1, $event);
		$event = sprintf(" -");
		display_event_time($minorstop1, $event);
		}
		
	echo "<h4> Major Periods</h4>";
	//display earlier major time first
	if (moontransit < 9.5){
		$event = sprintf("");
		display_event_time($majorstart1, $event);
		$event = sprintf(" -");
		display_event_time($majorstop1, $event);
		echo "<br>";
		$event = sprintf("");
		display_event_time($majorstart2, $event);
		$event = sprintf(" -");
		display_event_time($majorstop2, $event);
		}
	else {
		$event = sprintf("");
		display_event_time($majorstart2, $event);
		$event = sprintf(" -");
		display_event_time($majorstop2, $event);
		echo "<br>";
		$event = sprintf("");
		display_event_time($majorstart1, $event);
		$event = sprintf(" -");
		display_event_time($majorstop1, $event);
		}
		
		echo "<h4>Daily Action Rating</h4>";
		$dayscale = 0;
		$dayscale = ($soldayscale + $phasedayscale);
		echo "Todays action is rated a $dayscale (scale is 0 thru 5, 5 is the best)";



/*********************************************************************/
/*
 * RAW DATA DUMP
 */
	echo"<hr>";
	echo"<h3>Raw data</h3>";
	echo "julian date = $JD";
	echo "<br>moonrise = $moonrise";
	echo "<br>moontransit = $moontransit";
	echo "<br>moonunder = $moonunder";
	echo "<br>moonset = $moonset";
	echo "<br>sunrise = $sunrise";
	echo "<br>suntransit = $suntransit";
	echo "<br>sunset = $sunset";
	echo "<br>minor 1 start = $minorstart1";
	echo "<br>minor 1 stop = $minorstop1";
	echo "<br>minor 2 start = $minorstart2";
	echo "<br>minor 2 stop = $minorstop2";
	echo "<br>major 1 start = $majorstart1";
	echo "<br>major 1 stop = $majorstop1";
	echo "<br>major 2 start = $majorstart2";
	echo "<br>major 2 stop = $majorstop2";
	echo "<br>soldayscale = $soldayscale";
	echo "<br>phasedayscale = $phasedayscale";
	//daily action is the sum of $soldayscale and $phasedayscale
	echo "<br>daily action is a sum = $soldayscale + $phasedayscale";
	echo "<br>moonage in days = $moonage";
	echo "<br>moon illumination = $illumin";
	echo "<br>moonphase name = $PhaseName";

//thats it were done




