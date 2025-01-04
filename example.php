<?php

use LibSQLPDO\PDO;

require_once __DIR__ . '/vendor/autoload.php';

$db = new PDO(dsn: "database.db");
$db->exec('CREATE TABLE IF NOT EXISTS bar (name TEXT, baz TEXT, buz TEXT)');

$s = $db->prepare('INSERT INTO bar (name, baz, buz) VALUES (:name, :baz, :buz)');
$s->bindValue(':name', 'ini');
$s->bindValue(':baz', 'itu');
$s->bindValue(':buz', 'iti');
$s->execute();

$s = $db->prepare('INSERT INTO bar (name, baz, buz) VALUES (:name, :baz, :buz)');
$s->bindValue(':name', 'iku');
$s->bindValue(':baz', 'iki');
$s->bindValue(':buz', 'iko');
$s->execute();

$data = $db->prepare("SELECT * FROM bar WHERE name = :name");
$data->bindValue(':name', 'ini');

var_dump($data->fetch());

$db->close();
