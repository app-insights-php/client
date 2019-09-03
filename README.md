# Microsoft App Insights PHP telemetry client wrapper

This class wraps `microsoft/application-insights` package allowing to configure it according to the current needs. 

It was created to simplify `app-insights-php/app-insights-php-bundle` implementation.


### Usage 

```php
$client = new AppInsightsPHP\Client\Factory(
    'your_instrumentation_key', 
    AppInsightsPHP\Client\Configuration::createDefault()
);

$client->trackMessage(...);
$client->trackRequest(...);
// ... all methods available by source package
```

### Limitations

Size of the telemetry is limited to [64 kilobytes](https://docs.microsoft.com/en-us/azure/azure-monitor/service-limits#application-insights).
Before trying to send anything to AppInsights a telemetry is validated against that requirement. If it exceeds the limit
exception is thrown. To avoid that exception you should first check if the given data are not too big. You can
use `TelemetryData::exceededMaximumSize()` do it for you.
