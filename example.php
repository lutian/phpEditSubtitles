<?php

include('phpEditSubtitles.php');

$st = new phpEditSubtitles();

$st->setFile('test.srt');
// set output type to vtt (it will convert from srt to vtt type)
$st->setType('vtt');
$st->readFile();

// Edit subtitle on position 23
// IMPORTANT: it will reordenate the time. If the amount of time is smaller than $timeIni or bigger than $timeEnd the request will not be processed
$order    = 23;
$timeIni  = '00:01:10,880';
$timeEnd  = '00:01:18,830';
$subtitle = 'Edit subtitle';
$st->editSubtitle($order,$timeIni,$timeEnd,$subtitle);

// remove subtitle on position 25
$st->deleteSubtitle(25);

// add subtitle on position 25
// IMPORTANT: it will reordenate the time. If the amount of time is smaller than $timeIni or bigger than $timeEnd the request will not be processed
$order    = 25;
$timeIni  = '00:01:31,010';
$timeEnd  = '00:01:32,790';
$subtitle = 'New subtitle';
$st->addSubtitle($order,$timeIni,$timeEnd,$subtitle);

// save subtitles in a new file
$st->saveFile('newfile');

// get array of subtitles
$subtitles = $st->getSubtitles();

echo '<pre>';
print_r($subtitles);
echo '</pre>';
