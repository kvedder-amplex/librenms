<?php
/**
 * sitemonitor.inc.php
 *
 * LibreNMS voltage discovery module for Packetflux SiteMonitor
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       http://librenms.org
 * @copyright  2017 Neil Lathwood
 * @author     Neil Lathwood <gh+n@laf.io>
 */

$oid = '.1.3.6.1.4.1.32050.2.1.27.5.1';
$current = (snmp_get($device, $oid, '-Oqv') / 10);
discover_sensor($valid['sensor'], 'voltage', $device, $oid, 1, 'sitemonitor', 'Shunt Input', 10, 1, null, null, null, null, $current);

$oid = '.1.3.6.1.4.1.32050.2.1.27.5.2';
$current = (snmp_get($device, $oid, '-Oqv') / 10);
discover_sensor($valid['sensor'], 'voltage', $device, $oid, 2, 'sitemonitor', 'Power 1', 10, 1, null, null, null, null, $current);

$oid = '.1.3.6.1.4.1.32050.2.1.27.5.3';
$current = (snmp_get($device, $oid, '-Oqv') / 10);
discover_sensor($valid['sensor'], 'voltage', $device, $oid, 3, 'sitemonitor', 'Power 2', 10, 1, null, null, null, null, $current);


#poller for sitemonitor analog inputs treated as voltages
#Walk the table and identify interesting pieces by labels
$oids = snmp_walk($device, 'iso.3.6.1.4.1.32050.2.1.27.2', '-OsqnU', 'PACKETFLUX-SMI');
d_echo($oids."\n");

if ($oids) {
    echo 'sitemonitor ';

    $divisor = 10;
    $type    = 'sitemonitor';

    foreach (explode("\n", $oids) as $data) {
	$data = trim($data); 

	# match things that we want to graph as a voltage here
        if ($data and preg_match("/.1v/i",$data)) {
            list($oid,$descr) = explode(' ', $data, 2);
            $split_oid        = explode('.', $oid);
            $index            = $split_oid[(count($split_oid) - 1)];
            $oidExpansion     = '.1.3.6.1.4.1.32050.2.1.27.3.'.$index;
	    $expansion        = (snmp_get($device, $oidExpansion, '-Oqv', 'PACKETFLUX-SMI') / 1);
            $oid              = '.1.3.6.1.4.1.32050.2.1.27.5.'.$index;
            $current          = (snmp_get($device, $oid, '-Oqv', 'PACKETFLUX-SMI') / $divisor);

	    #prefix with which expansion card the input is on
	    $descr            = 'exp' . $expansion . ':' . $descr;  
	    if($expansion >0) { # ignore the voltages from the base, handled previously
               echo "\n adding ".$descr."\n";
               discover_sensor($valid['sensor'], 'voltage', $device, $oid, $index, $type, $descr, $divisor, '1', null, null, null, null, $current);
	    }
        }
    }
}
echo "\n";
