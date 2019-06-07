<?php

#poller for sitemonitor analog inputs treated as counts
#Walk the table and identify interesting pieces by labels
$oids = snmp_walk($device, 'iso.3.6.1.4.1.32050.2.1.27.2', '-OsqnU', 'PACKETFLUX-SMI');
d_echo($oids."\n");

if ($oids) {
    echo 'sitemonitor ';

    $divisor = 1;
    $type    = 'sitemonitor';

    foreach (explode("\n", $oids) as $data) {
	$data = trim($data); 

	# match things that we want to graph as a count here
        if ($data and preg_match("/sat/i",$data)) {
            list($oid,$descr) = explode(' ', $data, 2);
            $split_oid        = explode('.', $oid);
            $index            = $split_oid[(count($split_oid) - 1)];
            $oidExpansion     = '.1.3.6.1.4.1.32050.2.1.27.3.'.$index;
	    $expansion        = (snmp_get($device, $oidExpansion, '-Oqv', 'PACKETFLUX-SMI') / 1);
            $oid              = '.1.3.6.1.4.1.32050.2.1.27.5.'.$index;
            $current          = (snmp_get($device, $oid, '-Oqv', 'PACKETFLUX-SMI') / $divisor);

	    #prefix with which expansion card the input is on
	    $descr            = 'exp' . $expansion . ':' . $descr;  
            echo "\n adding ".$descr."\n";
            discover_sensor($valid['sensor'], 'count', $device, $oid, $index, $type, $descr, $divisor, '1', null, null, null, null, $current);
        }
    }
}
echo "\n";
