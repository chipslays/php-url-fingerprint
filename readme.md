# 🔗 Pathor

## Overview

Pathor is a PHP library for normalizing, analyzing, and comparing URLs. It is built on top of the [League\Uri](https://uri.thephpleague.com/) library and offers an easy-to-use API for common URL-related operations.

## Installation

Install the library via Composer:

```bash
composer require pathor/url
```

## Features

- Normalize URLs by standardizing components (scheme, host, path, query, etc.).
- Generate a consistent fingerprint (hash) for URLs.
- Compare multiple URLs to check if they are equivalent.
- Parse URLs into their individual components.
- Assemble URLs from their components.
- Customize normalization with handlers and configurations.

## Usage

### Basic Usage

Here is a quick example of how to use the Pathor library:

```php
use Pathor\Url;

$pathor = new Url;

$url = 'https://www.example.com/path///../a/b/../c//ё//hello world/?ref=google&b=2&a=1&&=&&foo[1]=222&foo[0]=111#hello world';

// Normalize URL
$normalizedUrl = $pathor->normalize($url);
dd($normalizedUrl); // https://www.example.com/path/a/c/%D1%91/hello%20world?a=1&b=2&foo%5B%5D=111&foo%5B%5D=222#hello%20world

// Generate fingerprint
$fingerprint = $pathor->fingerprint($url);
dd($fingerprint); // b18e86f5d2da88269fd0895af1178d8305ae78fe3fa3e61195af6b50a60f333d

// Compare URLs
$isEqual = $pathor->equals(
    'https://www.example.com/path/a/c/%D1%91/hello%20world?a=1&b=2&foo%5B%5D=111&foo%5B%5D=222#hello%20world',
    'https://www.example.com/path///../a/b/../c//ё//hello world/?ref=google&b=2&a=1&&=&&foo[1]=222&foo[0]=111#hello world',
    'https://www.example.com/path//a/b/../c//ё//hello world/?ref=google&b=2&a=1&&=&&&foo[]=111&foo[]=222#hello world',
);
dd($isEqual); // Outputs: bool(true)

// Get URL details
$details = $pathor->details($url);
dd($details); // Outputs an array with parsed and normalized components
```

### Examples

Examples can be found [here](./examples).

### Configuration

The `Url` class can be customized with configuration options to adjust the normalization behavior. These options include:

- **`fingerprint`**: Set the hashing algorithm for URL fingerprints (default: `sha256`).
- **`query`**: Customize query string handling.
  - `withoutDuplicates`: Remove duplicate query parameters.
  - `withoutEmptyPairs`: Remove empty query parameters.
  - `withSortedParams`: Sort query parameters alphabetically.
  - `withoutTrackingParams`: Remove known tracking parameters (e.g., `utm_source`).
- **`path`**: Customize path normalization.
  - `withoutDotSegments`: Remove `.` and `..` segments in the path.
  - `withoutEmptySegments`: Remove empty segments from the path.
  - `withoutTrailingSlash`: Remove trailing slashes.

#### Default Configuration

```php
$config = [
    'fingerprint' => 'sha256', // https://www.php.net/manual/en/function.hash-algos.php

    'query' => [
        'withoutDuplicates' => true,
        'withoutEmptyPairs' => true,
        'withoutNumericIndices' => true,
        'withSortedParams' => true,
        'withoutTrackingParams' => true,
        'trackingParamsList' => static::QUERY_TRACKING_PARAMS,
    ],

    'path' => [
        'withoutDotSegments' => true,
        'withoutEmptySegments' => true,
        'withoutTrailingSlash' => true,
    ],
];

$pathor = new Url($config);
```

### Handlers (Custom normalization)

Custom handlers allow you to define specific rules for processing URL components. Handlers are functions that take the original and normalized values as parameters.

Example:

```php
$handlers = [
    'scheme' => fn(?string $normalized, ?string $original): ?string => $normalized,
    'user' => fn(?string $normalized, ?string $original): ?string => $normalized,
    'password' => fn(?string $normalized, ?string $original): ?string => $normalized,
    'host' => fn(?string $normalized, ?string $original): ?string => strtoupper($original),
    'port' => fn(?int $normalized, ?int $original): ?int => $normalized,
    'path' => fn(?string $normalized, ?string $original): ?string => $normalized,
    'query' => fn(?string $normalized, ?string $original): ?string => $normalized,
    'fragment' => fn(?string $normalized, ?string $original): ?string => $normalized,
];

$pathor = new Url(handlers: $handlers);
```

### Documentation

#### `normalize(string $url): string`

Normalizes a given URL by standardizing its components. By default, this includes:
- Lowercasing the scheme and host.
- Remove duplicate query parameters.
- Remove empty query parameters.
- Sort query parameters alphabetically.
- Remove known tracking parameters (e.g., `utm_source`).
- Remove `.` and `..` segments in the path.
- Remove empty segments from the path.
- Remove trailing slashes.
- And more.

Example:

```php
$normalized = $pathor->normalize('HTTP://Example.COM/../a/B/./');
echo $normalized; // Outputs: http://example.com/a/B

$normalized = $pathor->normalize('https://сайт.рф');
echo $normalized; // Outputs: https://xn--80aswg.xn--p1ai
```

#### `fingerprint(string $url): string`

Generates a hash based on the normalized URL. The hashing algorithm can be configured.

Example:

```php
$fingerprint = $pathor->fingerprint('https://example.com/path?param=value');

echo $fingerprint; // Outputs a hash string (e.g., SHA256)
```

#### `equals(string ...$urls): bool`

Compares two or more URLs to check if they are equivalent after normalization. Throws an exception if less than two URLs are provided.

Example:

```php
$areEqual = $pathor->equals(
    'https://example.com/?utm_source=google',
    'https://example.com:443?ref=site&=',
    'https://example.com:443/',
    'https://example.com:443/?#',
    'https://example.com:443'
);
var_dump($areEqual); // Outputs: bool(true)
```

#### `parse(string $url): array`

Breaks a URL into its components, returning an associative array.

Example:

```php
$components = $pathor->parse('https://user:pass@example.com:8080/path?query=value#fragment');

dd($components);

// ^ array:8 [
//   "scheme" => "https"
//   "host" => "example.com"
//   "user" => "user"
//   "password" => "pass"
//   "port" => 8080
//   "path" => "/path"
//   "query" => "query=value"
//   "fragment" => "fragment"
// ]
```

#### `build(array $components): string`

Assembles a URL from its components. Accepts an associative array with keys like `scheme`, `host`, `path`, etc.

Example:

```php
$url = $pathor->build([
    'scheme' => 'https',
    'host' => 'example.com',
    'path' => 'new-path',
    'query' => ['param' => 'value'], // or string (http_build_query)
    'fragment' => 'section'
]);

echo $url; // Outputs: https://example.com/new-path?param=value#section
```

#### `details(string $url): array`

Returns a detailed breakdown of a normalized URL, including original and modified components.

Example:

```php
$details = $pathor->details('https://www.example.com:443/path///../a/b/../c//ё//hello world/?ref=google&b=2&a=1&&=&&foo[1]=222&foo[0]=111#hello world');

dd($details);

// ^ array:4 [
//   "fingerprint" => "4c64095f06900806842e22f93ee151ab"
//   "original_url" => "https://www.example.com:443/path///../a/b/../c//ё//hello world/?ref=google&b=2&a=1&&=&&foo[1]=222&foo[0]=111#hello world"
//   "normalized_url" => "https://www.example.com/path/a/c/%D1%91/hello%20world?a=1&b=2&foo%5B%5D=111&foo%5B%5D=222#hello%20world"
//   "parsed_url" => array:8 [
//     "scheme" => "https"
//     "host" => "www.example.com"
//     "user" => null
//     "password" => null
//     "port" => null
//     "path" => "/path/a/c/%D1%91/hello%20world"
//     "query" => "a=1&b=2&foo%5B%5D=111&foo%5B%5D=222"
//     "fragment" => "hello%20world"
//   ]
// ]
```

## Contributing

Contributions are welcome! Please submit pull requests or open issues.

## License

This library is licensed under the MIT License. See the [LICENSE](./license) file for details.
