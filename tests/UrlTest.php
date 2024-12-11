<?php

use League\Uri\Exceptions\SyntaxError;
use Pathor\Url;

beforeEach(function () {
    $this->url = new Url();
});

it('can handle empty URL', function () {
    $url = '';

    $normalized = $this->url->normalize($url);

    // Проверяем, что нормализованный пустой URL тоже пуст
    expect($normalized)->toBe('');
});

it('can handle URL with only a scheme', function () {
    $url = 'https://';

    $this->url->normalize($url);
})->throws(SyntaxError::class);

it('can handle URL with empty path', function () {
    $url = 'https://example.com/';

    $normalized = $this->url->normalize($url);

    // Проверяем, что нормализованный URL не содержит лишний слэш
    expect($normalized)->toBe('https://example.com');
});

it('can handle URL with multiple query parameters', function () {
    $url = 'https://example.com/test?param1=value1&param2=value2';

    $normalized = $this->url->normalize($url);

    // Проверяем, что параметры запроса сохранены
    expect($normalized)->toBe('https://example.com/test?param1=value1&param2=value2');
});

it('can handle URL with trailing slash in path', function () {
    $url = 'https://example.com/test/';

    $normalized = $this->url->normalize($url);

    // Проверяем, что лишний слэш в конце пути удаляется
    expect($normalized)->toBe('https://example.com/test');
});

it('can handle URL with complex query string', function () {
    $url = 'https://example.com/test?param1=value1&param2=value2&param1=value1';

    $normalized = $this->url->normalize($url);

    // Проверяем, что нормализованный URL не содержит дублирующих параметров
    expect($normalized)->toBe('https://example.com/test?param1=value1&param2=value2');
});

it('can handle URL with user and password', function () {
    $url = 'http://user:password@example.com/test';

    $normalized = $this->url->normalize($url);

    // Проверяем, что нормализованный URL включает пользователя и пароль
    expect($normalized)->toBe('http://user:password@example.com/test');
});

it('can remove tracking parameters from query', function () {
    $url = 'https://example.com/test?utm_source=google&utm_medium=cpc&param=value';

    $normalized = $this->url->normalize($url);

    // Проверяем, что tracking параметры убраны из запроса
    expect($normalized)->toBe('https://example.com/test?param=value');
});

it('correctly parses URL with user info', function () {
    $url = 'https://user:pass@domain.com/path';

    $parsed = $this->url->parse($url);

    // Проверяем, что парсится пользователь и пароль
    expect($parsed['user'])->toBe('user');
    expect($parsed['password'])->toBe('pass');
    expect($parsed['host'])->toBe('domain.com');
    expect($parsed['path'])->toBe('/path');
});

it('handles invalid URL format gracefully', function () {
    $url = 'htp://invalid-url'; // некорректный формат схемы

    $parsed = $this->url->parse($url);

    expect($parsed['scheme'])->toBe('htp');
    expect($parsed['host'])->toBe('invalid-url');
});

it('correctly parses URL with port', function () {
    $url = 'https://example.com:8080/path';

    $parsed = $this->url->parse($url);

    // Проверяем, что порт корректно разобран
    expect($parsed['port'])->toBe(8080);
    expect($parsed['host'])->toBe('example.com');
    expect($parsed['path'])->toBe('/path');
});

it('correctly handles URL with empty query', function () {
    $url = 'https://example.com/test?';

    $parsed = $this->url->parse($url);

    // Проверяем, что пустой query корректно обрабатывается
    expect($parsed['query'])->toBeNull();
});

it('returns correct fingerprint for the same URL', function () {
    $url = 'https://example.com/test?utm_source=google&utm_medium=cpc';

    $fingerprint1 = $this->url->fingerprint($url);
    $fingerprint2 = $this->url->fingerprint($url);

    // Проверяем, что хеш для одинаковых URL одинаковый
    expect($fingerprint1)->toBe($fingerprint2);
});

it('can compare different URLs with different query parameters', function () {
    $url1 = 'https://example.com/test?param=value1';
    $url2 = 'https://example.com/test?param=value2';

    $result = $this->url->equals($url1, $url2);

    // Проверяем, что URL с разными параметрами считаются разными
    expect($result)->toBeFalse();
});

it('can compare different URLs with same structure but different paths', function () {
    $url1 = 'https://example.com/test';
    $url2 = 'https://example.com/other';

    $result = $this->url->equals($url1, $url2);

    // Проверяем, что URL с разными путями считаются разными
    expect($result)->toBeFalse();
});

it('throws an exception if one URL is provided for comparison', function () {
    $url1 = 'https://example.com/test';

    $this->url->equals($url1);
})->throws(InvalidArgumentException::class, 'Необходимо передать хотя бы два URL для сравнения.');

it('handles URL with fragment correctly', function () {
    $url = 'https://example.com/path#fragment';

    $parsed = $this->url->parse($url);

    // Проверяем, что фрагмент правильно разобран
    expect($parsed['fragment'])->toBe('fragment');
});

it('correctly handles relative URLs', function () {
    $url = '/path/to/resource';

    $parsed = $this->url->parse($url);

    // Проверяем, что относительный URL корректно разбирается
    expect($parsed['path'])->toBe('/path/to/resource');
});

it('correctly handles full URL with all components', function () {
    $url = 'https://user:password@host:8080/path/to/resource?param=value#fragment';

    $parsed = $this->url->parse($url);

    expect($parsed['scheme'])->toBe('https');
    expect($parsed['host'])->toBe('host');
    expect($parsed['port'])->toBe(8080);
    expect($parsed['path'])->toBe('/path/to/resource');
    expect($parsed['query'])->toBe('param=value');
    expect($parsed['fragment'])->toBe('fragment');
    expect($parsed['user'])->toBe('user');
    expect($parsed['password'])->toBe('password');
});
