<?php

include('phpEditSubtitles.php');

$st = new phpEditSubtitles();

$st->setFile('test.srt');
$st->readFile();

// Edit the first subtitle
// IMPORTANT: it will reordenate the time. If the amount of time is smaller than $timeIni or bigger than $timeEnd the request will not be processed
$order    = 1;
$timeIni  = '00:00:00,090';
$timeEnd  = '00:00:01,830';
$subtitle = 'Replace the first subtitle';
$st->editSubtitle($order,$timeIni,$timeEnd,$subtitle);

// remove subtitle in position 25
$st->deleteSubtitle(25);

// add subtitle on position 145
// IMPORTANT: it will reordenate the time. If the amount of time is smaller than $timeIni or bigger than $timeEnd the request will not be processed
$order    = 145;
$timeIni  = '00:05:05,050';
$timeEnd  = '00:08:05,130';
$subtitle = 'New subtitle';
$st->addSubtitle($order,$timeIni,$timeEnd,$subtitle);

// save subtitles in a new file
$st->saveFile('newfile.srt');

// get array of subtitles
$subtitles = $st->getSubtitles();

echo '<pre>';
print_r($subtitles);
echo '</pre>';
