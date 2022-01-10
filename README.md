# magento2-log-correlation-id

Magento 2 log correlation id for PHP requests/processes and magento logs.

This is useful when debugging issues on a production site as high amounts of traffic can cause many logs to be written and identifying which logs belong to a specific failing request can sometimes be difficult.

With this you should easily be able to find all associated logs for a given web request or CLI process.
- From a web request you can look at the `X-Log-Correlation-Id` header then search all your magento log files for the logs corresponding to only that request.
- You can also find the log correlation identifier attached to New Relic transactions.

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
