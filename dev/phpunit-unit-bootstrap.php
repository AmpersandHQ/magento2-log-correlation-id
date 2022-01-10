<?php
$vendorPath = realpath(__DIR__ . '/../vendor/');
file_put_contents(
    "$vendorPath/magento/magento2-base/app/autoload.php",
    "<?php return '$vendorPath';"
);
require_once "$vendorPath/magento/magento2-base/dev/tests/unit/framework/bootstrap.php";
