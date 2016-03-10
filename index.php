<?php
$notes = Array("C"=>"C", "Csharp"=>"C#", "D"=>"D", "Eflat"=>"Eb", "E"=>"E", "F"=>"F", "Fsharp"=>"F#", "G"=>"G", "Aflat"=>"Ab", "A"=>"A", "Bflat"=>"Bb", "B"=>"B");
$types = Array("M"=>"major", "m"=>"minor", "dim"=>"diminished", "sus2"=> "sus 2", "sus4"=>"sus 4", "7"=>"7", "M7"=>"M7", "m7"=>"m7", "mM7"=>"mM7", "add9"=>"add 9");
$ukuleleFretMax = 22;
?>
<html>
<head>
	<script src="jquery.min.js"></script>
	<script>
	//get GET variable
	var jGets = new Array ();
	<?
	if(isset($_GET)) {
	    foreach($_GET as $key => $val) echo "jGets[\"$key\"]=\"$val\";\n";
	}
	?>
	//set up notes and chords behaviour
	var ukuleleFretMax = <?php echo $ukuleleFretMax; ?>;
	var notes = {"C":0, "Csharp":1, "D":2, "Eflat":3, "E":4, "F":5, "Fsharp":6, "G":7, "Aflat":8, "A":9, "Bflat":10, "B":11};
	var types = {"M": [0,4,7], "m": [0,3,7], "sus2": [0,2,7], "sus4": [0,5,7], "dim": [0,3,6], "7":[0,4,7,10], "M7":[0,4,7,11], "m7":[0,3,7,10], "mM7":[0,3,7,11], "add9":[0,2,4,7]};
	var Chord = function(root, type) {
		this.root = notes[root];
		this.type = type;
		var chordNotesArray = new Array ();
		for (var index = 0; index < types[type].length; index++) {
			var currentNote = (types[type][index] + this.root) % 12;
			chordNotesArray.push(currentNote);
		};
		this.notes = chordNotesArray;
		if (this.type == "M") {
			this.name = this.root;
		} else {
			this.name = this.root + this.type;
		}
		if (jQuery.inArray(this.type, ["dim", "sus2", "sus4", "mM7", "add9"]) != -1) {
			this.shapeRange = 3;
		} else {
			this.shapeRange = 2;
		}
	};
	//set up fretboard
	var line = {1: notes["G"], 2: notes["C"], 3: notes["E"], 4: notes["A"]};

	function noteWithPosition(fret, onLine) {
		var currentNote = (line[onLine]+fret) % 12;
		return currentNote;
	}

	//locate notes of chord
	Chord.prototype.notePositions = function() {
		var allPositions = new Array();
		for (var lineNum = 1; lineNum<=4; lineNum++) {
			var positions = new Array();
			for (var fretPosition = 0; fretPosition <= ukuleleFretMax; fretPosition++){
				var currentNote = noteWithPosition(fretPosition,lineNum);
				if (jQuery.inArray(currentNote, this.notes) != -1) {
					positions.push(1);
				} else {
					positions.push(0);
				}
			}
			allPositions.push(positions);
		}
		return allPositions;
	};

	//generate chord pattern
	function sortNumber(a,b) {
		return a - b;
	}
	function checkIfOne(value) {
		if (value==1) {
			return true;
		} else {
			return false;
		}
	}
	Chord.prototype.chordShapeFinder = function() {
		var chordShapes = new Array();
		var allPositions = this.notePositions();
		for (var fretPosition = 0; fretPosition <= ukuleleFretMax-this.shapeRange; fretPosition++) {
			if (fretPosition==0) {
				var useShapeRange = this.shapeRange+1;
			} else {
				var useShapeRange = this.shapeRange;
			}
			var isChord = true;
			var allFirstAreZero = true;
			var chordShape = new Array();
			var variantShapeInLine = new Array();
			chordShape.push(fretPosition);
			var lineCount = 1;
			for (var position in allPositions) {
				var positionArray = allPositions[position];
				var fretSlice = positionArray.slice(fretPosition,fretPosition+useShapeRange+1);
				if (jQuery.inArray(1, fretSlice) != -1) {
					if (fretSlice[0] == 1) {
						allFirstAreZero = false;
					}
					var fretSliceDropZero = fretSlice.filter(checkIfOne);
					if (fretSliceDropZero.length==1) {
						chordShape.push(fretSlice);
					} else {
						variantShapeInLine.push(lineCount);
						var first = jQuery.inArray(1, fretSlice);
						fretSlice.splice(first,1,0);
						var newFlatSlice = fretSlice.slice();
						var second = jQuery.inArray(1,newFlatSlice);
						variantShapeInLine.push(newFlatSlice);
						fretSlice.splice(second,1,0);
						fretSlice.splice(first,1,1);
						chordShape.push(fretSlice);
					}
				} else {
					isChord = false;
				}
				lineCount++;
			}
			if (allFirstAreZero) {
				isChord = false;
			}
			if (isChord) {
				//trim empty fret at the end
				var allLastAreZero = true;
				var endIndex = chordShape[1].length-1;
				for (var lineNum = 1; lineNum <= 4; lineNum++) {
					if (chordShape[lineNum][endIndex] == 1) {
						allLastAreZero = false;
					}
				};
				if (allLastAreZero) {
					for (var lineNum = 1; lineNum <= 4; lineNum++) {
						chordShape[lineNum].splice(endIndex,1);
					};
				};
				//check whether all notes exists
				var onFret = chordShape[0];
				var chordNotesInShape = new Array();
				for (var lineNum = 1; lineNum <= 4; lineNum++) {
					for (var currentFretPosition = 0; currentFretPosition < chordShape[lineNum].length; currentFretPosition++) {
						if (chordShape[lineNum][currentFretPosition] == 1) {
							var currentNote = noteWithPosition(onFret+currentFretPosition, lineNum);
							if (jQuery.inArray(currentNote, chordNotesInShape) == -1) {
								chordNotesInShape.push(currentNote);
							};
						};
					};
				};
				chordNotesInShape.sort(sortNumber);
				if (this.notes.sort(sortNumber).toString() == chordNotesInShape.toString()) {
					chordShapes.push(chordShape);
				}
				// process variant chord shape on the same fret
				if (variantShapeInLine.length > 0) {
					if (chordShape[1].length < variantShapeInLine[1].length) {
						for (var lineNum = 1; lineNum <= 4; lineNum++) {
							chordShape[lineNum].push(0);
						}
					}
					chordNotesInShape = new Array();
					var variantIndex = variantShapeInLine[0];
					var newChordShape = chordShape.slice();
					newChordShape.splice(variantIndex,1,variantShapeInLine[1]);
					allFirstAreZero = true;
					for (var lineNum = 1; lineNum <= 4; lineNum++) {
						if (newChordShape[lineNum][0] == 1) {
							allFirstAreZero = false;
						}
					};
					if (!allFirstAreZero) {
						for (var lineNum = 1; lineNum <= 4; lineNum++) {
							for (var currentFretPosition = 0; currentFretPosition < newChordShape[lineNum].length; currentFretPosition++) {
								if (newChordShape[lineNum][currentFretPosition] == 1) {
									var currentNote = noteWithPosition(onFret+currentFretPosition, lineNum);
									if (jQuery.inArray(currentNote, chordNotesInShape) == -1) {
										chordNotesInShape.push(currentNote);
									};
								};
							};
						};
						chordNotesInShape.sort(sortNumber);
						if (this.notes.sort(sortNumber).toString() == chordNotesInShape.toString()) {
							chordShapes.push(newChordShape);
						}
					};
				}
			};
		}
		return chordShapes;
	};

	// highlight given chord

	function highlight(currentChordNum) {
		var addShadowSelector = "span.chordNum[chord='"+currentChordNum+"']";
		$(addShadowSelector).addClass("clicked");
		var highlightSelector = "span.finger[chord='"+currentChordNum+"']";
		var dimSelector = "span.finger[chord!='"+currentChordNum+"']";
		$(highlightSelector).addClass("highlight").fadeTo("fast",1);
		$(dimSelector).fadeTo("fast", 0.15);
	}
	function unhighlight(previousChordNum) {
		var removeShadowSelector = "span.chordNum[chord='"+previousChordNum+"']";
		$(removeShadowSelector).removeClass("clicked");
		var unhighlightSelector = "span.finger[chord='"+previousChordNum+"']";
		$(unhighlightSelector).removeClass("highlight").fadeTo("fast",0.15);
	}

	//tweak previous and next button

	function previousNext(currentChordNum, chordTotal) {
		if (currentChordNum==0) {
			$("div#previous").fadeTo("fast", 0.3).removeClass("hover");
		}
		if (currentChordNum!=0) {
			$("div#previous").fadeTo("fast", 0.7).addClass("hover");
		}
		if (currentChordNum<=chordTotal-2) {
			$("div#next").fadeTo("fast", 0.7).addClass("hover");
		}
		if (currentChordNum==chordTotal-1) {
			$("div#next").fadeTo("fast", 0.3).removeClass("hover");
		}
	}

	$( document ).ready(function() {
		//setting variables
		var chordNumClicked = false;
		var clickedChordNum = 0;
		if (!jGets["root"]) {
			jGets["root"] = "C";
			jGets["type"] = "M";
		};
		var root = jGets["root"];
		var type = jGets["type"];
		var currentChord = new Chord(root,type);
		var chordShapes = currentChord.chordShapeFinder();
		var previousOnFret;
		//generate chord shapes and show on fretboard
		for (var i = 0; i < chordShapes.length; i++) {
			var chordShape = chordShapes[i];
			var onFret = chordShape[0];
			var fretSelector = "div[fret="+onFret+"]";
			if (i%2==1) {
				var addClassOddEven = "even";
			} else {
				var addClassOddEven = "odd";
			}
			if (onFret==previousOnFret) {
				var addClassLeftRight = "right";
			} else {
				var addClassLeftRight = "left";
			}
			var appendString = '<span class="chordNum '+addClassLeftRight+'" chord="'+i+'">'+(i+1)+'</span>';
			$(fretSelector).children("div.fretNum").append(appendString);
			for (var lineNum = 1; lineNum <= 4 ; lineNum++) {
				var fretPosition = jQuery.inArray(1,chordShape[lineNum]) + onFret;
				fretSelector = "div[fret='"+fretPosition+"']";
				//generate spots for finger position
				appendString = '<span class="finger" chord="'+i+'"></span>';
				$(fretSelector).children("div.line").eq(lineNum-1).append(appendString);
				$(fretSelector).children("div.line").eq(lineNum-1).children("span.finger").last().addClass(addClassOddEven);
			};
			previousOnFret = onFret;
		};
		//preselect first chord and set up interface
		highlight(0);
		$("div#next").addClass("hover");
		$("div#previous").css("opacity", "0.3");
		$("html, body").scrollTop();
		//main behaviour of web app
		$("span.chordNum").click(function(){
			var chordNum = $(this).attr("chord");
			if (chordNum!=clickedChordNum) {
				highlight(chordNum);
				unhighlight(clickedChordNum);
				clickedChordNum = chordNum;
				var chordCount = chordShapes.length;
				previousNext(clickedChordNum, chordCount);
			}
		});
		$("div#next, div#previous").click(function(){
			var thisId = $(this).attr("id");
			var chordNum = clickedChordNum;
			var chordCount = chordShapes.length;
			if (((thisId=="previous")&&(chordNum!=0))||((thisId=="next")&&(chordNum<chordCount-1))) {
				switch(thisId) {
					case "next":
						chordNum++;
						break;
					case "previous":
						chordNum--;
						break;
				}
				highlight(chordNum);
				unhighlight(clickedChordNum);
				var addShadowSelector = "span.chordNum[chord="+chordNum+"]";
				$("html, body").animate({
					scrollTop: $(addShadowSelector).offset().top-120
				},400);
				clickedChordNum = chordNum;
			};
			previousNext(clickedChordNum, chordCount);
		});
		//control form submit mechanism
		var timeOutVar;
		$("select[name='root'], select[name='type']").focusin(function(){
			clearTimeout(timeOutVar);
		});
		$("select[name='root'], select[name='type']").change(function(){
			timeOutVar = setTimeout(function() {
				$("form").submit();
			}, 400);
		});
	})
	</script>
	<link rel="stylesheet" type="text/css" href="style.css">
	<link rel="manifest" href="manifest.json">
	<title>Ukulele Chords</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="mobile-web-app-capable" content="yes">
</head>
<body>
	<div id="query">
		<form action="index.php" method="get">
			<label>
				<select name="root">
					<?php
					foreach ($notes as $key => $value) {
						echo '<option value="'.$key.'"';
						if ($key==$_GET["root"]) {
							echo " selected";
						}
						echo '>'.$value.'</option>';
					}
					?>
				</select>
			</label>
			<label>
				<select name="type">
					<?php
					foreach ($types as $key => $value) {
						echo '<option value="'.$key.'"';
						if ($key==$_GET["type"]) {
							echo " selected";
						}
						echo '>'.$value.'</option>';
					}
					?>
				</select>
			</label>
		</form>
	</div>
	<div id="ukulele-neck">
		<?php
		for ($i = 0; $i <= $ukuleleFretMax; $i++) {
			echo '<div class="fret" fret="'.$i.'">';
			for ($j = 1; $j <= 4; $j++) {
				echo '<div class ="line" line="'.$j.'"><span class="line"></span></div>';
			}
			echo '<div class="fretNum">'.$i.'</div>';
			echo '</div>';
		}
		?>
	</div>
	<div id="next">&raquo;</div>
	<div id="previous">&laquo;</div>
</body>
</html>