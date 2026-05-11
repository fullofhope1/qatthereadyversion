<?php
$lines = file('C:/xampp/apache/logs/error.log');
$last = array_slice((array)$lines, -40);
foreach($last as $l) echo $l;
