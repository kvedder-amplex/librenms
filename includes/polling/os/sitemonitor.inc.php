<?php

/*
 * LibreNMS OS Polling module for packetflux
 *
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

$smdescr    = $device['sysDescr'];
$smdescr    = preg_replace('/^.*base/i','Base',$smdescr);
$version    = $smdescr . ' (' . snmp_get($device, ".1.3.6.1.4.1.32050.2.1.25.3.0","-OQv", "PACKETFLUX-SMI") . ')';

$oids = snmp_walk($device, '.1.3.6.1.4.1.32050.2.1.25.2', '-OsqnU', 'PACKETFLUX-SMI');

if ($oids) {

    foreach (explode("\n", $oids) as $data) {
	$data = trim($data); 

        if ($data) {
            list($oid,$descr) = explode(' ', $data, 2);
            $split_oid        = explode('.', $oid);
            $index            = $split_oid[(count($split_oid) - 1)];
            $expansionSerial = snmp_get($device, '.1.3.6.1.4.1.32050.2.1.25.4.'.$index, '-Oqv', 'PACKETFLUX-SMI');

	    if($index >0) { # ignore the base, handled previously
		  $features .= $descr ."(".$expansionSerial.");";
	    }
        }
    }
}

$features = substr_replace($features,"",-1);

$latQty = 0;
$lngQty = 0;
$latTot = 0;
$lngTot = 0;

// Pull analog inputs
$oids = snmp_walk($device, '.1.3.6.1.4.1.32050.2.1.27.2', '-OsqnU', 'PACKETFLUX-SMI');
if ($oids) {

    foreach (explode("\n", $oids) as $data) {
	$data = trim($data); 

	// Process the Lat/Long, generating an average for values returned
        if ($data and preg_match("/Latitude|Longitude/",$data)) {
            list($oid,$descr) = explode(' ', $data, 2);
            $split_oid        = explode('.', $oid);
            $index            = $split_oid[(count($split_oid) - 1)];
            $value = (snmp_get($device, '.1.3.6.1.4.1.32050.2.1.27.5.'.$index, '-Oqv', 'PACKETFLUX-SMI')/100000);
	    if(preg_match("/Latitude/",$descr) and abs($value)>0) {
		    $latQty+=1;
		    $latTot+=$value;
	    }
	    if(preg_match("/Longitude/",$descr) and abs($value)>0) {
		    $lngQty+=1;
		    $lngTot+=$value;
	    }
	    echo "Found... ".$descr." ".$value."\n";
	    if($latQty>0 and $latQty == $lngQty) {
		    $lat=$latTot/$latQty;
		    $lng=$lngTot/$lngQty;
		    echo "Matching pairs, running average ".$lat.", ".$lng."\n";
	    }

        }
    }
}

$coord = "[".round($location->lat,3).", ".round($location->lng,3)."]";
$newcoord = "[".round($lat,3).", ".round($lng,3)."]";


// If the coordinates are different and the new values are populdated, update
if (isset($location) and $coord != $newcoord and abs($lat)>0 and abs($lng)>0) {
    $location->lat = $lat;
    $location->lng = $lng;
    $location->save();
    log_event('Location Update '. $coord . ' -> ' . $newcoord.'('.$lat.','.$lng.')', $device, 'system', 3);
}

// clear variables that don't need to persist.
unset(
	$latitutude, $lat, 
	$longitude, $lng,
	$coord, $newcoord,
   $smdescr, $expansionVersion, $index, $split_oid, $value
);

