<?php
/*
 *      solunar.php
 *
 *
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

/*functions*/
/*********************************************************************/
function get_Julian_Date ($year, $month, $day, $UT)
/* calculates Julian Day */
{
	$a = floor(($month + 9)/12);
	$b = floor((7 * ($year + $a))/4);
	$c = floor((275 * $month)/9);
	$d = 367 * $year - $b + $c + $day + 1721013.5 + UT/24;
	$e = (100 * $year + $month - 190002.5);
	$f = $e/abs($e);
	$locJD = ($d - 0.5 * $f + 0.5);
		
	return $locJD;
}
/*********************************************************************/
function ipart ($x)
//returns the true integer part, even for negative numbers
{
	if ($x!=0) {
$a = $x/abs($x) * floor(abs($x));
}
else {
	$a = 0;
}
return $a;
}
/*********************************************************************/
function fpart ($x)
//returns fractional part of a number
{
$x = $x - floor($x);
if ( $x < 0) {
	$x = $x + 1;
}
return $x;
}

/*********************************************************************/
function sinalt ($object, $mjd0, $hour, $ourlong, $cphi, $sphi )
/*
returns sine of the altitude of either the sun or the moon given the modified
julian day number at midnight UT and the hour of the UT day, the longitude of
the observer, and the sine and cosine of the latitude of the observer
*/
{
$ra = 0.0;
$dec = 0.0;
$instant = $mjd0 + $hour / 24.0;
$t = ($instant - 51544.5) / 36525;
    
    if ($object == 0) {
        moon ($t, $ra, $dec);
    }
    else {
        sun ($t, $ra, $dec);
    }
  
    $tau = 15.0 * (lmst($instant, $ourlong) - $ra);    //hour angle of object
    $value = $sphi * sin(deg2rad($dec)) + $cphi * cos(deg2rad($dec)) * cos(deg2rad($tau));
    
    return ($value);
}
/*********************************************************************/
function lmst ($mjd, $ourlong)
//returns the local siderial time for the mjd and longitude specified
{
$mjd0 = ipart($mjd);
$ut = ($mjd - $mjd0) * 24;
$t = ($mjd0 - 51544.5) / 36525;
$gmst = 6.697374558 + 1.0027379093 * $ut;
$gmst = $gmst + (8640184.812866 + (.093104 - .0000062 * $t) * $t) * $t / 3600;
$value = 24.0 * fpart(($gmst - $ourlong / 15.0) / 24.0);
return ($value);
}

/*********************************************************************/
function quad ($ym, $y0, $yp, &$xe, &$ye, &$z1, &$z2, &$nz)
/*
finds a parabola through three points and returns values of coordinates of
extreme value (xe, ye) and zeros if any (z1, z2) assumes that the x values are
-1, 0, +1
*/
{
$NZ = 0;
$XE = 0;
$YE = 0;
$Z1 = 0;
$Z2 = 0;
$a = .5 * ($ym + $yp) - $y0;
$b = .5 * ($yp - $ym);
$c = $y0;
$XE = (0.0 - $b) / ($a * 2.0); //              'x coord of symmetry line
$YE = ($a * $XE + $b) * $XE + $c; //      'extreme value for y in interval
$dis = $b * $b - 4.0 * $a * $c;   //    'discriminant

    if ( $dis > 0.000000 ) {                 //'there are zeros
        $dx = (0.5 * sqrt($dis)) / (abs($a));
        $Z1 = $XE - $dx;
        $Z2 = $XE + $dx;
            if (abs($Z1) <= 1) {
                $NZ = $NZ + 1 ;   // 'This zero is in interval
            }
            if (abs($Z2) <= 1) {
                $NZ = $NZ + 1  ;   //'This zero is in interval
            }
            if ($Z1 < -1) {
                $Z1 = $Z2;
            }
    }

$xe = $XE;
$ye = $YE;
$z1 = $Z1;
$z2 = $Z2;
$nz = $NZ;
return;
}

/*********************************************************************/
function sun ($t, &$ra, &$dec )
/*
Returns RA and DEC of Sun to roughly 1 arcmin for few hundred years either side
of J2000.0
*/
{
define("twoPI",		"6.283185306");
define("COSEPS",	"0.91748");
define("SINEPS",		"0.39778");
$m = twoPI * fpart(0.993133 + 99.997361 * $t);        //Mean anomaly
$dL = 6893 * sin($m) + 72 * sin(2 * $m);          //Eq centre
$L = twoPI * fpart(0.7859453 + $m / twoPI + (6191.2 * $t + $dL) / 1296000);
$sl = sin($L);
$x = cos($L);
$y = COSEPS * $sl;
$z = SINEPS * $sl;
$rho = sqrt(1 - $z * $z);
$DEC = (360 / twoPI) * atan2($z , $rho);
$RA = (48 / twoPI) * atan2($y , ($x + $rho));
if ($RA < 0) {
    $RA = $RA + 24;
}
$ra = $RA;
$dec = $DEC;
return;
}

/*********************************************************************/
function moon ($t, &$ra, &$dec)
/*
returns ra and dec of Moon to 5 arc min (ra) and 1 arc min (dec) for a few
centuries either side of J2000.0 Predicts rise and set times to within minutes
for about 500 years in past - TDT and UT time diference may become significant
for long times
*/
{
define ("twoPI",     "6.283185306");
define ("ARC",			"206264.8062");
define ("COSEPS",	"0.91748");
define ("SINEPS",		"0.39778");

$L0 = fpart(.606433 + 1336.855225 * $t);    //'mean long Moon in revs
$L = twoPI * fpart(.374897 + 1325.55241 * $t); //'mean anomaly of Moon
$LS = twoPI * fpart(.993133 + 99.997361 * $t); //'mean anomaly of Sun
$d = twoPI * fpart(.827361 + 1236.853086 * $t); //'diff longitude sun and moon
$F = twoPI * fpart(.259086 + 1342.227825 * $t); //'mean arg latitude
//' longitude correction terms
$dL = 22640 * sin($L) - 4586 * sin($L - 2 * $d);
$dL = $dL + 2370 * sin(2 * $d) + 769 * sin(2 * $L);
$dL = $dL - 668 * sin($LS) - 412 * sin(2 * $F);
$dL = $dL - 212 * sin(2 * $L - 2 * $d) - 206 * sin($L + $LS - 2 * $d);
$dL = $dL + 192 * sin($L + 2 * $d) - 165 * sin($LS - 2 * $d);
$dL = $dL - 125 * sin($d) - 110 * sin($L + $LS);
$dL = $dL + 148 * sin($L - $LS) - 55 * sin(2 * $F - 2 * $d);
//' latitude arguments
$S = $F + ($dL + 412 * sin(2 * $F) + 541 * sin($LS)) / ARC;
$h = $F - 2 * $d;
//' latitude correction terms
$N = -526 * sin($h) + 44 * sin($L + $h) - 31 * sin($h - $L) - 23 * sin($LS + $h);
$N = $N + 11 * sin($h - $LS) - 25 * sin($F - 2 * $L) + 21 * sin($F - $L);
$lmoon = twoPI * fpart($L0 + $dL / 1296000); //  'Lat in rads
$bmoon = (18520 * sin($S) + $N) / ARC;  //     'long in rads
//' convert to equatorial coords using a fixed ecliptic
$CB = cos($bmoon);
$x = $CB * cos($lmoon);
$V = $CB * sin($lmoon);
$W = sin($bmoon);
$y = COSEPS * $V - SINEPS * $W;
$Z = SINEPS * $V + COSEPS * $W;
$rho = sqrt(1.0 - $Z * $Z);
$DEC = (360.0 / twoPI) * atan2($Z , $rho);
$RA = (48.0 / twoPI) * atan2($y , ($x + $rho));
if ($RA < 0) {
        $RA = $RA + 24.0;
}
$ra = $RA;
$dec = $DEC;
return;
}

/*********************************************************************/
function get_rst ($object, $date, $ourlong, $ourlat, &$obRise, &$obSet, &$obTransit)
//get rise, set and transit times of object sun or moon
{
$sl = sin(deg2rad($ourlat));	//sin of lat
$cl = cos(deg2rad($ourlat));	//cos of lat

$sinho[0] = 0.002327;         //moonrise sin of horizon - average diameter used
$sinho[1] = -0.014544;       //sunrise sin of horizon - classic value for refraction
$ym = sinalt($object, $date, $hour - 1, $ourlong, $cl, $sl) - $sinho[$object];
    if ($ym > 0) {
		$above = 1;
    }
    else {
		$above = 0;
    }




//start rise-set loop
	do
	{
		$y0 = sinalt($object, $date, $hour, $ourlong, $cl, $sl) - $sinho[$object];
        $yp = sinalt($object, $date, $hour + 1, $ourlong, $cl, $sl) - $sinho[$object];
        
		quad ($ym, $y0, $yp, $xe, $ye, $z1, $z2, $nz);
        
        switch ($nz)
        {
        case 0:	//'nothing  - go to next time slot
			break;
        case 1:                      //' simple rise / set event
            if ($ym < 0) {       //' must be a rising event
				$utrise = $hour + $z1;
                $rise = 1;
            }
            else {	//' must be setting
				$utset = $hour + $z1;
                $sett = 1;
            }
            break;
		case 2:                      //' rises and sets within interval
			if ($ye < 0) {       //' minimum - so set then rise
				$utrise = $hour + $z2;
                $utset = $hour + $z1;
            }
            else {    //' maximum - so rise then set
				$utrise = $hour + $z1;
                $utset = $hour + $z2;
            }
            $rise = 1;
            $sett = 1;
            $zero2 = 1;
            break;
		}

        $ym = $yp;     //'reuse the ordinate in the next interval
        $hour = $hour + 2;
        $check = ($rise * $sett);
      //  echo "<br>hour = $hour";
	}
	while (($hour != 25) && ($check != 1));
	// end rise-set loop
	
//GET TRANSIT TIME
	$hour = 0; //reset hour
	$utransit = get_transit($object, $date, $hour, $ourlong);
	if ($utransit < 25.0) {
		$transitt = 1;
	}

	//logic to sort the various rise and set states
	// nested if's...sorry
	if (($rise == 1) || ($sett == 1) || ($transitt == 1)) {   //current object rises, sets or transits today
		if ($rise == 1) {
			$obRise = $utrise;
			// below code was used to display results for testing, may be removed.
			//echo "<br>rise = $utrise";
			//$event = sprintf("rise");
			//display_event_time($utrise, $event);
		}
		else {
			$obRise = 0.0;
			//printf ("does not rise");
		}
		if ($transitt == 1) {
			$obTransit = $utransit;
			// below code was used to display results for testing, may be removed.
			///echo "<br>transit = $utransit";
			//$event = sprintf("transit");
			//display_event_time($utransit, $event);
		}
		else {
			$obTransit = 0.0;
			//printf ("does not transit");
		}
		if ($sett == 1) {
			$obSet = $utset;
			// below code was used to display results for testing, may be removed.
			//echo "<br>set = $utset";
			//$event = sprintf("set");
			//display_event_time($utset, $event);
		}
		else {
			$obSet = 0.0;
			//printf ("does not set");
		}
	
	}
	else { //current object not so simple
		if ($above == 1) {
			//printf ("always above horizon");
		}
		else {
			//printf ("always below horizon");
		}
	}

return;
}


/*********************************************************************/
function get_transit ($object, $mjd0, $hour, $ourlong)
{
//loop through all 24 hours of the day and store the sign of the angle in an array
//actually loop through 25 hours if we reach the 25th hour with out a transit then no transit condition today.

		while ($hour < 25.0)
	{
		$instant = $mjd0 + $hour / 24.0;
		$t = ($instant - 51544.5) / 36525;
		if ($object == 0) {
			moon ($t, $ra, $dec);
		}
		else {
			sun ($t, $ra, $dec);
		}
		$lha = (lmst($instant, $ourlong) - $ra);
        $LA = $lha * 15.04107;    //convert hour angle to degrees
        $sLA = $LA/abs($LA);      //sign of angle
		$hourarray[$hour] = $sLA;
		$hour++;
	}
//search array for the when the angle first goes from negative to positive
		$i = 0;
		while ($i < 25)
        {
            $loc_transit = $i;
            if ($hourarray[$i] - $hourarray[$i+1] == -2) {
                //we found our hour
                break;
            }

            $i++;
        }
//check for no transit, return zero
        if ($loc_transit > 23) {
            // no transit today
            $loc_transit = 0.0;
            return $loc_transit;
        }

//loop through all 60 minutes of the hour and store sign of the angle in an array
	$mintime = $loc_transit;
	while ($min < 60)
	{
		$instant = $mjd0 + $mintime / 24.0;
		$t = ($instant - 51544.5) / 36525;
		if ($object == 0) {
			moon ($t, $ra, $dec);
		}
		else {
			sun ($t, $ra, $dec);
		}
		$lha = (lmst($instant, $ourlong) - $ra);
		$LA = $lha * 15.04107;
        $sLA = (int)($LA/abs($LA));
        $minarray[$min] = $sLA;
		$min++;
        $mintime = $mintime + 0.016667;		//increment 1 minute
	}

    $i = 0;
	while ($i < 60)
    {
        if ($minarray[$i] - $minarray[$i+1] == -2) {
        //we found our min
        break;
        }
        $i++;
        $loc_transit = $loc_transit + 0.016667;
    }
return ($loc_transit);
}
/*********************************************************************/
function get_moon_phase ($JD, &$PhaseName, &$illumin)
{
	$IP = fpart( ( $JD - 2451550.1 ) / 29.530588853 );
	$age = $IP*29.53;
	$angle = $age * 13;
	$illumin = 0.5 * (1 - cos(deg2rad($angle)));

if( $age <  1.84566 ) {
		$PhaseName = "NEW";
		$Phase = 1;
	}
	else if( $age <  5.53699 ) {
		$PhaseName = "waxing crescent";
		$Phase = 2;
	}
	else if( $age <  9.22831 ) {
		$Phase = 3;
		$PhaseName = "first quarter";
	}
	else if( $age < 12.91963 ) {
		$Phase = 4;
		$PhaseName = "waxing gibbous";
	}
	else if( $age < 16.61096 ) {
		$Phase = 5;
		$PhaseName =  "FULL";
	}
	else if( $age < 20.30228 ) {
		$Phase = 6;
		$PhaseName = "waning gibbous";
	}
	else if( $age < 23.99361 ) {
		$Phase = 7;
		$PhaseName = "Last quarter";
	}
	else if( $age < 27.68493 ) {
		$Phase = 8;
		$PhaseName = "waning crescent";
	}
	else {
		$Phase = 9;
		$PhaseName =  "NEW";
	}

/*
echo "<br>Moon Phase = $PhaseName, ";
$illumin = $illumin*100;
echo round($illumin, 1);
echo "% illuminated, ";
echo round($age, 1);
echo " days since new.";
	//printf("\nAngle is %f", angle);
*/
return $age;
}

/*********************************************************************/
function sol_get_minor1 (&$minorstart1, &$minorstop1, $moonrise)
{
	//only calculate if the minor periods do not overlap prev or next days
	if ($moonrise >= 0.5 & $moonrise <= 23.0) {
		$minorstart1 = $moonrise - 0.5;
		$minorstop1 = $moonrise + 1.0;
	}
	else {
		$minorstart1 = 0.0;
		$minorstop1 = 0.0;
}
	

return;
}

/*********************************************************************/
function sol_get_minor2 (&$minorstart2, &$minorstop2, $moonset)
{
	if ($moonset >= 0.5 & $moonset <= 23.0) {
		$minorstart2 = $moonset - 0.5;
		$minorstop2 = $moonset + 1.0;
	}
else{
		$minorstart2 = 0.0;
		$minorstop2 = 0.0;
	}
return;
}

/*********************************************************************/
function sol_get_major1 (&$majorstart1, &$majorstop1, $moontransit)
{
	if ($moontransit >= 0.5 & $moontransit <= 22.0) {
		$majorstart1 = $moontransit - 0.5;
		$majorstop1 = $moontransit + 2.0;
	}
	else {
		$majorstart1 = 0.0;
		$majorstop1 = 0.0;
	}
return;
}
/*********************************************************************/
function sol_get_major2 (&$majorstart2, &$majorstop2, $moonunder)
{
	if ($moonunder >= 0.5 & $moonunder <= 22.0) {
		$majorstart2 = $moonunder - 0.5;
		$majorstop2 = $moonunder + 2.0;
	}
	else {
		$majorstart2 = 0.0;
		$majorstop2 = 0.0;
	}
return;
}

/*********************************************************************/
function phase_day_scale ($moonage)
{
$scale = 0;

if($moonage <  1.84566 ) {		//new
	$scale = 3;
	}
else if( $moonage <  5.53699 ) {
	$scale = 2;
	}
else if( $moonage < 12.91963 ) {
	$scale = 2;
	}
else if( $moonage < 16.61096 ) {		//full
   	$scale = 3;
	}
else if( $moonage < 20.30228 ) {
	$scale = 2;
	}
else if( $moonage < 27.68493 ) {
	$scale = 2;
	}
else {	//new
	$scale = 3;
	}

return $scale;	
}

/*********************************************************************/
function sol_get_dayscale ($moonrise, $moonset, $moontransit, $sunrise, $sunset)
{
//check if a solunar period occurs within 30 minutes of sun rise/set
//ok I know the following code sucks and fixing it is first on my todo list
$locsoldayscale = 0;
$check = 1.0;
//check minorstart1
$check = abs(($moonrise - 0.5) - $sunrise);
if ($check < 0.5) {
	$locsoldayscale = $locsoldayscale + 1;
}
$check = abs(($moonrise - 0.5) - $sunset);
if ($check < 0.5) {
	$locsoldayscale = $locsoldayscale + 1;
}
//check minorstop1
$check = abs(($moonrise + 1.0) - $sunrise);
if ($check < 0.5) {
	$locsoldayscale = $locsoldayscale + 1;
}
$check = abs(($moonrise + 1.0) - $sunset);
if ($check < 0.5) {
	$locsoldayscale = $locsoldayscale + 1;
}
//check minorstart2
$check = abs(($moonset - 0.5) - $sunrise);
if ($check < 0.5) {
	$locsoldayscale = $locsoldayscale + 1;
}
$check = abs(($moonset - 0.5) - $sunset);
if ($check < 0.5) {
	$locsoldayscale = $locsoldayscale + 1;
}
//check minorstop2
$check = abs(($moonset + 1.0) - $sunrise);
if ($check < 0.5) {
	$locsoldayscale = $locsoldayscale + 1;
}
$check = abs(($moonset + 1.0) - $sunset);
if ($check < 0.5) {
	$locsoldayscale = $locsoldayscale + 1;
}	
//check majorstart1
$check = abs(($moontransit - 0.5) - $sunrise);
if ($check < 0.5) {
	$locsoldayscale = $locsoldayscale + 1;
}
$check = abs(($moontransit - 0.5) - $sunset);
if ($check < 0.5) {
	$locsoldayscale = $locsoldayscale + 1;
}
//check majorstop1
$check = abs(($moontransit + 2.0) - $sunrise);
if ($check < 0.5) {
	$locsoldayscale = $locsoldayscale + 1;
}
$check = abs(($moontransit + 2.0) - $sunset);
if ($check < 0.5) {
	$locsoldayscale = $locsoldayscale + 1;
}
//check majorstart2
$check = abs(($moonunder - 0.5) - $sunrise);
if ($check < 0.5) {
	$locsoldayscale = $locsoldayscale + 1;
}
$check = abs(($moonunder - 0.5) - $sunset);
if ($check < 0.5) {
	$locsoldayscale = $locsoldayscale + 1;
}
//check majorstop2
$check = abs(($moonunder + 2.0) - $sunrise);
if ($check < 0.5) {
	$locsoldayscale = $locsoldayscale + 1;
}
$check = abs(($moonunder + 2.0) - $sunset);
if ($check < 0.5) {
	$locsoldayscale = $locsoldayscale + 1;
}

return $locsoldayscale;
}

/*********************************************************************/
function sol_display_dayscale ($soldayscale, $phasedayscale)
{
$dayscale = 0;
$dayscale = ($soldayscale + $phasedayscale);
echo "<br><br>Todays action is rated a $dayscale (scale is 0 thru 5, 5 is the best)";
return;
}

/*********************************************************************/
function convert_time_to_string ($doubletime)
{
/*split the time into hours (i) and minutes (d)*/
$d = fpart($doubletime);
$d = $d * 60;
$i = ipart($doubletime);
if ($d >= 59.5) {
	$i = $i + 1;
	$d = 0;
}

/*convert times to a string*/
if ($d < 10) {
$stringtime = sprintf("%.0f:0%.0f",$i , $d);
}
else {
	$stringtime = sprintf("%.0f:%.0f",$i , $d);
}
return $stringtime;
}


/*********************************************************************/
function display_event_time ($time, $event)
{
	//char sTime[6];
	$stringtime = convert_time_to_string ($time);
	printf("\n %s %s",$event, $stringtime);
return;
}
/*********************************************************************/
function get_underfoot ($date, $underlong)
{
	$loc_moonunderTime = get_transit (0, $date, 0, $underlong);

return ($loc_moonunderTime);
}
/*********************************************************************/

/*********************************************************************/



//end functions
?>
