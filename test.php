<?php

require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Spanner\SpannerClient;
use Google\Cloud\Spanner\KeySet;

/**
 * This is a sample code to reproduce spanner transaction deadlock.
 * Set environment variable `TEST_SPANNER_INSTANCE_ID` to designate your spanner instance.
 * Also `GOOGLE_APPLICATION_CREDENTIALS` is required for authentication.
 */

$spanner = new SpannerClient();

$db = $spanner->connect(getenv('TEST_SPANNER_INSTANCE_ID'), 'deadlock-test');
const DDL = <<<DDL
CREATE TABLE `TestTable` (
  `id` STRING(8) NOT NULL,
  `name` STRING(MAX)
) PRIMARY KEY (`id`)
DDL;

try {
    /** create database */
    $operation = $db->create(['statements' => [DDL]]);
    $operation->pollUntilComplete();

    /** insert record */
    $db->insert('TestTable', [
        'id' => 'einstein',
        'name' => 'もふちゃん',
    ]);
    $client1 = (new SpannerClient())->connect(getenv('TEST_SPANNER_INSTANCE_ID'), 'deadlock-test');
    $client2 = (new SpannerClient())->connect(getenv('TEST_SPANNER_INSTANCE_ID'), 'deadlock-test');

    /** start transaction */
    $transaction1 = $client1->transaction();
    $transaction2 = $client2->transaction();
    $keySet = new KeySet(['keys' => ['id' => 'einstein']]);
    /** shared lock */
    $res1 = $transaction1->read('TestTable', $keySet, ['name']);
    $res2 = $transaction2->read('TestTable', $keySet, ['name']);
    /** note that read() method is lazy */
    $res1->rows()->current();
    $res2->rows()->current();
    /** upgrade shared lock to exclusive lock */
    $transaction1->update('TestTable', ['id' => 'einstein', 'name' => 'もぷ1']);
    $transaction2->update('TestTable', ['id' => 'einstein', 'name' => 'もぷ2']);
    $time_start = microtime(true);
    $transaction1->commit();
    $transaction2->commit();
} finally {
    /** time while deadlock */
    $time = microtime(true) - $time_start;
    echo "time to detect deadlock: ".$time."sec\n";
    /** drop database */
    $db->drop();
}
