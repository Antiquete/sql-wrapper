<!-- @format -->

<!--
 Copyright (c) 2020 Hari Saksena <hari.mail@protonmail.ch>

 This software is released under the MIT License.
 https://opensource.org/licenses/MIT
-->

# SQL Wrapper

A simple wrapper for MySQL Database Connection.

## Installation

```bash
composer require antiquete/sql-wrapper
```

## Usage

- [Initialize](#initialize)
- [Select](#select)
- [Joins](#joins)
- [Insert](#insert)
- [Update](#update)
- [Delete](#delete)
- [Transactions](#transactions)
- [Settings](#settings)
- [Logs](#logs)
- [Misc](#misc)

### Initialize

```php
namespace Antiquete\SQLWrapper;
use Database;

$mydb = new Database($server, $dbuser, $dbpass, $dbname);
```

### Select

#### Get whole table

```php
$results = $mydb->select("table");
```

#### Get a row

```php
$row = $mydb->getRow("table", [
  "column" => "value"   // WHERE `column` == 'value'
]);
```

#### Get a row by id

```php
$row = $mydb->getRowById("table", "id");
```

#### Get an entry

```php
$val = $mydb->getVal("table",
                     [
                       "column" => "value"   // WHERE `column` == 'value'
                     ],
                     "column_name"  // column to get value of
                     );
```

### Joins

```php
/**
 * Returns a sql result array with for joined tables
 *
 * @param string $table1
 * @param string $table2
 * @param array $ons - List of all clauses within ON in "column" => "column" format
 * @param array $wheres = [] - List of al clauses within WHERE in "column" => "value" format, defaults to no condition
 * @param string $orderBy = "" - List of all ORDER BY in "column1, column2...." format, defaults to no order
 * @param boolean $orderAsc = TRUE - Whether to order in ascending format, defaults to true
 * @param string $extraConditions = "" - Any extra condition to apply on query in string format, defaults to nothing
 * @param string $joinType = "INNER JOIN" - Type of join to use in string format, defaults to INNER JOIN
 * @return void
 */
function selectJoin2($table1, $table2, $ons, $wheres = [], $orderBy = "", $orderAsc = TRUE, $extraConditions = "", $joinType = "INNER JOIN")
```

#### Get joined tables

```php
$result = $mydb->selectJoin2("table1", "table2",
                             [
                               "id1" => "id2"   // ON table1.id1 = table2.id2
                             ]);
```

#### Get only rows that match a condition from joined tables

```php
$result = $mydb->selectJoin2("table1", "table2",
                             [
                               "id1" => "id2"   // ON table1.id1 = table2.id2
                             ],
                             [
                               "column" => "value"  // WHERE `column` = 'value'
                             ])
```

#### Get only rows that match a condition from joined tables and are ordered in descending order

```php
$result = $mydb->selectJoin2("table1", "table2",
                             [
                               "id1" => "id2"   // ON table1.id1 = table2.id2
                             ],
                             [
                               "column" => "value"  // WHERE `column` = 'value'
                             ],
                             $orderBy = "column",
                             $orderAsc = FALSE)
```

#### Get only rows that match a condition from joined tables, are ordered in descending order and are Right Joined

```php
$result = $mydb->selectJoin2("table1", "table2",
                             [
                               "id1" => "id2"   // ON table1.id1 = table2.id2
                             ],
                             [
                               "column" => "value"  // WHERE `column` = 'value'
                             ],
                             $orderBy = "column",
                             $orderAsc = FALSE,
                             $extraConditions = "",
                             $joinType = "RIGHT JOIN")
```

### Insert

```php
$success = $mydb->insert("table",
                         [
                           "column1" => "value1",
                           "column2" => "value2",
                           "column3" => "value3"
                         ]);
```

#### Get last insert id

```php
$last_insert_id = $mydb->insert_id();
```

### Update

```php
$success = $mydb->update("table",
                         [
                           "column1" => "value1",   // SET `column` = 'value'
                           "column2" => "value2"
                         ],
                         [
                           "column" => "value"      // WHERE `column` = 'value'
                         ]);
```

### Delete

```php
$success = $mydb->delete("table",
                         [
                           "column" => "value",     // DELETE FROM `table` WHERE `column` = 'value'
                         ]);
```

### Transactions

#### Start a transaction

```php
$mydb->startTransaction();  // Any command executed after this will be part of transaction.
```

#### Commit transaction

```php
$mydb->commit();
```

#### Rollback transaction

```php
$mydb->rollback();
```

#### Atomically perform three insertions

```php
try
{
  $mydb->startTransaction();

  if(!$mydb->insert("cars" ["id"=>1, "brand_id"=>5, "series_id"=>2, "name"=>"Veyron"]))
    throw new Exception('Insertion failed.');

  if(!$mydb->insert("series" ["id"=>2, "name"=>"Bugatti"]))
    throw new Exception('Insertion failed.');

  if(!$mydb->insert("brands" ["id"=>5, "name"=>"Volkswagen Group"]))
    throw new Exception('Insertion failed.');

  $mydb->commit();
}
catch (Exception $e)
{
  $mydb->rollback();
}
```

### Settings

#### Requirement - Settings Table

```sql
CREATE TABLE `settings` (
 `skey` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
 `sval` text COLLATE utf8mb4_unicode_ci NOT NULL,
 PRIMARY KEY (`skey`)
);
```

#### Get a setting

```php
$mydb->getSetting("setting_key");
```

### Logs

#### Requirement - Logs Table

```sql
CREATE TABLE `logs` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`title` text NOT NULL,
`content` text NOT NULL,
`log_time` datetime NOT NULL,
PRIMARY KEY (`id`)
);
```

#### Log a message

```php
$mydb->log("Access Denied.", "Reason: Go away!");
```

### Misc

#### MySql real escape a string

```php
$escaped_str = $mydb->real_escape($str);
```

#### Get server time in mysql friendly format

```php
$now = $mydb->phptime();
```

#### Execute arbitary sql query

NOTE: This function is unescaped, ie. any user provided string should first be escaped using real_esacpe() before passing through this function to avoid sql injection.
In general direct usage of this function is discouraged. Much better alternative is to implement a function for select task that takes care of proper escaping and send me a pull request to merge it into main library.

```php
$result = $mydb->execute("SELECT count(*) FROM `table`");
```

## Contributions

Any contributions or suggestions are welcome. If you are encountering a bug or need a new feature open an issue on git repository. If you have already implemented a fix or a new feature and want it merged, send me a pull request. Thanks.

## License

This software is released under the MIT License.
https://opensource.org/licenses/MIT
