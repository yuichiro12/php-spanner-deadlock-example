<?php

require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Spanner\SpannerClient;
use Google\Cloud\Spanner\KeySet;
use Google\Cloud\Core\Exception\AbortedException;

/**
 * This is a sample code to count AbortedException message kind.
 *
 * WARNING: THIS SCRIPT TAKES MORE THAN 100 MINUTES!!!!
 */

$spanner = new SpannerClient();

$db = $spanner->connect(getenv('TEST_SPANNER_INSTANCE_ID'), 'deadlock-test');
const DDL = <<<DDL
CREATE TABLE `TestTable` (
  `id` STRING(8) NOT NULL,
  `name` STRING(MAX)
) PRIMARY KEY (`id`)
DDL;

$exceptionMessagesCount = [[], []];

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

    for ($i = 0; $i < 100; $i++) {
        /** start transaction */
        $transaction1 = $client1->transaction();
        $transaction2 = $client2->transaction();
        $keySet = new KeySet(['keys' => ['id' => 'einstein']]);
        $res1 = $transaction1->read('TestTable', $keySet, ['name']);
        $res2 = $transaction2->read('TestTable', $keySet, ['name']);
        $res1->rows()->current();
        $res2->rows()->current();
        $transaction1->update('TestTable', ['id' => 'einstein', 'name' => 'もぷ1']);
        $transaction2->update('TestTable', ['id' => 'einstein', 'name' => 'もぷ2']);
        try {
            $transaction1->commit();
        } catch (AbortedException $e) {
            $message = $e->getMessage();
            if (isset($exceptionMessagesCount[0][$message])) {
                $exceptionMessagesCount[0][$message]++;
            } else {
                $exceptionMessagesCount[0][$message] = 1;
            }
        }
        try {
            $transaction2->commit();
        } catch (AbortedException $e) {
            $message = $e->getMessage();
            if (isset($exceptionMessagesCount[1][$message])) {
                $exceptionMessagesCount[1][$message]++;
            } else {
                $exceptionMessagesCount[1][$message] = 1;
            }
        }
    }
} finally {
    /** drop database */
    $db->drop();

    foreach ($exceptionMessagesCount as $i => $v) {
        echo "number: $i\n";
        foreach ($v as $message => $count) {
            $map = json_decode($message, true);
            $message = isset($map['message']) ? $map['message'] : $e->getMessage();
            echo "message: $message\n";
            echo "count: $count\n\n";
        }
    }
}
