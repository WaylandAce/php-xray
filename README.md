[![Build Status](https://img.shields.io/travis/waylandace/php-xray.svg?style=flat-square)](https://travis-ci.com/waylandace/php-xray)
[![PHP 7.0](https://img.shields.io/badge/php-7.0-blue.svg?style=flat-square)](http://php.net/)  
[![Packagist](https://img.shields.io/packagist/v/waylandace/xray.svg?style=flat-square)](https://packagist.org/packages/waylandace/xray)


# waylandace\xray
A basic PHP instrumentation library for AWS X-Ray. Fork of https://github.com/patrickkerrigan/php-xray

Until Amazon releases an official PHP SDK for AWS X-Ray this library allows you to add basic instrumentation to PHP applications and report traces via the AWS X-Ray daemon.

Please note that no automatic instrumentation of popular libraries is provided. In order to instrument SQL queries, HTTP requests and/or other services you'll be required to create your own wrappers which start and end tracing segments as appropriate.

## Installation

The recommended way to install this library is using Composer:

```bash
$ composer require waylandace/xray ^1.2
```

## Usage

### Starting a trace

The ```Trace``` class represents the top-level of an AWS X-Ray trace, and can function as a singleton for easy access from anywhere in your code, including before frameworks and dependency injectors have been initialised.

You should start a trace as early as possible in your request:

```php
use Pkerrigan\Xray\Trace;

Trace::getInstance()
    ->setTraceHeader($_SERVER['HTTP_X_AMZN_TRACE_ID'] ?? null)
    ->setName('app.example.com')
    ->setUrl($_SERVER['REQUEST_URI'])
    ->setMethod($_SERVER['REQUEST_METHOD'])
    ->begin(); 
```

### Adding a segment to a trace

You can add as many segments to your trace as necessary, including nested segments. To add an SQL query to your trace, you'd do the following:

```php
Trace::getInstance()
    ->getCurrentSegment()
    ->addSubsegment(
        (new SqlSegment())
            ->setName('db.example.com')
            ->setDatabaseType('PostgreSQL')
            ->setQuery($mySanitisedQuery)    // Make sure to remove sensitive data before passing in a query
            ->begin()    
    );
    
    
// Run your query here
    
Trace::getInstance()
    ->getCurrentSegment()
    ->end();
    
```

The ```getCurrentSegment()``` method will always return the most recently opened segment, allowing you to nest as deeply as necessary.

### Adding an exception to a trace

You can add as many exceptions to your trace as necessary. Nested exceptions will be flattened and included in the trace.
Adding an exception does _not_ set error/fault flag.

```php   
Trace::getInstance()
    ->getCurrentSegment()
    ->addException($exception)
    ->setError(true);
```

### Ending a trace

At the end of your request, you'll want to end and submit your trace. By default only submitting via the AWS X-Ray daemon is supported.

```php
Trace::getInstance()
    ->end()
    ->setResponseCode(http_response_code())
    ->submit(new DaemonSegmentSubmitter());
```
