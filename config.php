<?php

// Set this to your own database values. Also make sure to run the installation .SQL file.
$db = array (
    'host' => 'localhost',
    'user' => 'YOURUSERNAME',
    'pass' => 'YOURPASSWORD',
    'database' => 'runetimer',
);

// The two $oreMap variables below map the ores to their respawn times. You can also add anything else you would like time that respawns based on the population linearly. (Bosses, other resources, etc.). Uses just a simple timeleft(in seconds) = m * population + b equation.

using the equation timeLeft(in seconds) = m * population + b. 

// RuneScape
$oreMap_RS = array(
    0 => array('name' => 'Adamant',  'm' => -0.11890971312492, 'b' => 477.90457445517),
    1 => array('name' => 'Non-wild', 'm' => -0.375,            'b' => 1500),
    2 => array('name' => 'Wild',     'm' => -0.25,             'b' => 1000)
);

// Old-School Scape
$oreMap_07 = array(
    0 => array('name' => 'Adamant',  'm' => -0.11890971312492, 'b' => 477.90457445517),
    1 => array('name' => 'Runite', 'm' => -0.375,            'b' => 1500)
);

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

?>
