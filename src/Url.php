<?php

declare(strict_types=1);

namespace Pathor;

use InvalidArgumentException;
use League\Uri\Components\Fragment;
use League\Uri\Components\HierarchicalPath;
use League\Uri\Components\Host;
use League\Uri\Components\Query;
use League\Uri\Components\Scheme;
use League\Uri\Uri;

class Url
{
    private const QUERY_TRACKING_PARAMS = [
        // Общие аналитические параметры
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'utm_cpc', 'utm_device', 'utm_placement', 'utm_network',

        // Параметры социальных сетей и рекламных систем
        'gclid', 'fbclid', 'twclid', 'msclkid', 'dclid',
        'yclid', 'wickedid', 'mtm_source', 'mtm_medium',

        // Парметры электронной почты и маркетинга
        'mc_cid', 'mc_eid', 'campaignid', 'adgroupid',
        'mailtrack', 'pk_campaign', 'pk_kwd',

        // Парметры affiliate и реферальные
        'ref', 'referrer', 'aff', 'affiliate', 'affiliate_id',

        // Параметры трекинга сессий
        '_ga', '_gl', '__hssc', '__hstc', 'hsCtaTracking',

        // Параметры рекламных платформ
        'ad_id', 'ad_name', 'adset_id', 'adset_name', 'campaign_id',

        // Параметры A/B тестирования
        'ab', 'experiment', 'variation', 'test_group',

        // Дополнительные параметры
        'cid', 'scid', 'sid', 'tap_a', 'tap_s', 'vgo_ee'
    ];

    protected array $config;

    protected array $handlers;

    /**
     * Конструктор.
     *
     * @param array $config Настройки нормализации.
     * @param array $handlers Пользовательские обработчики.
     */
    public function __construct(array $config = [], array $handlers = [])
    {
        $this->config = array_replace_recursive(
            $this->defaultConfig(),
            $config
        );

        $this->handlers = array_replace_recursive(
            $this->defaultHandlers(),
            $handlers
        );
    }

    /**
     * Нормализация URL.
     *
     * Разбирает и нормализует компоненты URL согласно конфигурации.
     *
     * @param string $url URL для нормализации.
     * @return string Нормализованный URL.
     */
    public function normalize(string $url): string
    {
        return $this->build(...$this->parse($url));
    }

    /**
     * Генерация fingerprint для URL.
     *
     * Нормализует URL и генерирует его хеш в соответствии с указанной алгоритмом.
     *
     * @param string $url URL для генерации fingerprint.
     * @return string Хеш (fingerprint) для URL.
     */
    public function fingerprint(string $url): string
    {
        $tempConfig = $this->config;

        $this->config['query'] = $this->defaultConfig()['query'];
        $this->config['query']['trackingParamsList'] = $tempConfig['query']['trackingParamsList'];
        $this->config['path'] = $this->defaultConfig()['path'];

        $normalizedUrl = $this->normalize($url);

        $this->config = $tempConfig;

        return hash($this->config['fingerprint'], $normalizedUrl);
    }

    public function details(string $url): array
    {
        $components = $this->parse($url);

        $result = [
            'fingerprint' => $this->fingerprint($url),
            'original_url' => $url,
            'normalized_url' => $this->build(...$components),
            'parsed_url' => $components,
        ];

        return $result;
    }

    /**
     * Сравнение нескольких URL.
     *
     * Метод принимает произвольное количество URL и сравнивает их нормализованные версии.
     *
     * @param string ...$urls Список URL для сравнения.
     * @return bool
     */
    public function equals(...$urls): bool
    {
        // Если передано меньше двух URL, вернуть false
        if (count($urls) < 2) {
            throw new InvalidArgumentException('Необходимо передать хотя бы два URL для сравнения.');
        }

        // Получаем хеш первого URL и сравниваем его с хешами остальных URL
        $normalizedFingerprint = $this->fingerprint($urls[0]);

        return array_reduce($urls, fn($carry, $url) => $carry && $this->fingerprint($url) === $normalizedFingerprint, true);
    }

    public function parse(string $url): array
    {
        $url = Uri::new($url);

        $components = [
            'scheme' => $this->normalizeScheme($url->getScheme()),
            'host' => $this->normalizeHost($url->getHost()),
            'user' => $this->normalizeUser($url->getUsername()),
            'password' => $this->normalizePassword($url->getPassword()),
            'port' => $this->normalizePort($url->getPort()),
            'path' => $this->normalizePath($url->getPath()),
            'query' => $this->normalizeQuery($url->getQuery()),
            'fragment' => $this->normalizeFragment($url->getFragment()),
        ];

        return array_map(
            fn ($value) => is_string($value) && trim($value) === '' ? null : $value,
            $components
        );
    }

    /**
     * Сборка URL.
     *
     * @param string|null $scheme Схема.
     * @param string|null $host Хост.
     * @param string|null $user Имя пользователя.
     * @param string|null $password Пароль.
     * @param int|null $port Порт.
     * @param string|null $path Путь.
     * @param string|array|null $query Строка запроса.
     * @param string|null $fragment Фрагмент.
     * @return string Собранный URL
     */
    public function build(
        ?string $scheme = null,
        ?string $host = null,
        ?string $user = null,
        ?string $password = null,
        ?int $port = null,
        ?string $path = null,
        string|array|null $query = null,
        ?string $fragment = null
    ): string {
        $url = '';

        if ($scheme) {
            $url .= trim($scheme) . '://';
        }

        if ($user) {
            $url .= trim($user);

            if ($password) {
                $url .= ':' . trim($password);
            }

            $url .= '@';
        }

        if ($host) {
            $url .= trim($host);
        }

        if ($port !== null) {
            $url .= ':' . $port;
        }

        if ($path) {
            $url .= '/' . trim(ltrim($path, '/'));
        }

        if ($query) {
            $url .= '?' . (is_array($query) ? http_build_query($query) : trim($query));
        }

        if ($fragment) {
            $url .= '#' . trim($fragment);
        }

        return $url;
    }

    /**
     * Нормализация схемы.
     *
     * @param string|null $scheme Схема URL.
     * @return string|null Нормализованная схема.
     */
    protected function normalizeScheme(?string $scheme): ?string
    {
        $normalizedScheme = Scheme::new($scheme);

        return $this->executeHandler('scheme', (string) $normalizedScheme, $scheme);
    }

    /**
     * Нормализация хоста.
     *
     * @param string|null $host Хост URL.
     * @return string|null Нормализованный хост или null.
     */
    protected function normalizeHost(?string $host): ?string
    {
        if ($host === null) {
            return null;
        }

        $normalizedHost = Host::new($host);

        return $this->executeHandler('host', (string) $normalizedHost, $host);
    }

    /**
     * Нормализация пользователя.
     *
     * @param string|null $user Имя пользователя URL.
     * @return string|null Нормализованный пользователь.
     */
    protected function normalizeUser(?string $user): ?string
    {
        return $this->executeHandler('user', $user, $user);
    }

    /**
     * Нормализация пароля.
     *
     * @param string|null $password Пароль URL.
     * @return string|null Нормализованный пароль.
     */
    protected function normalizePassword(?string $password): ?string
    {
        return $this->executeHandler('password', $password, $password);
    }

    /**
     * Нормализация порта.
     *
     * @param int|null $port Порт URL.
     * @return int|null Нормализованный порт или null.
     */
    protected function normalizePort(?int $port): ?int
    {
        return $this->executeHandler('port', $port, $port);
    }

    /**
     * Нормализация пути.
     *
     * @param string|null $path Путь URL.
     * @return string|null Нормализованный путь.
     */
    protected function normalizePath(?string $path): ?string
    {
        if ($path === null) {
            return $this->executeHandler('path', null, null);
        }

        $normalizedPath = HierarchicalPath::new($path);

        if ($this->config['path']['withoutDotSegments']) {
            /** @var HierarchicalPath */
            $normalizedPath = $normalizedPath->withoutDotSegments();
        }

        if ($this->config['path']['withoutEmptySegments']) {
            /** @var HierarchicalPath */
            $normalizedPath = $normalizedPath->withoutEmptySegments();
        }

        if ($this->config['path']['withoutTrailingSlash']) {
            /** @var HierarchicalPath */
            $normalizedPath = $normalizedPath->withoutTrailingSlash();
        }

        return $this->executeHandler('path', (string) $normalizedPath, $path);
    }

    /**
     * Нормализация строки запроса.
     *
     * @param string|null $query Строка запроса URL.
     * @return string|null Нормализованная строка запроса.
     */
    protected function normalizeQuery(?string $query): ?string
    {
        $normalizedQuery = Query::new($query);

        if ($this->config['query']['withoutDuplicates']) {
            $normalizedQuery = $normalizedQuery->withoutDuplicates();
        }

        if ($this->config['query']['withoutEmptyPairs']) {
            $normalizedQuery = $normalizedQuery->withoutEmptyPairs();
        }

        if ($this->config['query']['withSortedParams']) {
            $normalizedQuery = $normalizedQuery->sort();
        }

        if ($this->config['query']['withoutNumericIndices']) {
            $normalizedQuery = $normalizedQuery->withoutNumericIndices();
        }

        if ($this->config['query']['withoutTrackingParams']) {
            $normalizedQuery = $normalizedQuery->withoutParameters(
                ...$this->config['query']['trackingParamsList']
            );
        }

        return $this->executeHandler('query', (string) $normalizedQuery, $query);
    }

    /**
     * Нормализация фрагмента.
     *
     * @param string|null $fragment Фрагмент URL.
     * @return string|null Нормализованный фрагмент.
     */
    protected function normalizeFragment(?string $fragment): ?string
    {
        $normalizedFragment = Fragment::new($fragment);

        return $this->executeHandler('fragment', $normalizedFragment->value(), $fragment);
    }

    /**
     * Выполнение пользовательского обработчика.
     *
     * @param string $handlerName Название обработчика.
     * @param mixed $normalizedValue Значение для обработки.
     * @return mixed Результат обработки.
     */
    protected function executeHandler(string $handlerName, mixed $normalizedValue, mixed $originalValue): mixed
    {
        if ($normalizedValue === null) {
            return null;
        }

        if (isset($this->handlers[$handlerName])) {
            $handler = $this->handlers[$handlerName];
            return is_callable($handler) ? $handler($normalizedValue, $originalValue) : $normalizedValue;
        }

        return $normalizedValue;
    }

    /**
     * Конфигурация по умолчанию.
     *
     * @return array Массив конфигурации по умолчанию.
     */
    protected function defaultConfig(): array
    {
        return [
             // Список всех алгоритмов: https://www.php.net/manual/en/function.hash-algos.php
            'fingerprint' => 'sha256',

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
    }

    /**
     * Обработчики по умолчанию.
     *
     * @return array Массив обработчиков по умолчанию.
     */
    protected function defaultHandlers(): array
    {
        return [
            'scheme' => null,
            'user' => null,
            'password' => null,
            'host' => null,
            'port' => null,
            'path' => null,
            'query' => null,
            'fragment' => null,
        ];
    }
}
