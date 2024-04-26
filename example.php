<?php

require_once 'vendor/autoload.php';

use Ruhulfbr\QueryGeneratorFromCsv\Generator;

$filePath = "example.csv";  // (String) Required, Absolute file path
$createQuery = false; // (Boolean) Optional, set true if need to generate table create query
$tableName = ""; // (String) Optional, If tableName not provided then csv filename will be the table name;

$generator = new Generator($filePath, $createQuery, $tableName);
print_r($generator->generate());