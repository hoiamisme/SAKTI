<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=sakti_db', 'root', '');
$stmt = $pdo->query('DESCRIBE access_logs');
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . ' | ' . $row['Type'] . PHP_EOL;
}
