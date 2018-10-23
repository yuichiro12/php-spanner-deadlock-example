# php-spanner-deadlock-example

## preliminary

Clone and composer install.
```
git clone https://github.com/yuichiro12/php-spanner-deadlock-example.git
cd php-spanner-deadlock-example
composer install
```

Set up environment variables `$GOOGLE_APPLICATION_CREDENTIALS` and `$TEST_SPANNER_INSTANCE_ID`.
```
export GOOGLE_APPLICATION_CREDENTIALS=/path/to/credential.json
export TEST_SPANNER_INSTANCE_ID=<SPANNER_INSTANCE_ID>
```

## reproduce deadlock
```
php test.php
```
