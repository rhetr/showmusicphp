<!DOCTYPE html>
<html>
<head>
<title>mosic | (/0)</title>
 <meta charset="UTF-8">
<meta name="viewport" content="width=347, initial-scale=1">
<link rel="stylesheet" href="style.css">
</head>
<body>
<script src="http://www.wavesurfer.fm/dist/wavesurfer.min.js"></script>
<script>
var players = [];
</script>
<h1>(Music for the underground)</h1>
<div id="wrapper">
<div class="flex-container c1">
<?php
session_start();
if (!isset($_SESSION["cwd"])) { 
    $_SESSION['cwd'] = 'files/';
}
if (isset($_GET["cwd"])) { 
    if ($_GET['cwd'] == basename($_SESSION['cwd'])) {
	$_SESSION['cwd'] = $_SESSION['cwd'];
    }
    else if ($_GET['cwd'] == 'back') {
	$_SESSION['cwd'] = dirname($_SESSION['cwd']).'/';
    }
    else {
	$_SESSION['cwd'] = $_SESSION['cwd'].$_GET["cwd"].'/';
    }
    echo "<div class='flex-item small'><a href=?cwd=back>back</a></div>"; 
}
else { 
    $_SESSION['cwd'] = 'files/';
}
foreach (glob($_SESSION['cwd']."*", GLOB_ONLYDIR) as $dir) {
    echo "<div class='flex-item small'><a href=?cwd=" . rawurlencode(basename($dir)) . ">" . basename($dir) . "</a></div>"; 
}
?>
</div>
<div class="flex-container c2">
<?php
foreach (glob($_SESSION['cwd']."*.{ogg,wav,mp3,m4a}", GLOB_BRACE) as $key=>$filename) {
    $name = basename($filename);
    echo "
	<div class='flex-item'><a href='$filename'>$name</a>
	<div class='player'>
	<audio id='audio$key'>
	<source src='$filename'/>
	</audio>
	<button id='button$key' class='play'></button>
	<div id='timeline$key' class='timeline'>
	     <div id='playhead$key' class='playhead'></div>
	     <div id='time$key' class='time'><span id='curtime$key'></span><span id='duration$key'></span></div>
	</div>
	</div>
	<script> players.push('$key'); </script>
	</div>";
}
?>
</div>

<script>
String.prototype.toMMSS = function () {
    var sec_num = parseInt(this, 10); // don't forget the second param
    var minutes = Math.floor((sec_num) / 60);
    var seconds = sec_num - (minutes * 60);

    if (minutes < 10) {minutes = "0"+minutes;}
    if (seconds < 10) {seconds = "0"+seconds;}
    var time    = minutes+':'+seconds;
    return time;
}

var playing = false;
for (var i=0; i<players.length; i++) {
    var key = players[i];
    var button = document.getElementById('button'+key);
    button.timeline = document.getElementById('timeline'+key);
    button.audio = document.getElementById('audio'+key);
    button.audio.playhead = document.getElementById('playhead'+key);
    button.audio.curtime = document.getElementById('curtime'+key);
    button.audio.durationElement = document.getElementById('duration'+key);

    //button.timeline.audio = button.audio;
    //button.timeline.tWidth = button.timeline.offsetWidth - button.audio.playhead.offsetWidth;

    button.audio.addEventListener("timeupdate", function() {
	var playPercent = (this.currentTime / this.duration);
	this.durationElement.innerHTML = '/'+this.duration.toString().toMMSS();
	this.curtime.innerHTML = this.currentTime.toString().toMMSS();
	var width = playPercent * 12.1;
	this.playhead.style.width = width + 'em';
    } , false);

    button.addEventListener("click", function () {
	var pause = this.className === 'pause';
	this.className = pause ? 'play' : 'pause';
	if (pause) {
	    this.audio['pause']();
	    playing = false;
	}
	else {
	    if (playing) {
		playing.className = 'play';
		playing.audio['pause']();
	    }
	    this.audio['play']();	    
	    playing = this;
	}
	return false;
    });

    //button.timeline.addEventListener("click", function (event) {
    //    var offset = event.pageX - this.offsetLeft;
    //    if (offset >= 0 && offset <= this.tWidth) {
    //        this.audio.playhead.style.width = offset + 'px';
    //    } else if (offset < 0) {
    //        this.audio.playhead.style.width = '0';
    //    } else {
    //        this.audio.playhead.style.width = this.tWidth + 'px';
    //    }
    //    this.audio = this.audio.duration * ((event.pageX - this.offsetLeft)/this.tWidth);


    //}, false);
}

</script>
</div>
</body>
</html>
