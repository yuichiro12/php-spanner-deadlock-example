<?php

require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Spanner\SpannerClient;

$spanner = new SpannerClient();

$db = $spanner->connect($_ENV["TEST_SPANNER_INSTANCE_ID"], );

//$userQuery = $db->execute('SELECT * FROM Users WHERE id = @id', [
//    'parameters' => [
//        'id' => $userId
//    ]
//]);

$user = $userQuery->rows()->current();

echo 'Hello ' . $user['firstName'];