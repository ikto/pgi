# PgI: Simple PostgreSQL OOP interface

## Short description

This library is inspired by [Perl DBI](http://search.cpan.org/~timb/DBI-1.641/) module.
It's database dependent (tied to PostgreSQL), but still tries to be simple.
As simple as possible.

It's may be a good choice if you don't want to use ORM or similar thing for some reason.
With this library you don't need to interact with PHP API directly.
It provides exceptions for errors, what makes possible to write code more cleanly rather than direct interaction with PHP API.

But be careful. It won't stop you if you wan't to shoot your leg :).

## Features

 - Exceptions for database-level errors.
 - Nested transactions mechanism (using savepoints).
 - Automatic bidirectional data conversion between db and php. For example _timestamp with time zone_ is represented as _DateTime_.

## Requirements (environment)

 - PHP 7.0 or higher
 - **pgsql** extension
 
## How to use

Here is a couple of examples.

```php

use IKTO/PgI;

// Connecting to the database.
$dbh = PgI::connect('host=127.0.0.1 port=5432 dbname=pgi_test', 'postgres', 'postgres');

// Inserting a row into database table.
if (!$dbh->doQuery('INSERT INTO "message" (name, data) VALUES ($1, $2)', [], ['Welcome!', 'Hello, this is a test!'])) {
    throw new \RuntimeException('Something went wrong');
}

// Updating rows in db.
$count = $dbh->doQuery('UPDATE "record" SET "published" = $1 WHERE "published" = $2 AND "date" < $3', [], [false, true, DateTime::createFromFormat('Y-m-d', '2013-11-21')]);
echo sprintf("We've unpublished %d records", $count);

// Deleting records from db.
$count = $dbh->doQuery('DELETE FROM "record" WHERE "published" = $1', [], [false]);
echo sprintf("We've removed %d unpublished records", $count);

// Selecting the latest record as associative array.
$record = $dbh->selectRowAssoc('SELECT * FROM "record" WHERE "published" = $1 ORDER BY "date" DESC LIMIT 1', [], [true]);

// Selecting the array of available record IDs.
$ids = $dbh->selectColArray('SELECT "id" FROM "record" ORDER BY "id" ASC');

// Getting the next sequence value.
$id = $dbh->getSeqNextValue('record_id_seq');

// Using transactions.
try {
    $dbh->beginWork();
    
    $id = $dbh->getSeqNextValue('record_id_seq');
    
    $dbh->doQuery('INSERT INTO "record" (id, date, published) VALUES ($1, NOW(), $2)', [], [$id, false]);
    
    $dbh->doQuery('INSERT INTO "message" (id_record, name, data) VALUES ($1, $2, $3)', [], [$id, 'Hello', 'This is a test']);
    
    $dbh->doQuery('UPDATE "record" SET "published" = $1 WHERE "id" = $2', [], [true, $id]);
    
    $dbh->commit();
} catch (\Exception $e) {
    $dbh->rollback();
}

```

To be continued...
