<?php

namespace Ruhulfbr\QueryGeneratorFromCsv;

class Generator
{
    private string $_TABLE_NAME;
    private string $_FILE_PATH;
    private bool $_CREATE_QUERY;
    private object $_RESPONSE;
    private array $_COLUMNS = [];
    private array $_ROWS = [];
    private bool $_HAS_ID_COLUMN = TRUE;

    /**
     * Class constructor.
     *
     * Initializes the object with provided file path, optional table name, and options.
     *
     * @param string $_FILE_PATH The path to the CSV file.
     * @param bool $_CREATE_QUERY (Optional) Whether to include CREATE TABLE query in generated SQL. Default is FALSE.
     * @param string $_TABLE_NAME (Optional) The name of the table. Default is an empty string.
     */
    public function __construct(string $_FILE_PATH, bool $_CREATE_QUERY = false, string $_TABLE_NAME = "")
    {
        // Initialize properties
        $this->_FILE_PATH = $_FILE_PATH;
        $this->_CREATE_QUERY = $_CREATE_QUERY;
        $this->_TABLE_NAME = $_TABLE_NAME;
        $this->_RESPONSE = new \stdClass();
    }

    /**
     * Generates an SQL query based on the CSV data.
     *
     * This method extracts data from the CSV file, validates the extracted data, and generates an SQL query
     * for insertion into a database table. It returns an object containing information about the query generation
     * process, including the type of response (success or error), any relevant messages, and the generated SQL query
     * if successful.
     *
     * @return object An object representing the response of the query generation process. It contains the following properties:
     *                - type: The type of response, indicating whether the query generation was successful or resulted in an error.
     *                - message: A message providing information about the result of the query generation process.
     *                - query: The generated SQL query, if the generation process was successful.
     */
    public function generate(): object
    {
        // Extract CSV data from file path
        if (!$this->extractCSVData()) {
            return $this->_RESPONSE;
        }

        // Check if columns and rows are empty
        if (empty($this->_COLUMNS)) {
            $this->setResponse('error', 'No columns found from CSV');
        } elseif (empty($this->_ROWS)) {
            $this->setResponse('error', 'No row data found to generate query');
        } else {
            // Generate query if columns and rows are available
            $query = $this->generateQuery();
            $this->setResponse('success', 'Query generated', $query);
        }

        return $this->_RESPONSE;
    }


    /**
     * Generates the final SQL query for inserting data.
     *
     * @return string The generated SQL query.
     */
    private function generateQuery(): string
    {
        // Prepare columns and set table name
        $this->prepareColumns();
        $this->setTableName();

        // Generate data query
        $query = $this->generateDataQuery();

        // Include CREATE TABLE query if needed
        if ($this->_CREATE_QUERY) {
            $createQuery = $this->generateCreateQuery();
            $query = $createQuery . PHP_EOL . PHP_EOL . $query;
        }

        return $query;
    }

    /**
     * Generates the CREATE TABLE SQL query based on the table name and columns.
     *
     * @return string The generated CREATE TABLE SQL query.
     */
    private function generateCreateQuery(): string
    {
        $tableName = $this->_TABLE_NAME;
        $columns = $this->_COLUMNS;

        // Initialize the query with table name and opening parentheses
        $query = "CREATE TABLE IF NOT EXISTS `$tableName` (" . PHP_EOL;

        // Iterate through columns to define their types
        foreach ($columns as $column) {
            // For 'id' column, set as auto-incrementing integer
            if ($column === 'id') {
                $query .= " `id` int(11) NOT NULL AUTO_INCREMENT, " . PHP_EOL;
                continue;
            }

            // For columns containing 'date', set as DATETIME; otherwise, set as VARCHAR
            $columnType = str_contains($column, 'date') ? 'DATETIME' : 'VARCHAR(255)';
            $query .= " `$column` $columnType DEFAULT NULL, " . PHP_EOL;
        }

        // Define primary key
        $query .= " PRIMARY KEY (`id`)" . PHP_EOL . ");";

        return $query;
    }

    /**
     * Generates the SQL queries for inserting data into the table.
     *
     * @return string The generated SQL queries for inserting data.
     */
    private function generateDataQuery(): string
    {
        $columns = $this->_COLUMNS;
        $rows = $this->_ROWS;
        $tableName = $this->_TABLE_NAME;

        // Create a string of column names
        $columnNameString = implode(', ', array_map(function ($column) {
            return "`$column`";
        }, $columns));

        $insertQueries = [];

        // Generate INSERT queries for each row of data
        foreach ($rows as $row) {
            $values = [];

            if (!$this->_HAS_ID_COLUMN) {
                $values[] = "NULL";
            }

            // Escape each value to prevent SQL injection
            foreach ($row as $value) {
                $values[] = "'" . addslashes($value) . "'";
            }

            // Create the VALUES string for the current row
            $insertValues = '(' . implode(', ', $values) . ')';

            // Construct the INSERT query
            $insertQueries[] = "INSERT INTO `$tableName` ($columnNameString) VALUES $insertValues;";
        }

        return implode(PHP_EOL, $insertQueries);
    }

    /**
     * Sets the table name based on the file path if it's not sent from user,
     * and replaces spaces with underscores in the table name.
     */
    private function setTableName(): void
    {
        if ($this->_TABLE_NAME == "") {
            $this->_TABLE_NAME = pathinfo($this->_FILE_PATH, PATHINFO_FILENAME);
        }

        $this->_TABLE_NAME = str_replace(" ", '_', $this->_TABLE_NAME);
    }

    /**
     * Prepares column names by replacing spaces with underscores and ensures 'id' column is present.
     */
    private function prepareColumns(): void
    {
        $columns = [];

        // Replace spaces with underscores in column names
        foreach ($this->_COLUMNS as $column) {
            $columns[] = str_replace(" ", '_', $column);
        }

        // Add 'id' column if not already present
        if (!in_array('id', $columns)) {
            $this->_HAS_ID_COLUMN = false;
            array_unshift($columns, 'id');
        }

        // Update the columns property
        $this->_COLUMNS = $columns;
    }

    /**
     * Extracts column names and data rows from the CSV file.
     *
     * @return bool True if extraction is successful, false otherwise.
     */
    private function extractCSVData(): bool
    {
        // Validate the file path
        if (!$this->validateFile()) {
            return false;
        }

        // Open the CSV file
        $handle = fopen($this->_FILE_PATH, 'r');

        $skipFirstRow = true;
        while (($data = fgetcsv($handle)) !== false) {
            // Skip the first row (column headers)
            if ($skipFirstRow) {
                $this->_COLUMNS = $data;
                $skipFirstRow = false;
                continue;
            }

            // Store the data rows
            $this->_ROWS[] = $data;
        }

        // Close the file handle
        fclose($handle);

        return true;
    }

    /**
     * Validates the data source (CSV file path).
     *
     * @return bool True if the file is valid, false otherwise.
     */
    private function validateFile(): bool
    {
        try {
            if (!file_exists($this->_FILE_PATH) || !is_file($this->_FILE_PATH)) {
                $this->setResponse('error', 'Invalid file path');
                return false;
            }

            $extension = pathinfo($this->_FILE_PATH, PATHINFO_EXTENSION);
            if (strtolower($extension) !== 'csv') {
                $this->setResponse('error', 'Only CSV files are allowed');
                return false;
            }

            $file = fopen($this->_FILE_PATH, 'r');
            $bytes = fread($file, 4);
            fclose($file);

            if (empty($bytes)) {
                $this->setResponse('error', 'Invalid CSV file');
                return false;
            }
        } catch (\Exception $e) {
            $this->setResponse('error', $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Sets the response properties.
     *
     * @param string $type The response type.
     * @param string $message The response message.
     * @param string $query (Optional) The generated Query.
     *
     * @return void
     */
    private function setResponse(string $type, string $message, string $query = ""): void
    {
        $this->_RESPONSE->type = $type;
        $this->_RESPONSE->message = $message;

        if ($query != "") {
            $this->_RESPONSE->query = $query;
        }
    }

}
