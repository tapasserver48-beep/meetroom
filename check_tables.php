<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    echo $t . PHP_EOL;
}
