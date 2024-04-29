<?php

require_once 'vendor/autoload.php';

use Ruhulfbr\CsvToQuery\Query;

$filePath = "example.csv";  // (String) Required, Absolute file path
$createQuery = true; // (Boolean) Optional, set true if need to generate table create query, Default is FALSE;
$tableName = "your_table_name"; // (String) Optional, If tableName not provided then csv filename will be the table name, Default is an empty string;

// With Named argument
// $query = new Query($filePath, _TABLE_NAME: "your_table_name");
// $query = new Query($filePath, _CREATE_QUERY: true);

// Together
$query = new Query($filePath, $createQuery, $tableName);
print_r($query->generate());