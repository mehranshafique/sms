<?php
$lines=file(__DIR__.'/storage/logs/laravel.log');
$pat='/ERROR|SQLSTATE|ValidationException|StudentRequest|student_request|Integrity constraint/i';
foreach($lines as $i=>$line){if(preg_match('/local\.(ERROR|CRITICAL)/',$line)||preg_match($pat,$line)){echo 'L'.($i+1).': '.substr(rtrim($line),0,300).chr(10);}}
