<?php

require('config.php');

// Time script execution to include in time-left calculation (should be minimal, but doesn't hurt, provides to-the-second accuracy.)
$time = explode(' ', microtime());
$startTime = $time[1] + $time[0];

$defaultOre = isset($_COOKIE['rt07defaultore']) ? $_COOKIE['rt07defaultore'] : 1; // Sets default selected ore (from $oreMap_07 in config.php)

// Sets the style or if a style has just been selected, set cookie and refresh.
$style = !empty($_COOKIE['rtstyle']) ? $_COOKIE['rtstyle'] : 'style';
if (isset($_GET['style'])) {
    switch ($_GET['style']) {
      case 'style_compact':
        $style = 'style_compact';
        break;
      case 'style':
        $style = 'style';
        break;
    }
    setCookie('rtstyle', $style, time() + 604800);  
    header('Location: index07.php');
}

function retrieveWorldPop($world) {
    $content = file_get_contents('http://oldschool.runescape.com/slu');
    preg_match('/e\((?:[0-9]+,)(?:true|false),(?:[0-7]+),(?:[^,]+),([0-9]+),(?:[^,]+),(?:[^,]+),"Old School ' . $world . '",(?:[^,]+),(?:[^\)]+)\);/', $content, $matches);
    if (isset($matches[1])) {
        return $matches[1];
    } else {
        return null;
    }
}

function timeLeft($population, $oreID) {
    global $oreMap_07;
    return round($oreMap_07[$oreID]['m'] * $population + $oreMap_07[$oreID]['b']); // timeLeft = m * population + b
}
    
function scriptDuration($startTime) {
    $time = explode(' ', microtime());
    $endTime = $time[1] + $time[0];
    $totalTime = round(($endTime - $startTime), 4);
    return $totalTime;
}
    
$conn = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['database'], $db['user'], $db['pass']);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

if (isset($_GET['delete'])) {

    $stmt = $conn->prepare('DELETE FROM timers WHERE (id = :id AND INET_NTOA(ip) = :ip)');
    $stmt->execute(array(
        'id' => (int) $_GET['delete'],
        'ip' => $_SERVER['REMOTE_ADDR']
    ));
    header('Location: index07.php');
}

if (isset($_GET['remine'])) {
    $stmt = $conn->prepare('SELECT world, ore, population FROM timers  WHERE (id = :id AND INET_NTOA(ip) = :ip AND version = 1) LIMIT 1');
    $stmt->execute(array(
        'id' => (int) $_GET['remine'],
        'ip' => $_SERVER['REMOTE_ADDR']
    ));

    $result = $stmt->fetch();

    $timeLeft = timeLeft($result['population'], $result['ore']);
    $scriptDuration = scriptDuration($startTime);

    $stmt = $conn->prepare('UPDATE timers SET time = :now, timefinished = :timeFinished WHERE (id = :id AND INET_NTOA(ip) = :ip)');
    $stmt->execute(array(
        'now'          => (int) $_SERVER['REQUEST_TIME'],
        'timeFinished' => (int) ($_SERVER['REQUEST_TIME'] + $timeLeft + $scriptDuration),
        'id'           => (int) $_GET['remine'],
        'ip'           => $_SERVER['REMOTE_ADDR']
    ));

    header('Location: index07.php');
}

// New timer
if (isset($_POST['world'])) {
    
    $population = retrieveWorldPop( (int)$_POST['world']);
    if ($population === null) {
        exit('Are you sure that \'' . (int)$_POST['world'] . '\' is a valid world number? If it is, world population parser has been broken, most likely due to an update on the old-school world selection screen. Please contact me at TexasMd91@gmail.com and I\'ll look into it. Thanks!');
    }
    $language = 0; // Only one language for old-school scape.

    $timeLeft = timeLeft($population, (int) $_POST['ore']);
    $scriptDuration = scriptDuration($startTime);

    setCookie('rt07defaultore', (int) $_POST['ore'], time() + 604800);
    try {
        $stmt = $conn->prepare('INSERT INTO timers(ip, ore, time, timefinished, world, population, version) VALUES (INET_ATON(:ip), :ore, :now, :timeFinished, :world, :population, 1)');
        $stmt->execute(array(
            'ip'           => $_SERVER['REMOTE_ADDR'],
            'ore'          => (int) $_POST['ore'],
            'now'          => (int) $_SERVER['REQUEST_TIME'],
            'timeFinished' => (int) ($_SERVER['REQUEST_TIME'] + $timeLeft + $scriptDuration),
            'world'        => (int) $_POST['world'],
            'population'   => $population
        ));
    } catch(PDOException $e) {
        echo "Error " . $e->getMessage();
    }
    // header('Location: index07.php');   Not needed, will be in database by time rest of page loads.
}
    
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" type="text/css" href="<?php echo $style; ?>.css" />
    <title>RuneTimer - Runescape Rune Ore Respawn Timer</title>  
    <meta name="description" content="A manageable rune ore respawn timer list to help you mine the most ores in Runescape." />
    <style type="text/css">
        <?php echo '#s_' . $style . ' { font-weight: bold; pointer-events: none; cursor: default; }'; ?>
        #s_07scape { font-weight: bold; pointer-events: none; cursor: default; }
    </style>
    <script language="javascript" type="text/javascript">
        var Timer = function(currenttimestamp, timestamp,databaseId) {       
            this.databaseId = databaseId;
            this.currenttimestamp = currenttimestamp;
            this.Interval = 1000;
            this.Enable = new Boolean(false);
            this.timestamp = timestamp;
            this.timeleft;
            this.secondsleft;
            this.minutesleft;
            this.tick;
            var timerId = 0;
            var thisObject;
            
            this.Enable = new Boolean(true);
            thisObject = this;

            if (thisObject.Enable) {   
                
                this.tick = function() {
                    if (thisObject.timeleft <= 0) {

                        document.getElementById("timer" + thisObject.databaseId).innerHTML = "Ore Available";
                        document.getElementById("timer" + thisObject.databaseId).className="red";
                        reminediv = document.getElementById('remineore' + thisObject.databaseId);
                        newlink = document.createElement('a');
                        newlink.setAttribute('id', 'reminelink' + thisObject.databaseId);
                        newlink.setAttribute('href', 'index07.php?remine=' + thisObject.databaseId);
                        reminediv.appendChild(newlink);
                        
                        document.getElementById('reminelink' + thisObject.databaseId).innerHTML='<img src="images/action_refresh.png" />';
                        
                        thisObject.Enable = new Boolean(false);
                        clearInterval(thisObject.timerId);  
                    } else {
                         if (thisObject.timeleft <= 60) {
                            if (thisObject.timeleft % 2 == 0) {
                                document.getElementById("timer" + thisObject.databaseId).className="normal";
                            } else {
                                document.getElementById("timer" + thisObject.databaseId).className="red1";
                            }
                        }
                        
                        document.getElementById("timer" + thisObject.databaseId).innerHTML = this.timeleft;
                        thisObject.currenttimestamp = thisObject.currenttimestamp + 1;
                        thisObject.timeleft = thisObject.timestamp - thisObject.currenttimestamp;
                        thisObject.minutesleft = Math.floor(thisObject.timeleft / 60);
                        thisObject.secondsleft = thisObject.timeleft % 60;
                        if (thisObject.secondsleft < 10) {
                            thisObject.secondsleft = "0" + thisObject.secondsleft;
                        }
                        document.getElementById("timer" + thisObject.databaseId).innerHTML = thisObject.minutesleft + ":" + thisObject.secondsleft;
                    }
                    
                }
                thisObject.timerId = setInterval( this.tick, thisObject.Interval);
            }
        };
    </script>
</head>
<body>
    <div id="wrapper">
        <div id="footer" style="font-size: 10px;"><a href="index07.php?style=style" id="s_style">Full View</a> | <a href="index07.php?style=style_compact" id="s_style_compact">Compact View</a> :: <a href="index.php" id="s_runescape">RuneScape</a> | <a href="index07.php" id="s_07scape">07scape</a></div>
        <h1>RuneTimer07</h1>
        <div id="left">  
            <h2>New Ore Timer</h2>
            <p>
                <form id="newworld" method="post" action="">
                    <label>World: </label> 
                        <input name="world" type="text" size="15" maxlength="3" id="worldinput" />
                    <br />
                    <label>Type: </label>
                        <select name="ore" id="oreinput" />
                        <?php
                            foreach($oreMap_07 as $id => $ore) {
                                if ($ore['name'] != null) {
                                    if ($id == $defaultOre) {
                                        echo '<option value=' . $id . ' selected>' . $ore['name'] . '</option>';
                                    } else {
                                        echo '<option value=' . $id . '>' . $ore['name'] . '</option>';
                                    }
                                }
                            }
                        ?>
                        </select>
                    <br />
                    <input type="submit" name="submit" id="submit" value="Add Timer" />
                </form>
            </p>
        </div>
        <div id="right">
            <h2>Timer List</h2>
           
            <form action="index07.php" method="get" name="alter">  
                <table id="timertable" cellspacing="0">
                    <tr>
                        <th scope="col" width="22px" abbr=""></th>
                        <th scope="col" abbr="World">World</th>
                        <th scope="col" abbr="Time Left">Time Left</th>
                        <th scope="col" abbr="Ore Type">Ore Type</th>
                        <th scope="col" width="16px" abbr="Re-mine"></th>
                        <th scope="col" width="16px" abbr="Delete"></th>
                    </tr>
                    <?php

                    $stmt = $conn->prepare('
                        SELECT timers.id, INET_NTOA(timers.ip) AS ip, timers.time, timers.timefinished, timers.world, timers.ore, languages.img as language
                        FROM timers 
                        LEFT JOIN languages ON timers.language = languages.id
                        WHERE ip = INET_ATON(:ip) AND version = 1
                        ORDER BY timefinished ASC'
                    );
                    $stmt->execute(array('ip' => $_SERVER['REMOTE_ADDR']));

                    $i = 0;
                    while($row = $stmt->fetch()) {
                        $timeLeft = ($row['timefinished'] - $_SERVER['REQUEST_TIME']);

                        echo '<tr class="row' . ($i % 2) . '">';
                        echo '<td><img src="images/' . $row['language'] .'" /></td>';
                        echo '<td>World ' . $row['world'] . '</td>';

                        if ($timeLeft > 0) {
                            $remineOre = false;
                            $minutesleft = floor($timeLeft / 60);
                            $secondsleft = $timeLeft % 60;
                            if ($secondsleft < 10) { 
                                $secondsleft = "0" . $secondsleft; 
                            }
                            echo '<td><span id="timer' . $row['id'] . '">' . $minutesleft . ':' . $secondsleft . '</span></td>';
                            echo '<script language="javascript" type="text/javascript">';
                            echo 'var timer' . $row['id'] . ' = new Timer(' . $_SERVER['REQUEST_TIME'] . ', ' . $row['timefinished'] . ', ' . $row['id'] . ');';
                            echo '</script>';
                        } else {  
                            $remineOre = true;  
                            echo '<td><span class="red" id="timer' . $row['id'] . '">Ore Available</span></td>';
                        }

                        echo '<td>' . $oreMap_07[$row['ore']]['name'] . '</td>';

                        if ($remineOre) {
                            echo '<td id="remineore' . $row['id'] . '"><a href="index07.php?remine=' . $row['id'] . '" id="remine' . $row['id'] . '"><img src="images/action_refresh.png" /></a></td>'; 
                        } else { 
                            echo '<td id="remineore' . $row['id'] . '"></td>';
                        }

                        echo '<td><a href="index07.php?delete=' . $row['id'] . '"><img src="images/action_delete.png" /></a></td>';
                        echo '</tr>';

                        $i++; 
                    }
                    ?>   
                </table>
            </form>
        </div>
       
        <div id="footer" style="text-align: center;">Copyright &copy; 2009-2014 :: Created by Mark Dowdle (Atroxide) - TexasMd91@gmail.com</div>
        </div>
    </body>
</html>
