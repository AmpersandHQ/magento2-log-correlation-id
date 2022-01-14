# magento2-log-correlation-id

[![Build Status](https://app.travis-ci.com/AmpersandHQ/magento2-log-correlation-id.svg?token=4DzjEueYNQwZuk3ywXjG&branch=main)](https://app.travis-ci.com/AmpersandHQ/magento2-log-correlation-id)

Magento 2 log correlation id for PHP requests/processes and magento logs.

This is useful when debugging issues on a production site as high amounts of traffic can cause many logs to be written and identifying which logs belong to a specific failing request can sometimes be difficult.

With this you should easily be able to find all associated logs for a given web request or CLI process.
- From a web request you can look at the `X-Log-Correlation-Id` header then search all your magento log files for the logs corresponding to only that request.
- You can also find the log correlation identifier attached to New Relic transactions.

## How it works

###  Entry point

This module creates a new Cache Decorator (`src/CacheDecorator/CorrelationIdDecorator.php`). It needs to be here so that it's constructed immediately after `Magento\Framework\Cache\Frontend\Decorator\Logger` which is the class responsible for instantiating `Magento\Framework\App\Request\Http` and the Logger triggered after. 

This is the earliest point in the magento stack where we can get any existing trace-id (for example `cf-request-id`) and have it attached to any logs produced.

### Exit points
- The correlation ID is attached to web requests as `X-Log-Correlation-Id` in `src/HttpResponse/HeaderProvider/LogCorrelationIdHeader.php`
    - REST API requests work a bit differently in Magento and use the following to attach the header `src/Plugin/AddToWebApiResponse.php`
- Monolog files have the correlation ID added into their context section under the key `amp_correlation_id` via `src/Processor/MonologCorrelationId.php`
- New Relic has this added as a custom parameter under the key `amp_correlation_id`

## Installation

Composer install the module.
```
composer require ampersand/magento2-log-correlation-id
```

Run module installation
```shell
php bin/magento setup:upgrade
```

This module does not work properly when magento is in developer mode. This is because the object manager is only constructed with the contents of `app/etc/*di.xml` rather than the full compiled configuration. Without the full di configuration at the time of constructing the object manager the custom cache decorator is not included and the correlation ID cannot be set for the request.

Run `php bin/magento di:compile` if you want to debug/test this module locally.

## Example usage

Firstly you need to expose the header in your logs, this is an example for apache logs

```apacheconf
Header always note X-Log-Correlation-Id amp_correlation_id
LogFormat "%t %U %{amp_correlation_id}n" examplelogformat
CustomLog "/path/to/var/log/httpd/access_log" examplelogformat
```

The above configuration would give log output like the following when viewing a page

```shell
[13/Jan/2021:11:34:37 +0000] /some-cms-page/ cid-61e006137ceec
```

You could then search for all magento logs pertaining to that request 

```shell
grep -r cid-61e00714d1920 ./var/log
```

If the request was long-running, or had an error it may also be flagged in new relic with the custom parameter `amp_correlation_id`
 
# Configuration and Customisation

## Use existing correlation id from request header

If you want to use an upstream correlation/trace ID you can define one di.xml

```xml
<type name="Ampersand\LogCorrelationId\Service\RetrieveCorrelationIdentifier">
    <arguments>
        <argument name="headerInput" xsi:type="string">X-Your-Header-Here</argument>
    </arguments>
</type>
```

If this is present on the request magento will use that value for `X-Log-Correlation-Id`, the monolog context, and the New Relic parameter. Otherwise magento will generate one.

# Change the key name from `amp_correlation_id`

You can change the monolog/new relic key from `amp_correlation_id` using di.xml

```xml
<type name="Ampersand\LogCorrelationId\Service\RetrieveCorrelationIdentifier">
    <arguments>
        <argument name="identifierKey" xsi:type="string">your_key_name_here</argument>
    </arguments>
</type>
```
