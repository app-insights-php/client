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