<?php

class phpEditSubtitles {
	
	private $file = '';
	private $subtitles = array();
	
	public function addSubtitle($order,$timeIni,$timeEnd,$subtitle) {
		// verify time consistence
		$timeRight = $this->verifyTimeConsistence($order,$timeIni,$timeEnd);
		if(!$timeRight) {
			return;
		}
		
		$arrParams[] = array(
			'order' => $order,
			'time_ini' => $timeIni,
			'time_end' => $timeEnd,
			'subtitle' => utf8_decode($subtitle)."\r\n",
		);
		array_splice( $this->subtitles, ($order-1), 0, $arrParams );
		$this->verifyTimeMovement($order);
	}
	
	public function deleteSubtitle($order) {
		$subtitles = $this->getSubtitles();
		unset($subtitles[($order-1)]);
		$this->setSubtitles(array_values($subtitles));
	}
	
	public function editSubtitle($order,$timeIni,$timeEnd,$subtitle) {
		// verify time consistence
		$timeRight = $this->verifyTimeConsistence($order,$timeIni,$timeEnd);
		if(!$timeRight) {
			return;
		}
		
		$arrParams[] = array(
			'order' => $order,
			'time_ini' => $timeIni,
			'time_end' => $timeEnd,
			'subtitle' => utf8_decode($subtitle)."\r\n",
		);
		array_splice( $this->subtitles, ($order-1), 1, $arrParams );
		$this->verifyTimeMovement($order);
	}
	
	public function listSubtitles() {
		return $this->getSubtitles();
	}
	
	public function saveFile($newFile = '') {
		if(empty($newFile)) $newFile = time().'_edited_subtitles.srt';
		@file_put_contents($newFile,$this->dumpSubtitles());
	}
	
	private function dumpSubtitles() {
		$dump = '';
		for($i=0;$i<count($this->subtitles);$i++) {
			$dump .= ($i+1)."\r";
			$dump .= $this->subtitles[$i]['time_ini'].' --> '.$this->subtitles[$i]['time_end']."\r";
			$dump .= $this->subtitles[$i]['subtitle']."\r\n";
		}
		return $dump;
	}
	
	private function verifyTimeMovement($order) {
		// get the time ini/end
		$currTimeIni = $this->timeToMilliseconds($this->subtitles[($order-1)]['time_ini']);
		$currTimeEnd = $this->timeToMilliseconds($this->subtitles[($order-1)]['time_end']);
		
		// get the time ini/end
		$currTimeIniPrev = $currTimeIni;
		$currTimeEndPrev = $currTimeEnd;
		// lookup in subtitles for the prev/next time
		// next time
		$arrNext = array();
		for($i=$order;$i<count($this->subtitles);$i++) {
			$eachTimeIni = $this->timeToMilliseconds($this->subtitles[$i]['time_ini']);
			$eachTimeEnd = $this->timeToMilliseconds($this->subtitles[$i]['time_end']);
			if($currTimeEnd > $eachTimeIni) {
				$newEachMillisecondsIni = $eachTimeIni+($currTimeEnd - $eachTimeIni);
				$newEachTimeIni = $this->millisecondsToTime($newEachMillisecondsIni);
				$newEachTimeEnd = $this->millisecondsToTime($newEachMillisecondsIni+($eachTimeEnd-$eachTimeIni));
				$arrNext[] = array('pos' => $i, 'time_ini' => $newEachTimeIni, 'time_end' => $newEachTimeEnd);
				$this->subtitles[$i]['time_ini'] = $newEachTimeIni;
				$this->subtitles[$i]['time_end'] = $newEachTimeEnd;
				$currTimeEnd = $newEachMillisecondsIni+($eachTimeEnd-$eachTimeIni);
			}
		}

		// prev time
		$arrPrev = array();
		for($i=($order-2);$i>-1;$i--) {
			$eachTimeIni = $this->timeToMilliseconds($this->subtitles[$i]['time_ini']);
			$eachTimeEnd = $this->timeToMilliseconds($this->subtitles[$i]['time_end']);
			if($currTimeIniPrev < $eachTimeEnd) {
				$newEachMillisecondsEnd = $eachTimeEnd-($eachTimeEnd-$currTimeIniPrev);
				$newEachTimeEnd = $this->millisecondsToTime($newEachMillisecondsEnd);
				$newEachTimeIni = $this->millisecondsToTime($newEachMillisecondsEnd-($eachTimeEnd-$eachTimeIni));
				$arrPrev[] = array('pos' => $i, 'time_ini' => $newEachTimeIni, 'time_end' => $newEachTimeEnd, 'diff' => $eachTimeEnd-$eachTimeIni);
				$this->subtitles[$i]['time_ini'] = $newEachTimeIni;
				$this->subtitles[$i]['time_end'] = $newEachTimeEnd;
				$currTimeIniPrev = $newEachMillisecondsEnd-($eachTimeEnd-$eachTimeIni);
			}
		}		
	}
	
	private function timeToMilliseconds($time) {
		$arr = explode(":",$time);
		$arr2 = explode(",",$arr[2]);
		$hs = $arr[0];
		$mi = $arr[1];
		$sc = $arr2[0];
		$ml = $arr2[1];
		$milliseconds = ($hs*3600*1000)+($mi*60*1000)+($sc*1000)+$ml;
		return $milliseconds;
	}
	
	private function millisecondsToTime($milliseconds) {
		$sc = floor($milliseconds / 1000);
		$mi = floor($sc / 60);
		$hs = floor($mi / 60);
		$milliseconds = $milliseconds % 1000;
		$sc = $sc % 60;
		$mi = $mi % 60;
		
		$format = '%02u:%02u:%02u,%03u';
		$time = sprintf($format, $hs, $mi, $sc, $milliseconds); 
		return $time;
	}
	
	private function verifyTimeConsistence($order,$timeIni,$timeEnd) {
		// get the time ini/end
		$currTimeIni = $this->timeToMilliseconds($timeIni);
		$currTimeEnd = $this->timeToMilliseconds($timeEnd);
		$consistence = 0;
		if($currTimeIni < $currTimeEnd) {
			$consistence += 1;
		} else {
			echo '<p>inconsistence of time';
		}
		
		$arrMountOfTime = $this->getAmountOfTime($order);
		
		if($currTimeIni > $arrMountOfTime['toStart']) {
			$consistence += 1;
		} else {
			echo '<p>Time start exceded';
		}
		
		if($currTimeEnd < $arrMountOfTime['toEnd']) {
			$consistence += 1;
		} else {
			echo '<p>Time end exceded';
		}
		
		return ($consistence>2)?true:false;
	}
	
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
	
	public function readFile() {
		$handle = @fopen($this->getFile(),"r");
	
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

	public function setFile($file) {
		$this->file = $file;
	}
	
	public function getFile() {
		return $this->file;
	}

	public function setSubtitles($subtitles) {
		$this->subtitles = $subtitles;
	}
	
	public function getSubtitles() {
		return $this->subtitles;
	}

}