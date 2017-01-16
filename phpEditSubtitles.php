<?php

class phpEditSubtitles {
	
	private $version = '1.1';
	
	/** @var string */
	private $file = '';
	
	/** @var string */
	private $type = 'srt';
	
	/** @var string */
	private $originalType = 'srt';
	
	/** @var array */
	private $subtitles = array();
	
	/*
	* Add a row to array $subtitles
	* @param: int $order
	* @param: string $timeIni
	* @param: string $timeEnd
	* @param: string $subtitle
	* @param: bool $moveTime (default: true)
	*/
	public function addSubtitle($order,$timeIni,$timeEnd,$subtitle,$moveTime = true) {
		// verify time consistence
		$this->verifyTimeConsistence($order,$timeIni,$timeEnd);
		
		// create the array
		$arrParams[] = array(
			'order' => $order,
			'time_ini' => $timeIni,
			'time_end' => $timeEnd,
			'subtitle' => utf8_decode($subtitle)."\r\n",
		);
		
		// if I want to move the time of the consecuents rows it must to be true
		if($moveTime) $this->verifyTimeMovement($order,$timeIni,$timeEnd);
		
		// add row
		array_splice( $this->subtitles, ($order-1), 0, $arrParams );
	}
	
	/*
	* Delete row from array $subtitles
	* @param: int $order
	*/
	public function deleteSubtitle($order) {
		$subtitles = $this->getSubtitles();
		unset($subtitles[($order-1)]);
		$this->setSubtitles(array_values($subtitles));
	}
	
	/*
	* Edit row on array $subtitles
	* @param: int $order
	* @param: string $timeIni
	* @param: string $timeEnd
	* @param: string $subtitle
	* @param: bool $moveTime (default: true)
	*/
	public function editSubtitle($order,$timeIni,$timeEnd,$subtitle,$moveTime = true) {
		// verify time consistence
		$this->verifyTimeConsistence($order,$timeIni,$timeEnd);
		
		// create the array
		$arrParams[] = array(
			'order' => $order,
			'time_ini' => $timeIni,
			'time_end' => $timeEnd,
			'subtitle' => utf8_decode($subtitle)."\r\n",
		);
		
		// if I want to move the time of the consecuents rows it must to be true
		if($moveTime) $this->verifyTimeMovement($order,$timeIni,$timeEnd);
		
		// replace row
		array_splice( $this->subtitles, ($order-1), 1, $arrParams );
	}
	
	/*
	* List array $subtitles
	* @return: array
	*/
	public function listSubtitles() {
		return $this->getSubtitles();
	}
	
	/*
	* Save file
	* @param: string $newFile
	*/
	public function saveFile($newFile = '') {
		if(empty($newFile)) $newFile = time().'_edited_subtitles.'.$this->getType();
		else $newFile = $newFile.'.'.$this->getType();
		@file_put_contents($newFile,$this->dumpSubtitles());
	}
	
	/*
	* Dump array $subtitles into a string
	* @return: string $dump
	*/
	private function dumpSubtitles() {
		$dump = '';
		for($i=0;$i<count($this->subtitles);$i++) {
			if($this->type == 'srt') $dump .= ($i+1)."\r";
			$this->subtitles[$i]['time_ini'] = $this->convertType($this->subtitles[$i]['time_ini']);
			$this->subtitles[$i]['time_end'] = $this->convertType($this->subtitles[$i]['time_end']);
			$dump .= $this->subtitles[$i]['time_ini'].' --> '.$this->subtitles[$i]['time_end']."\r";
			$dump .= $this->subtitles[$i]['subtitle']."\r\n";
		}
		return $dump;
	}
	
	/*
	* Verify if have to move time of the consecuents rows
	* @param: int $order
	* @param: string $timeIni
	* @param: string $timeEnd
	*/
	private function verifyTimeMovement($order,$timeIni,$timeEnd) {
		// get the time ini/end
		$currTimeIni = $this->timeToMilliseconds($this->subtitles[($order-1)]['time_ini']);
		$currTimeEnd = $this->timeToMilliseconds($this->subtitles[($order-1)]['time_end']);
		
		$currTimeIniNew = $this->timeToMilliseconds($timeIni);
		$currTimeEndNew = $this->timeToMilliseconds($timeEnd);
		
		$diffTimeIni = $currTimeIniNew - $currTimeIni;
		$diffTimeEnd = $currTimeEndNew - $currTimeEnd;
		
		$this->moveTime($order,$diffTimeIni,$diffTimeEnd);	
	}
	
	/*
	* Convert time string into milliseconds
	* @param: string $time
	* @return: int $milliseconds
	*/
	private function timeToMilliseconds($time) {
		$arr = explode(":",$time);
		if(strpos(".",$arr[2]) !== false) $arr2 = explode(".",$arr[2]);
		else $arr2 = explode(",",$arr[2]);
		$hs = $arr[0];
		$mi = $arr[1];
		$sc = $arr2[0];
		$ml = $arr2[1];
		$milliseconds = ($hs*3600*1000)+($mi*60*1000)+($sc*1000)+$ml;
		return $milliseconds;
	}
	
	/*
	* Convert time milliseconds to format hs:mi:ss,mil
	* @param: int $milliseconds
	* @return: string $time
	*/
	private function millisecondsToTime($milliseconds) {
		$sc = floor($milliseconds / 1000);
		$mi = floor($sc / 60);
		$hs = floor($mi / 60);
		$milliseconds = $milliseconds % 1000;
		$sc = $sc % 60;
		$mi = $mi % 60;
		
		if($this->type == 'vtt') $format = '%02u:%02u:%02u.%03u';
		else $format = '%02u:%02u:%02u,%03u';
		$time = sprintf($format, $hs, $mi, $sc, $milliseconds); 
		return $time;
	}
	
	/*
	* Verify time consistence
	* @param: int $order
	* @param: string $timeIni
	* @param: string $timeEnd
	*/
	private function verifyTimeConsistence($order,$timeIni,$timeEnd) {
		// get the time ini/end
		$currTimeIni = $this->timeToMilliseconds($timeIni);
		$currTimeEnd = $this->timeToMilliseconds($timeEnd);
		
		// verify time consistence
		if($currTimeIni > $currTimeEnd) {
			die('Inconsistence of time');
		}
		
		// get the total amount of time
		$arrMountOfTime = $this->getAmountOfTime($order);
		
		// verify if current time initial is smaller than the amount of time from current time until start
		if($currTimeIni < $arrMountOfTime['toStart']) {
			die('Time start exceded');
		}
		
		// verify if current time finel is bigger than the amount of time from current time until the end
		if($currTimeEnd > $arrMountOfTime['toEnd']) {
			die('Time end exceded');
		}
	}
	
	/*
	* Move time for all rows
	* @param: int $order
	* @param: int $diffIni
	* @param: int $diffEnd
	*/
	private function moveTime($order,$diffIni,$diffEnd) {
		$arrSub = array();
		
		// move the time for consecuents rows greater than current time
		for($i=0;$i<count($this->subtitles);$i++) {
			if($i > ($order-1)) {
				$eachTimeIni = $this->timeToMilliseconds($this->subtitles[$i]['time_ini']);
				$eachTimeEnd = $this->timeToMilliseconds($this->subtitles[$i]['time_end']);
				$timeIni = $this->millisecondsToTime($eachTimeIni + $diffEnd);
				$timeEnd = $this->millisecondsToTime($eachTimeEnd + $diffEnd);
				$this->subtitles[$i]['time_ini'] = $timeIni;
				$this->subtitles[$i]['time_end'] = $timeEnd;
			}
		}
		
		// get the current time initial before change
		$currTimeIni = $this->timeToMilliseconds($this->subtitles[($order-1)]['time_ini'])+$diffIni;

		// move the time for consecuents rows smaller than current time
		if($diffIni < 0) {
			for($i=($order-1);$i>-1;$i--) {
				if($i < ($order-1)) {
					$eachTimeIni = $this->timeToMilliseconds($this->subtitles[$i]['time_ini']);
					$eachTimeEnd = $this->timeToMilliseconds($this->subtitles[$i]['time_end']);
					$eachDiff = $eachTimeEnd - $eachTimeIni;
					 
					if($currTimeIni < $eachTimeEnd) {
						$eachNewTimeIni = (($eachTimeIni + $diffIni)<0?0:($eachTimeIni + $diffIni));
						$eachNewTimeEnd = (($eachTimeEnd + $diffIni)<0?0:($eachTimeEnd + $diffIni));
						
						$timeIni = $this->millisecondsToTime($eachNewTimeIni);
						$timeEnd = $this->millisecondsToTime($eachNewTimeEnd);
						$this->subtitles[$i]['time_ini'] = $timeIni;
						$this->subtitles[$i]['time_end'] = $timeEnd;
						$currTimeIni = $timeIni;
					}
				}
			}
		}
	}
	
	/*
	* Get amount of time from current position until start and end
	* @param: int $order
	* @return array
	*/
	private function getAmountOfTime($order) {

		$millisecondsToStart = 0;
		for($i=($order-2);$i>-1;$i--) {
			$eachTimeIni = $this->timeToMilliseconds($this->subtitles[$i]['time_ini']);
			$eachTimeEnd = $this->timeToMilliseconds($this->subtitles[$i]['time_end']);
			$millisecondsToStart += ($eachTimeEnd-$eachTimeIni);
		}
		$millisecondsToEnd = 0;
		for($i=($order);$i<count($this->subtitles);$i++) {
			$eachTimeIni = $this->timeToMilliseconds($this->subtitles[$i]['time_ini']);
			$eachTimeEnd = $this->timeToMilliseconds($this->subtitles[$i]['time_end']);
			$millisecondsToEnd += ($eachTimeEnd-$eachTimeIni);
		}
		
		return array('toStart' => $millisecondsToStart, 'toEnd' => $millisecondsToEnd);
	}
	
	/*
	* Read file
	*/
	public function readFile() {
		$handle = @fopen($this->getFile(),"r");
		
		$this->detectFileType($this->getFile());
	
		$arrSubtitle = array();
		$i = 0;
		$found = false;
		$started = false;
		if ($handle) {
			while ($buffer = fgets($handle, 4096)) {

				// verifico si empieza el bloque de subtitulo
				$arr = $this->findSubtitleBlock($buffer);

				$found = $arr['found'];
							
				if($found) {
					$arrSubtitle[$i]['order'] = ($i+1);
					$arrSubtitle[$i]['time_ini'] = $arr['time_ini'];
					$arrSubtitle[$i]['time_end'] = $arr['time_end'];
					$arrSubtitle[$i]['subtitle'] = '';
					$started = true;
				} else {
					if($started) {
						if($buffer != "\r\n") {
							$arrSubtitle[$i]['subtitle'] .= $buffer;
						} else {
							$started = false;
							$i++;
						}
					}
				} 
				
			 }
			 fclose($handle);
			 $this->setSubtitles($arrSubtitle);
		}
	}
	
	/*
	* Find block of subtitles
	* @param: string $string
	* @return: array
	*/
	public function findSubtitleBlock($string) {
		$arr['found'] = false;
		$pattern = ' --> '; 
		$pos = strpos($string, $pattern);
		if ($pos !== false && strlen($string) == 31) { 
			$arr['found'] = true;
			$arr['time_ini'] = substr($string,0,12);
			$arr['time_end'] = substr($string,17,12);
		}
		return $arr;
	}
	
	/*
	* Convert time from originalType to new type (srt -> vtt or vtt -> srt)
	*/
	private function convertType($time) {
		if($this->originalType != $this->getType()) return $this->millisecondsToTime($this->timeToMilliseconds($time));
		else return $time;
	}
	
	/*
	* Detect type of original file
	*/
	private function detectFileType($file){
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		switch($ext){
			case "srt":
			case "vtt":
				$type = $ext;
				break;
			default:
				$type = 'srt';
				break;
		}
		$this->originalType = $type;
	}

	/* Getters and Setters */
	public function setFile($file) {
		$this->file = $file;
	}
	
	public function getFile() {
		return $this->file;
	}
	
	public function setType($type) {
		$this->type = $type;
	}
	
	public function getType() {
		return $this->type;
	}

	public function setSubtitles($subtitles) {
		$this->subtitles = $subtitles;
	}
	
	public function getSubtitles() {
		return $this->subtitles;
	}

}