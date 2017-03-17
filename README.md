# Simple PDO Wrapper

A simple PDO-wrapper for accessing MySQL and SQLite databases.

# Examples

Here are a few examples of how to use this wrapper. More comprehensive documentation is coming later (hopefully).

## Instantiate
```
$db = new \Database\Database();
```

## SQLite Connection

```
$db->driver('sqlite')
   ->dbPath('sqlite.db')
   ->connect();
```

## MySQL Connection

```
$db->driver('mysql')
   ->host('localhost')
   ->dbName('DB-NAME')
   ->username('USERNAME')
   ->password('PASSWORD')
   ->connect();
```

## SELECT

This example uses `bindValue` and returns an associative array of all the results.

```
$results = $db->select('`column1`', '`column2`')
   ->from('`table`')
   ->where("`column1` = :column1 AND `column2` = :column2")
   ->orderBy('`platform`')
   ->bind(['column1' => $column1Value, 'column2' => $column2Value])
   ->fetchAll();
```

## INSERT

```
$db->insert('`table`')
   ->columns('`column1`', '`column2`')
   ->values('value1', 'value2')
   ->execute();
```

## DELETE

```
$db->delete('`table`')
   ->where("`column` = 'value'")
   ->execute();
```

## UPDATE

```
$db->update('`table`')
   ->set("`column` = 'value'")
   ->where("`column2` = 'value2'")
   ->execute();
```

## REPLACE

`REPLACE` works exactly like `INSERT`, except that if an old row in the table has the same value as a new row for a `PRIMARY KEY` or a `UNIQUE` index, the old row is deleted before the new row is inserted.

```
$db->replace('`table`')
   ->columns('`column`', '`column2`')
   ->values('value1', 'value2')
   ->execute();
```

## LEFT JOIN

```
$results = $db->select(`table1`.`column`)
   ->from(`table1`)
   ->leftJoin(`table2`, `table3`)
   ->on(`table2`.`column` = `table1`.`column` AND `table3`.`column` = `table1`.`column`)
   ->fetchAll();
```

## TRUNCATE

```
$db->truncate('`table`');
```

## mysqldump

This method will dump the entire database into the specified file.

```
$db->mysqldump('path/to/dump/file.sql');
```

## Quick Reference

If you want a quick reference on the syntax of various SQL statements, use `sqlRef()`.

```
$db->sqlRef();
```

### Output

```
SELECT:
SELECT `column1`, `column2` FROM `table` WHERE `column1` = 'value' GROUP BY `column1` ORDER BY `column2` LIMIT 2

INSERT:
INSERT INTO `table` (`column1`, `column2`) VALUES (`value1`, `value2`) ON DUPLICATE KEY UPDATE `column1` = 'value'

REPLACE:
REPLACE INTO `table` (`column1`, `column2`) VALUES ('value1', 'value2')

DELETE:
DELETE FROM `table` WHERE `column` = 'value' ORDER BY `column` LIMIT 2

UPDATE:
UPDATE `table` SET `column1` = 'value1', `column2` = 'value2' WHERE `column1` = 'value1' ORDER BY `column2` LIMIT 2

LEFT JOIN:
SELECT `table`.`column` FROM `table1` LEFT JOIN (`table2`, `table3`) ON (`table2`.`column` = `table1`.`column` AND `table3`.`column` = `table1`.`column`)
```
