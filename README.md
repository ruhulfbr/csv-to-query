# CSV to SQL Query Generator

This package provides a simple utility to convert data from a CSV file into SQL queries for database insertion.

## Installation

To install the package, you can use [Composer](https://getcomposer.org/):

```bash
composer require ruhulfbr/query-generator-from-csv
```

## Usage

```php
<?php

require_once 'vendor/autoload.php';

use Ruhulfbr\QueryGeneratorFromCsv\QueryGenerator;

$filePath = "sample.csv";  // (String) Required, Absolute file path
$createQuery = false; // (Boolean) Optional, set true if need to generate table create query, Default is FALSE;
$tableName = ""; // (String) Optional, If tableName not provided then csv filename will be the table name, Default is an empty string;

// With Named argument
// $generator = new QueryGenerator($filePath, _TABLE_NAME: "your_table_name");
// $generator = new QueryGenerator($filePath, _CREATE_QUERY: true);

// Together
$generator = new QueryGenerator($filePath, $createQuery, $tableName);
print_r($generator->generate());

```
## Response

```php
//Success
stdClass Object
(
    [type] => "success"
    [message] => "Query generated"
    [query] => "INSERT INTO `example` (`id`, `name`, `age`) VALUES ('1', '“Allis”', '24');
               INSERT INTO `example` (`id`, `name`, `age`) VALUES ('2', '\'Gwyneth’', '36');
               INSERT INTO `example` (`id`, `name`, `age`) VALUES ('3', 'Sashenka', '49')";
)

//Error
stdClass Object
(
    [type] => "error"
    [message] => "Invalid file path"
)
```

## Contributing

Pull requests are welcome. For major changes, please open an issue first
to discuss what you would like to change.

Please make sure to update tests as appropriate.

## 
This documentation provides clear instructions on the installation and usage of the package. It includes examples and explanations of each parameter, making it easy for users to understand how to use the package in their projects.