<!DOCTYPE html>
<style>
@import url('https://fonts.googleapis.com/css?family=Cantarell|Slabo+27px');

body {
margin:0;
padding:0;
font-family: 'Slabo 27px', serif;
}

h3 {
font-family: 'Cantarell', sans-serif;
}

#json {
font-size: 0.8em;
}

#cwd, #folders, #files {
border-radius:5px;
padding:0.5em;
}

#cwd {
background: #faebd7;
overflow: auto;
}

#folders {
background-color: #FCF6F1;
}

.breadcrumb {
float:left;
margin:1px;
}
/*
.left {
width:63%;
margin: 20px 2%;
}
*/

.left {
margin: 20px 2%;
}

.right {
width:33%;
float: right;
}

.clickable {
border: 1px solid black;
padding:15px;
margin:10px;
}

.clickable:hover {
color:#6699EE;
outline: 2px solid #6699EE;
border: 1px solid #6699EE;
text-shadow: 0 0 1px;
}

/* player */
.top-pad {
height:3em;
padding-bottom:12px;
}

#player {
width:100%;
background: #AEAEAE;
display: flex;
align-items: center;
position: fixed;
top:0;
padding: 10px 0px;
}

#playbutton {
margin-left:2%;
/*padding: 20px;*/
}

#song {
width:50%;
margin-left:1em;
}

#timeline {
width: 100%;
height: 1em;
}

#full-timeline {
padding-top: 0.5em;
border-bottom: 2px solid white;
}

#elapsed-timeline {
margin-top: -2px;
border-top: 2px solid black;
width: 0;
}

.duration {
float:right;
}

.duration-loading > span {
  animation-name: blink;
  animation-duration: 1.4s;
  animation-iteration-count: infinite;
  animation-fill-mode: both;
}

.duration-loading > span:nth-child(2) {
  animation-delay: .2s;
}

.duration-loading > span:nth-child(3) {
  animation-delay: .4s;
}

@keyframes blink {
  0% {
    opacity: .2;
  }
  20% {
    opacity: 1;
  }
  100% {
    opacity: .2;
  }
}

/* buttons */
.play, .pause {
    position:relative;
    padding:3px;
    width:3em;
    height:3em;
    background-color:transparent;
    border:1px solid black;
}

.play:hover, .pause:hover {
    border:1px solid #6699EE;
    outline: 1px solid #6699EE;
}

.play:hover:before {
    border-left: 1.5em solid #6699EE;
}

.play:before {
    content: " ";
    position:absolute;
    margin-top:-0.9em;
    margin-left:-0.7em;
    z-index:2;
    border-top: 1em solid transparent;
    border-bottom: 1em solid transparent;
    border-left: 1.5em solid black;
}

.pause:hover:before, .pause:hover:after {
    background-color:#6699EE;
}

.pause:before, .pause:after {
    content: " ";
    position:absolute;
    background-color:black;
    width:0.55em;
    height:1.5em;
    margin-top:-0.7em;
    margin-left:-0.7em;
    content: " ";
    z-index:2;
}

.pause:after{
    margin-left:0.1em;
}

</style>
<html>
<?php
/*
 * @param string $dir is the path to get contents of
 * @return array $results
 *
 */
function getDirContents($dir) {
    $results = [
	"name" => $dir,
	"files" => [],
	"folders" => [],
    ];

    $elems = scandir($dir);
    foreach($elems as $key => $value){
	$path = $dir.DIRECTORY_SEPARATOR.$value;
	if(!is_dir($path)) {
	    $results["files"][] = $path;
	} 
	else if($value != "." && $value != "..") {
	    // not sure why there are 2 args to this
	    // $results["folders"][] = getDirContents($path, $results[$path]);
	    $results["folders"][] = getDirContents($path);
	}
    }

    return $results;
}
$root = 'files';
$contents = getDirContents($root);
?>

<body>
<div class="top-pad"></div>
<div id="player">
    <button id="playbutton" class="play"></button>
    <div id="song">
	<div id="playing"></div>
	<div id="timeline">
	    <div id="full-timeline"></div>
	    <div id="elapsed-timeline"></div>
	    <div id="playhead"></div>
	</div>
    </div>
</div>
<div class="right">
    <!--h3> json </h3>
    <pre id="json"></pre-->
</div>
<div class="left">
    <div id="browser">
	<div id="cwd"></div>
	<div>
	    <!--h3> folders </h3-->
	    <div id="folders"></div>
	</div>
	<!--h3> files </h3-->
	<div id="files"></div>
    </div>
</div>
<script>
function toMMSS(str) {
    var sec_num = parseInt(str, 10); // don't forget the second param
    var minutes = Math.floor((sec_num) / 60);
    var seconds = sec_num - (minutes * 60);

    if (minutes < 10) {minutes = "0"+minutes;}
    if (seconds < 10) {seconds = "0"+seconds;}
    var time = minutes+':'+seconds;
    return time;
}

function AudioFile(path, elem, durationElem) {
    this.path = path;
    this.elem = elem;
    this.durationElem = durationElem;
    this.duration = null;
    this.current_time = 0;
    this.elem.addEventListener('loadedmetadata', function() {
	this.duration = this.elem.duration;
	this.durationElem.className = "duration";
	this.durationElem.innerHTML = toMMSS(this.duration);
    }.bind(this));
}


function Timeline() {
    this.elapsed_div = document.getElementById("elapsed-timeline");
    this.full_div = document.getElementById("full-timeline");
    this.playhead = document.getElementById("playhead");
    this.update(0,1);
}


Timeline.prototype.update = function(cur_time, total_time) {
    percent = cur_time / total_time * 100;
    this.elapsed_div.style.width = percent.toString() + "%";
}

function Player() {
    this.playing = false;
    this.current_audio = null;
    this.button = document.getElementById("playbutton");
    this.button.addEventListener("click", this.toggle.bind(this));
    this.div = document.getElementById("playing");
    this.div.innerHTML = "not playing";
    this.timeline = new Timeline();
}

Player.prototype.toggle = function() {
    if (this.playing) {
	this.current_audio.elem.pause();
	this.playing = false;

	this.button.className = 'play';
    }
    else if (this.current_audio != null) {
	this.current_audio.elem.play();
	this.playing = true;

	this.button.className = 'pause';
	this.div.innerHTML = this.current_audio.path;
    }
}

Player.prototype.trigger = function(audio_file) {
    if (this.current_audio != null && this.current_audio != audio_file) {
	this.current_audio.elem.pause();
	this.playing = false;
	this.current_audio = null;
    }

    if (this.current_audio === null) {
	this.current_audio = audio_file;
	console.log("triggered");
	this.current_audio.elem.addEventListener('timeupdate', function() {
	    console.log(this.current_audio.elem.currentTime);
	    console.log(this.current_audio.duration);
	    this.timeline.update( this.current_audio.elem.currentTime, this.current_audio.duration);
	}.bind(this));
    }
    this.toggle();
}

Player.prototype.clear = function() {
    // this.current_audio.elem.pause();
    this.playing = false;
    this.current_audio = null;
    this.div.innerHTML = "not playing";
    this.button.className = 'play';
}

function addParents(dir, par = null) {
    dir.par = par
    for (i in dir.folders) {
        addParents(dir.folders[i], dir);
    }
}

function basename(str) {
   var base = new String(str).substring(str.lastIndexOf('/') + 1); 
    if(base.lastIndexOf(".") != -1)       
        base = base.substring(0, base.lastIndexOf("."));
   return base;
}

function makeFolderDiv(folder) {
    var div = document.createElement("div");
    div.className = "clickable folder";
    div.innerHTML = folder.name;
    div.addEventListener("click", function() {
	changeDir(folder);
    });
    return div;
}

function makeBCDiv(folder) {
    var div = document.createElement("div");
    div.className = "clickable breadcrumb";
    div.innerHTML = folder.name.split("/").pop();
    div.addEventListener("click", function() {
	changeDir(folder);
    });
    return div;
}

function makeAudio(filename) {
    var elem = document.createElement("audio");
    elem.src = filename;

    var durationElem = document.createElement("div");
    durationElem.className = "duration duration-loading";
    durationElem.innerHTML = "<span>.</span><span>.</span><span>.</span>";

    var audiofile = new AudioFile(filename, elem, durationElem);
    return audiofile;
}

function makeAudioDiv(div, name) {
    var audiofile = makeAudio(name);
    div.appendChild(audiofile.elem);
    div.appendChild(audiofile.durationElem);
    div.addEventListener("click", function() {
	player.trigger(audiofile);
    });
}

function makeFileDiv(name) {
    var div = document.createElement("div");
    div.className = "clickable files";
    div.innerHTML = basename(name);

    var ext = name.substring(name.lastIndexOf('.') + 1);
    if (ext === "ogg") {
	makeAudioDiv(div, name);
    }
    return div;
}

// returns a list of Dirs
function getParentDirs(cwd) {
    var parents = [cwd];
    var rent = cwd.par;
    while (rent != null) {
	parents.push(rent);
	rent = rent.par;
    }
    return parents.reverse();
}

function changeDir(cwd) {
    player.clear();

    fileFrag = document.createElement("div");
    fileFrag.id = "files";
    for (f in cwd.files) {
	fileFrag.appendChild(makeFileDiv(cwd.files[f]));
    }

    folderFrag = document.createElement("div");
    folderFrag.id = "folders";
    // if (cwd.par != null) {
    //     folderFrag.appendChild(makeFolderDiv(cwd.par));
    // }
    for (f in cwd.folders) {
	folderFrag.appendChild(makeFolderDiv(cwd.folders[f]));
    }
    cwdFrag = document.createElement("div");
    cwdFrag.id = "cwd";
    var parents = getParentDirs(cwd);
    for (i in parents) {
	cwdFrag.appendChild(makeBCDiv(parents[i]));
    }
    // var cwd_accordion = "";
    // var tree = cwd.name.split("/");
    // for (i in tree) {
    //     cwd_accordion = (i == 0) ? tree[i] : [cwd_accordion, tree[i]].join("/");
    //     getParentDir(cwd_accordion, cwd);
    //     // need to search for folder in json
    //     cwdFrag.appendChild(makeFolderDiv({'name':cwd_accordion}));
    // }
    document.getElementById("cwd").replaceWith(cwdFrag);
    // document.getElementById("cwd").innerHTML = cwd.name;
    document.getElementById("files").replaceWith(fileFrag);
    document.getElementById("folders").replaceWith(folderFrag);
}

function openPath(path) {
    // subtract root from path
    root = data.name;
    paths = path.split("/")
	.filter(n => n)
	.filter(n => n == root ? false : true);
    iterpath = root;
    dir = data;
    for (i in paths) {
	iterpath += "/" + paths[i];
	console.log(iterpath+"\n");
	if (dir.files.includes(iterpath)) {
	    break;
	}
	for (f in dir.folders) {
	    console.log("name is " + dir.folders[f].name);
	    if (dir.folders[f].name === iterpath) {
		dir = dir.folders[f];
		break;
	    }
	}
    }
    changeDir(dir);
}

var player = new Player();
var data = <?php echo json_encode($contents); ?>;
addParents(data);
changeDir(data);

qs = location.search.substring(1).split("&");

for (i in qs) {
    s = qs[i].split("=");
    k = s[0];
    if (k == "path") {
	path = decodeURI(s[1]);
	openPath(path);
	break;
    }
}

// var nondata = <?php echo json_encode($contents); ?>;
// document.getElementById("json").innerHTML = JSON.stringify(nondata, undefined, 2);
</script>
</body>

</html>
