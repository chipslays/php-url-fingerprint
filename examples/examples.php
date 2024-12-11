<?php

use Pathor\Url;

require __DIR__ . '/../vendor/autoload.php';

// Создание экземпляра с базовой конфигурацией
$config = [
    'fingerprint' => 'md5',
    'query' => [
        'withoutDuplicates' => false,
        'withSortedParams' => false,
    ],
];

$handlers = [
    'host' => fn($value) => strtoupper($value), // Дополнительный обработчик для нормализации хоста
];

$url = new Url($config, $handlers);

// Пример 1: Нормализация URL
$rawUrl = "https://Example.com:80/path/to/resource?utm_source=google&utm_medium=cpc&gclid=123#fragment";
$normalizedUrl = $url->normalize($rawUrl);
echo "Normalized URL: $normalizedUrl\n";

// Пример 2: Генерация fingerprint
$fingerprint = $url->fingerprint($rawUrl);
echo "Fingerprint: $fingerprint\n";

// Пример 3: Получение деталей URL
$details = $url->details($rawUrl);
print_r($details);

// Пример 4: Сравнение нескольких URL
$url1 = "https://example.com/path/to/resource?utm_source=google";
$url2 = "https://example.com/path/to/resource";
$url3 = "https://example.com/path/to/resource?utm_medium=email";

$areEqual = $url->equals($url1, $url2, $url3);
echo "Are URLs equal? " . ($areEqual ? "Yes" : "No") . "\n";

// Пример 5: Разбор URL
$parsedComponents = $url->parse($rawUrl);
print_r($parsedComponents);

// Пример 6: Сборка URL
$builtUrl = $url->build(
    scheme: 'https',
    host: 'example.com',
    path: '/path/to/resource',
    query: ['param1' => 'value1', 'param2' => 'value2'],
    fragment: 'section1'
);
echo "Built URL: $builtUrl\n";

// Пример 8: Нормализация с добавлением обработчика для порта
$config['handlers']['port'] = fn($port) => $port === 1337 ? null : $port;
$urlWithPortHandler = new Url($config);
$normalizedWithPort = $urlWithPortHandler->normalize("http://example.com:80/path");
echo "Normalized URL with port handler: $normalizedWithPort\n";

// Пример 9: Создание URL с массивом параметров запроса
$builtUrlWithArrayQuery = $url->build(
    scheme: 'https',
    host: 'example.com',
    query: [
        'filter' => ['type' => 'image', 'size' => 'large'],
        'page' => 2
    ]
);
echo "Built URL with array query: $builtUrlWithArrayQuery\n";

// Пример 10: Генерация fingerprint с другим алгоритмом
$config['fingerprint']= 'sha1';
$urlWithSha1 = new Url($config);
$fingerprintSha1 = $urlWithSha1->fingerprint($rawUrl);
echo "Fingerprint with SHA1: $fingerprintSha1\n";

// Пример 11: Разбор сложного URL
$complexUrl = "https://user:pass@sub.example.com:8080/path/to/resource?param1=value1&param2=value2#section";
$parsedComplexComponents = $url->parse($complexUrl);
print_r($parsedComplexComponents);

// Пример 12: Сравнение URL с разными параметрами
$url4 = "https://example.com/path?param=value&test=123";
$url5 = "https://example.com/path?test=123&param=value";
$areEqualSorted = $url->equals($url4, $url5);
echo "Are URLs with sorted params equal? " . ($areEqualSorted ? "Yes" : "No") . "\n";

// Пример 13: Нормализация URL с удалением трекинговых параметров
$trackingUrl = "https://example.com?utm_source=google&utm_campaign=spring_sale&param=value";
$normalizedTrackingUrl = $url->normalize($trackingUrl);
echo "Normalized URL without tracking params: $normalizedTrackingUrl\n";

// Пример 14: Нормализация пути с удалением точечных сегментов
$dotPathUrl = "https://example.com/path/./to/../resource";
$normalizedDotPathUrl = $url->normalize($dotPathUrl);
echo "Normalized URL without dot segments: $normalizedDotPathUrl\n";

// Пример 15: Создание URL с пользовательским фрагментом
$customFragmentUrl = $url->build(
    scheme: 'https',
    host: 'example.com',
    fragment: 'custom-section'
);
echo "Built URL with custom fragment: $customFragmentUrl\n";
