<?php

namespace App\Console\Commands;

use DateTimeImmutable;
use Dedoc\Scramble\Contracts\RouteProvider;
use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\OperationBuilder;
use Dotenv\Dotenv;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Builds the Postman v2.1 collection by app (Flutter, React website, React admin panel) from the docs in
 * docs/postman/ and the Scramble OpenAPI document (URLs, parameters and example bodies).
 */
#[Signature('postman:export
    {--docs=docs/postman : The folder with the Postman docs, relative to the project root}
    {--output= : The folder to write the files to, relative to the project root (default: the paths in collection.php)}
    {--env-example=.env.example : The env example file the placeholders are read from, relative to the project root}')]
#[Description('Export the API v1 Postman collection, organized by app, and the local environment')]
class ExportPostmanCollection extends Command
{
    private const string SCHEMA_URL = 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json';

    private const string V1_PREFIX = 'api/v1/';

    /**
     * Semantic Versioning 2.0.0 (semver.org), with optional pre-release and build parts.
     */
    private const string SEMVER_PATTERN = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

    private const string CHANGELOG_FOLDER = 'Changelog';

    /**
     * Every changelog line starts with one of these tags (see the header of docs/postman/changelog.php).
     */
    private const string CHANGELOG_TAG_PATTERN = '/^(Added|Changed|Breaking|Breaking \(before launch\)|Removed|Docs): \S/';

    /**
     * Template sections in order. All but "notes" are required for every request.
     */
    private const array SECTIONS = [
        'what' => 'What it does',
        'screen' => 'Screen',
        'who' => 'Who can call it',
        'request' => 'Request',
        'success' => 'Success',
        'errors' => 'Errors',
        'notes' => 'Notes',
    ];

    private const array OPTIONAL_SECTIONS = ['notes'];

    /**
     * The token request and where MobileTokenController puts the token in its response.
     */
    private const string TOKEN_ROUTE = 'api.v1.auth.token';

    private const string TOKEN_RESPONSE_FIELD = 'token';

    /**
     * Text placeholders: the .env.example key (null when the value is not env-backed, then the config/tayari.php
     * default is used) and the config/tayari.php key.
     */
    private const array PLACEHOLDERS = [
        ':otp_length' => ['OTP_LENGTH', 'otp.length'],
        ':otp_expires_minutes' => ['OTP_EXPIRES_MINUTES', 'otp.expires_minutes'],
        ':otp_max_attempts' => ['OTP_MAX_ATTEMPTS', 'otp.max_attempts'],
        ':otp_cooldown_seconds' => ['OTP_RESEND_COOLDOWN_SECONDS', 'otp.resend_cooldown_seconds'],
        ':otp_limit_per_phone' => ['OTP_RATE_LIMIT_PER_PHONE', 'otp.rate_limits.per_phone'],
        ':otp_limit_per_ip' => ['OTP_RATE_LIMIT_PER_IP', 'otp.rate_limits.per_ip'],
        ':otp_limit_minutes' => ['OTP_RATE_LIMIT_DECAY_MINUTES', 'otp.rate_limits.decay_minutes'],
        ':login_per_minute' => ['LOGIN_ATTEMPTS_PER_MINUTE', 'auth.login_attempts_per_minute'],
        ':login_per_phone_per_hour' => ['LOGIN_ATTEMPTS_PER_PHONE_PER_HOUR', 'auth.login_attempts_per_phone_per_hour'],
        ':max_upload_kb' => ['MEDIA_MAX_UPLOAD_KB', 'media.max_upload_kb'],
        ':signed_url_minutes' => ['MEDIA_SIGNED_URL_MINUTES', 'media.signed_url_minutes'],
        ':tayar_location_per_minute' => ['TAYAR_LOCATION_UPDATES_PER_MINUTE', 'tayars.location_updates_per_minute'],
        ':tayar_offline_minutes' => ['TAYAR_OFFLINE_AFTER_MINUTES', 'tayars.offline_after_minutes'],
        ':tayar_reapply_hours' => ['TAYAR_REAPPLY_AFTER_HOURS', 'tayars.reapply_after_hours'],
        ':zone_min_radius_km' => ['TAYAR_ZONE_MIN_RADIUS_KM', 'tayars.zone_min_radius_km'],
        ':zone_max_radius_km' => ['TAYAR_ZONE_MAX_RADIUS_KM', 'tayars.zone_max_radius_km'],
        ':order_zone_max_distance_km' => ['ORDER_ZONE_MAX_DISTANCE_KM', 'orders.zone_max_distance_km'],
        ':order_max_distance_km' => ['ORDER_MAX_DISTANCE_KM', 'orders.max_distance_km'],
        ':per_page' => [null, 'pagination.per_page'],
        ':max_per_page' => [null, 'pagination.max_per_page'],
    ];

    /**
     * Clearly fake example values by field name, the same on every run.
     */
    private const array FIELD_EXAMPLES = [
        'name' => 'Example User',
        'phone' => '+201000000000',
        'password' => 'example-password',
        'password_confirmation' => 'example-password',
        'current_password' => 'example-old-password',
        'code' => '123456',
        'device_name' => 'Example Phone',
        'push_token' => 'example-fcm-token',
        'app_version' => '1.0.0',
        'reason' => 'Example reason.',
        'ar' => 'مثال',
        'en' => 'Example',
        'business_name' => 'Example Store',
        'description' => 'Example description',
        'tax_registration_number' => '000-000-000',
        'url' => 'https://example.com',
        'business_type_id' => 1,
        'avg_daily_orders' => 40,
        'per_page' => 20,
        'national_id_number' => '29003030000000',
        'vehicle_type_id' => 1,
        'plate_number' => 'ABC 1234',
        'model' => 'Example Model',
        'color' => 'Red',
        'year' => 2020,
        'lat' => 30.0444,
        'lng' => 31.2357,
        'idempotency_key' => '00000000-0000-4000-8000-000000000000',
        'category_id' => 1,
        'details' => 'Example package details',
        'pickup_address' => 'Example pickup address',
        'pickup_lat' => 30.0444,
        'pickup_lng' => 31.2357,
        'pickup_contact_phone' => '+201000000000',
        'dropoff_address' => 'Example drop-off address',
        'dropoff_lat' => 30.0626,
        'dropoff_lng' => 31.2497,
        'receiver_name' => 'Example Receiver',
        'receiver_phone' => '+201000000000',
        'goods_value' => '150.00',
        'shipping_fee' => '40.00',
        'cancel_reason_id' => 1,
        'note' => 'Example note.',
    ];

    /**
     * @var array<string, mixed>
     */
    private array $spec = [];

    /**
     * @var list<string>
     */
    private array $problems = [];

    /**
     * The docs files by name (collection, endpoints and one per app).
     *
     * @var array<string, array<string, mixed>>
     */
    private array $docs = [];

    /**
     * @var array<string, string>
     */
    private array $placeholders = [];

    public function handle(): int
    {
        $this->problems = [];
        $this->docs = [];
        $this->placeholders = $this->placeholderValues();

        $generatorConfig = Scramble::getGeneratorConfig('default');
        $this->spec = (new Generator(app(OperationBuilder::class), app(RouteProvider::class)))
            ->generate($generatorConfig)
            ->spec($generatorConfig->openApiVersion());

        $latest = $this->checkChangelog();
        $usedRoutes = [];
        $appFolders = [];

        foreach ($this->docs('collection')['apps'] as $app) {
            $appFolders[] = $this->appFolder($this->docs($app), $usedRoutes);
        }

        $this->checkRouteCoverage($usedRoutes);

        $collection = [
            'info' => [
                '_postman_id' => $this->stableUuid('collection'),
                'name' => $this->docs('collection')['name'].' · docs '.($latest['version'] ?? ''),
                'description' => $this->text($this->docs('collection')['description']),
                'schema' => self::SCHEMA_URL,
            ],
            'auth' => ['type' => 'noauth'],
            'event' => [$this->script('prerequest', $this->headersScript([
                'Accept' => 'application/json',
                'Accept-Language' => '{{locale}}',
            ]))],
            'item' => [$this->readMeFolder($latest), $this->changelogFolder(), ...$appFolders],
        ];

        if ($this->problems !== []) {
            foreach ($this->problems as $problem) {
                $this->error($problem);
            }

            return self::FAILURE;
        }

        $collectionPath = $this->outputPath('collection');
        $environmentPath = $this->outputPath('environment');

        $this->writeJson($collectionPath, $collection);
        $this->writeJson($environmentPath, $this->environment());

        $this->info("Postman collection exported to {$collectionPath} and {$environmentPath}.");

        return self::SUCCESS;
    }

    /**
     * Loads a docs file with require, so the docs never become part of the app config.
     *
     * @return array<string, mixed>
     */
    private function docs(string $name): array
    {
        return $this->docs[$name] ??= require base_path($this->option('docs')."/{$name}.php");
    }

    /**
     * Placeholder values come from .env.example (then the config/tayari.php defaults), never from the running config,
     * so the output is the same on every machine.
     *
     * @return array<string, string>
     */
    private function placeholderValues(): array
    {
        $envExample = $this->option('env-example');
        $example = Dotenv::parse(File::get(base_path($envExample)));
        $defaults = require config_path('tayari.php');
        $values = [];

        foreach (self::PLACEHOLDERS as $placeholder => [$envKey, $configKey]) {
            if ($envKey !== null && blank($example[$envKey] ?? null)) {
                $this->problems[] = "Placeholder [{$placeholder}] needs {$envKey} in {$envExample}.";
            }

            $value = $envKey !== null && filled($example[$envKey] ?? null) ? $example[$envKey] : Arr::get($defaults, $configKey);
            $values[$placeholder] = (string) (int) $value;
        }

        $values[':max_upload_mb'] = (string) round((int) Arr::pull($values, ':max_upload_kb') / 1024, 1);

        return $values;
    }

    /**
     * Records a problem for an empty changelog, a version that is not semver, or versions not strictly newest first.
     *
     * @return array{version: string, date: string, changes: list<string>}|null the latest entry
     */
    private function checkChangelog(): ?array
    {
        $changelog = $this->docs('changelog');

        if ($changelog === []) {
            $this->problems[] = 'docs/postman/changelog.php has no entries.';

            return null;
        }

        $previous = null;

        foreach ($changelog as $entry) {
            $version = (string) ($entry['version'] ?? '');
            $date = (string) ($entry['date'] ?? '');
            $changes = $entry['changes'] ?? null;

            $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

            if ($parsedDate === false || $parsedDate->format('Y-m-d') !== $date) {
                $this->problems[] = "Changelog version [{$version}] has the date [{$date}]: use a real date as YYYY-MM-DD.";
            }

            if (! is_array($changes) || $changes === [] || ! array_is_list($changes) || collect($changes)->contains(fn (mixed $change): bool => ! is_string($change) || blank($change))) {
                $this->problems[] = "Changelog version [{$version}] needs changes: a non-empty list of change lines.";
            } else {
                foreach ($changes as $change) {
                    if (preg_match(self::CHANGELOG_TAG_PATTERN, $change) !== 1) {
                        $this->problems[] = "Changelog version [{$version}] has a line without a tag (Added:, Changed:, Breaking:, Breaking (before launch):, Removed: or Docs:): [".Str::limit($change, 60).'].';
                    }
                }
            }

            if (preg_match(self::SEMVER_PATTERN, $version) !== 1) {
                $this->problems[] = "Changelog version [{$version}] is not a valid semver version (for example 1.2.0).";

                continue;
            }

            if ($previous !== null) {
                // Build metadata (+...) has no effect on semver precedence.
                $comparison = version_compare(Str::before($version, '+'), Str::before($previous, '+'));

                if ($comparison === 0) {
                    $this->problems[] = "Changelog version [{$version}] is used by more than one entry.";
                } elseif ($comparison > 0) {
                    $this->problems[] = "Changelog version [{$version}] comes after the lower version [{$previous}]: list versions newest first.";
                }
            }

            $previous = $version;
        }

        return $changelog[0];
    }

    /**
     * @return array<string, mixed>
     */
    private function changelogFolder(): array
    {
        $sections = array_map(
            // checkChangelog() already reported malformed entries; this only has to not crash on them.
            fn (array $entry): string => '## '.($entry['version'] ?? '').' ('.($entry['date'] ?? '').")\n\n".collect(is_array($entry['changes'] ?? null) ? $entry['changes'] : [])
                ->filter(fn (mixed $change): bool => is_string($change))
                ->map(fn (string $change): string => "- {$change}")
                ->implode("\n"),
            $this->docs('changelog'),
        );

        return [
            'name' => self::CHANGELOG_FOLDER,
            'description' => $this->text(implode("\n\n", $sections)),
            'item' => [],
        ];
    }

    private function outputPath(string $file): string
    {
        $path = $this->docs('collection')['output'][$file];
        $directory = $this->option('output');

        return filled($directory) ? rtrim($directory, '/').'/'.basename($path) : $path;
    }

    /**
     * @param  array{version: string, date: string, changes: list<string>}|null  $latest
     * @return array<string, mixed>
     */
    private function readMeFolder(?array $latest): array
    {
        $notUsed = collect($this->docs('collection')['not_used_directly'])
            ->map(function (string $reason, string $routeName): string {
                $route = Route::getRoutes()->getByName($routeName);

                return '- `'.($route ? $this->method($route).' /'.$route->uri() : $routeName)."`: {$reason}";
            })
            ->sort()
            ->implode("\n");

        $description = $this->docs('collection')['read_me']['description'];

        if ($latest !== null) {
            $description = "Docs version {$latest['version']} ({$latest['date']})\n\n{$description}";
        }

        if ($notUsed !== '') {
            $description .= "\n\n## Endpoints no app calls directly\n\n{$notUsed}";
        }

        return [
            'name' => $this->docs('collection')['read_me']['name'],
            'description' => $this->text($description),
            'item' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $app
     * @param  array<string, true>  $usedRoutes
     * @return array<string, mixed>
     */
    private function appFolder(array $app, array &$usedRoutes): array
    {
        $folders = [];

        foreach ($app['folders'] as $folder) {
            if (blank($folder['description'] ?? null)) {
                $this->problems[] = "Folder [{$app['name']} › {$folder['name']}] has no description.";
            }

            $requests = [];

            foreach ($folder['requests'] as $entry) {
                $usedRoutes[$entry['route']] = true;
                $request = $this->requestItem($entry, $app);

                if ($request !== null) {
                    $requests[] = $request;
                }
            }

            $folders[] = [
                'name' => $folder['name'],
                'description' => $this->text($folder['description'] ?? ''),
                'item' => $requests,
            ];
        }

        if (blank($app['description'] ?? null)) {
            $this->problems[] = "App folder [{$app['name']}] has no description.";
        }

        return [
            'name' => $app['name'],
            'description' => $this->text($app['description'] ?? ''),
            'auth' => $app['auth'] === 'bearer'
                ? ['type' => 'bearer', 'bearer' => [['key' => 'token', 'value' => '{{token}}', 'type' => 'string']]]
                : ['type' => 'noauth'],
            'event' => [$this->script('prerequest', $app['auth'] === 'bearer' ? $this->mobileScript() : $this->cookieScript())],
            'item' => $folders,
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, mixed>  $app
     * @return array<string, mixed>|null
     */
    private function requestItem(array $entry, array $app): ?array
    {
        $label = "{$app['name']} › {$entry['name']}";
        $route = Route::getRoutes()->getByName($entry['route']);

        if ($route === null) {
            $this->problems[] = "[{$label}] uses route [{$entry['route']}], which does not exist.";

            return null;
        }

        $method = $this->method($route);
        $isV1 = str_starts_with($route->uri(), self::V1_PREFIX);
        $path = '/'.($isV1 ? Str::after($route->uri(), self::V1_PREFIX) : $route->uri());
        $operation = $isV1 ? ($this->spec['paths'][$path][Str::lower($method)] ?? null) : [];

        if ($operation === null) {
            $this->problems[] = "[{$label}] route [{$entry['route']}] is missing from the Scramble document.";

            return null;
        }

        $request = [
            'method' => $method,
            'header' => [],
            'url' => $this->url($isV1 ? '{{baseUrl}}' : '{{appUrl}}', $path, $operation['parameters'] ?? []),
            'description' => $this->documentation($entry, $label),
        ];

        // An empty security list is how Scramble marks @unauthenticated routes; non-v1 routes (the CSRF cookie) are public too.
        if (! $isV1 || ($operation['security'] ?? null) === []) {
            $request['auth'] = ['type' => 'noauth'];
        }

        $body = $this->body($operation['requestBody']['content'] ?? []);

        if ($body !== null) {
            $request['body'] = $body;

            if ($body['mode'] === 'raw') {
                $request['header'][] = ['key' => 'Content-Type', 'value' => 'application/json'];
            }
        }

        $item = ['name' => $entry['name'], 'request' => $request, 'response' => []];

        if ($entry['route'] === self::TOKEN_ROUTE) {
            $item['event'] = [$this->script('test', $this->saveTokenScript())];
        }

        return $item;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function documentation(array $entry, string $label): string
    {
        $shared = $this->docs('endpoints')[$entry['route']] ?? null;

        if (! is_array($shared)) {
            $this->problems[] = "[{$label}] route [{$entry['route']}] has no documentation in docs/postman/endpoints.php.";
            $shared = [];
        }

        $sections = [...$shared, ...array_intersect_key($entry, self::SECTIONS)];

        if (filled($entry['next'] ?? null)) {
            $sections['success'] = trim(($sections['success'] ?? '')."\n\n**Next:** {$entry['next']}");
        }

        $markdown = [];

        foreach (self::SECTIONS as $key => $heading) {
            if (blank($sections[$key] ?? null)) {
                if (! in_array($key, self::OPTIONAL_SECTIONS, true)) {
                    $this->problems[] = "[{$label}] has no \"{$heading}\" documentation.";
                }

                continue;
            }

            $markdown[] = "### {$heading}\n\n".trim($sections[$key]);
        }

        return $this->text(implode("\n\n", $markdown));
    }

    /**
     * @param  array<string, true>  $usedRoutes
     */
    private function checkRouteCoverage(array $usedRoutes): void
    {
        $notUsed = $this->docs('collection')['not_used_directly'];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $name = $route->getName();

            if ($name === null || ! str_starts_with($name, 'api.v1.') || isset($usedRoutes[$name]) || isset($notUsed[$name])) {
                continue;
            }

            $this->problems[] = "Route [{$name}] is in no app folder and not in not_used_directly in docs/postman/collection.php.";
        }

        foreach (array_keys($notUsed) as $name) {
            if (Route::getRoutes()->getByName($name) === null) {
                $this->warn("not_used_directly in docs/postman/collection.php lists [{$name}], which no longer exists.");
            }
        }

        foreach (array_keys($this->docs('endpoints')) as $name) {
            if (! isset($usedRoutes[$name])) {
                $this->warn("docs/postman/endpoints.php documents [{$name}], which no app folder uses.");
            }
        }
    }

    private function method(LaravelRoute $route): string
    {
        return collect($route->methods())->reject(fn (string $method): bool => $method === 'HEAD')->first();
    }

    /**
     * Fills placeholders such as :otp_cooldown_seconds (see placeholderValues()).
     */
    private function text(string $markdown): string
    {
        return trim(strtr($markdown, $this->placeholders));
    }

    /**
     * @param  list<array<string, mixed>>  $parameters
     * @return array<string, mixed>
     */
    private function url(string $host, string $path, array $parameters): array
    {
        $segments = array_map(
            fn (string $segment): string => str_starts_with($segment, '{') ? ':'.trim($segment, '{}') : $segment,
            explode('/', trim($path, '/')),
        );

        $url = [
            'raw' => $host.'/'.implode('/', $segments),
            'host' => [$host],
            'path' => $segments,
        ];

        $query = [];
        $variables = [];

        foreach ($parameters as $parameter) {
            $parameter = $this->resolve($parameter);
            $value = $this->stringValue($this->example($parameter['schema'] ?? [], $parameter['name']));

            if ($parameter['in'] === 'query') {
                $query[] = array_filter([
                    'key' => $parameter['name'],
                    'value' => $value,
                    'description' => $parameter['description'] ?? null,
                    'disabled' => ! ($parameter['required'] ?? false),
                ], fn (mixed $field): bool => $field !== null);
            }

            if ($parameter['in'] === 'path') {
                $variables[] = array_filter([
                    'key' => $parameter['name'],
                    'value' => $value,
                    'description' => $parameter['description'] ?? null,
                ], fn (mixed $field): bool => $field !== null);
            }
        }

        $enabledQuery = array_filter($query, fn (array $item): bool => ! $item['disabled']);

        if ($enabledQuery !== []) {
            $url['raw'] .= '?'.implode('&', array_map(fn (array $item): string => "{$item['key']}={$item['value']}", $enabledQuery));
        }

        if ($query !== []) {
            $url['query'] = $query;
        }

        if ($variables !== []) {
            $url['variable'] = $variables;
        }

        return $url;
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>|null
     */
    private function body(array $content): ?array
    {
        if (isset($content['multipart/form-data'])) {
            $schema = $this->resolve($content['multipart/form-data']['schema'] ?? []);

            return ['mode' => 'formdata', 'formdata' => $this->formData($schema)];
        }

        if (isset($content['application/json'])) {
            $example = $this->example($content['application/json']['schema'] ?? []);

            return [
                'mode' => 'raw',
                'raw' => json_encode($example, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'options' => ['raw' => ['language' => 'json']],
            ];
        }

        return null;
    }

    /**
     * Flattens the schema to form fields (links[0][url]); optional fields start disabled.
     *
     * @param  array<string, mixed>  $schema
     * @return list<array<string, mixed>>
     */
    private function formData(array $schema, string $prefix = '', bool $parentRequired = true): array
    {
        $fields = [];
        $required = $schema['required'] ?? [];

        foreach ($schema['properties'] ?? [] as $name => $property) {
            $property = $this->resolve($property);
            $key = $prefix === '' ? $name : "{$prefix}[{$name}]";
            $isRequired = $parentRequired && in_array($name, $required, true);
            $type = $this->type($property);

            if ($type === 'object') {
                $fields = [...$fields, ...$this->formData($property, $key, $isRequired)];

                continue;
            }

            if ($type === 'array') {
                $items = $this->resolve($property['items'] ?? []);
                $fields = $this->type($items) === 'object'
                    ? [...$fields, ...$this->formData($items, "{$key}[0]", $isRequired)]
                    : [...$fields, $this->formField("{$key}[0]", $items, $name, $isRequired)];

                continue;
            }

            $fields[] = $this->formField($key, $property, $name, $isRequired);
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function formField(string $key, array $schema, string $name, bool $isRequired): array
    {
        $field = ($schema['format'] ?? null) === 'binary'
            ? ['key' => $key, 'type' => 'file', 'src' => []]
            : ['key' => $key, 'value' => $this->stringValue($this->example($schema, $name)), 'type' => 'text'];

        if (isset($schema['description'])) {
            $field['description'] = $schema['description'];
        }

        if (! $isRequired) {
            $field['disabled'] = true;
        }

        return $field;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function example(array $schema, ?string $name = null): mixed
    {
        $schema = $this->resolve($schema);

        foreach (['example', 'default', 'const'] as $key) {
            if (array_key_exists($key, $schema)) {
                return $schema[$key];
            }
        }

        if (isset($schema['enum'])) {
            return $schema['enum'][0];
        }

        // Field examples are for plain values; an object such as a translated name { ar, en } is built from its properties.
        if ($name !== null && array_key_exists($name, self::FIELD_EXAMPLES) && ! in_array($this->type($schema), ['object', 'array'], true)) {
            return self::FIELD_EXAMPLES[$name];
        }

        return match ($this->type($schema)) {
            'object' => (object) collect($schema['properties'] ?? [])
                ->map(fn (array $property, string $propertyName): mixed => $this->example($property, $propertyName))
                ->all(),
            'array' => [$this->example($schema['items'] ?? [], $name)],
            'integer' => max(1, (int) ($schema['minimum'] ?? 1)),
            'number' => 1.5,
            'boolean' => true,
            default => match ($schema['format'] ?? null) {
                'uri' => 'https://example.com',
                'email' => 'user@example.com',
                'date-time' => '2026-01-01T09:00:00Z',
                'date' => '2026-01-01',
                default => 'string',
            },
        };
    }

    /**
     * Follows $ref and merges allOf, so callers only see plain schemas.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function resolve(array $schema): array
    {
        if (isset($schema['$ref'])) {
            [, , $section, $name] = explode('/', $schema['$ref']);

            return $this->resolve($this->spec['components'][$section][$name] ?? []);
        }

        if (isset($schema['allOf'])) {
            $merged = ['type' => 'object', 'properties' => [], 'required' => []];

            foreach ($schema['allOf'] as $part) {
                $part = $this->resolve($part);
                $merged['properties'] = [...$merged['properties'], ...($part['properties'] ?? [])];
                $merged['required'] = [...$merged['required'], ...($part['required'] ?? [])];
            }

            return $merged;
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function type(array $schema): ?string
    {
        $type = $schema['type'] ?? (isset($schema['properties']) ? 'object' : null);

        if (is_array($type)) {
            $type = collect($type)->first(fn (string $candidate): bool => $candidate !== 'null');
        }

        return $type;
    }

    private function stringValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        };
    }

    /**
     * Postman v2.1 has no folder or collection headers, so pre-request scripts add them to every request.
     *
     * @param  array<string, string>  $headers
     * @return list<string>
     */
    private function headersScript(array $headers): array
    {
        $lines = ['const headers = {'];

        foreach ($headers as $key => $value) {
            $lines[] = "    '{$key}': '{$value}',";
        }

        return [
            ...$lines,
            '};',
            '',
            'Object.entries(headers).forEach(([key, value]) => {',
            '    pm.request.headers.upsert({ key, value: pm.variables.replaceIn(value) });',
            '});',
        ];
    }

    /**
     * @return list<string>
     */
    private function mobileScript(): array
    {
        return $this->headersScript([
            'X-Platform' => '{{platform}}',
            'X-App-Version' => '{{appVersion}}',
        ]);
    }

    /**
     * Sends what a browser would: the web platform, the website as Origin and Referer, and the XSRF-TOKEN cookie
     * back as the X-XSRF-TOKEN header (Sanctum's CSRF check).
     *
     * @return list<string>
     */
    private function cookieScript(): array
    {
        return [
            ...$this->headersScript([
                'X-Platform' => 'web',
                'Origin' => '{{frontendUrl}}',
                'Referer' => '{{frontendUrl}}/',
            ]),
            '',
            "const appUrl = pm.variables.replaceIn('{{appUrl}}');",
            '',
            "pm.cookies.jar().get(appUrl, 'XSRF-TOKEN', (error, xsrfToken) => {",
            '    if (error) {',
            '        console.warn(`Cannot read the XSRF-TOKEN cookie: open Cookies › Domains Allowlist and add the host of ${appUrl} (for example localhost), otherwise requests answer 419.`, error);',
            '',
            '        return;',
            '    }',
            '',
            '    if (!xsrfToken) {',
            "        console.warn('No XSRF-TOKEN cookie yet: call Get CSRF cookie first, or requests answer 419.');",
            '',
            '        return;',
            '    }',
            '',
            "    pm.request.headers.upsert({ key: 'X-XSRF-TOKEN', value: decodeURIComponent(xsrfToken) });",
            '});',
        ];
    }

    /**
     * @return list<string>
     */
    private function saveTokenScript(): array
    {
        $field = self::TOKEN_RESPONSE_FIELD;

        return [
            'if (pm.response.code === 200) {',
            "    const token = pm.response.json().{$field};",
            '',
            '    if (token) {',
            "        pm.environment.set('token', token);",
            '    }',
            '}',
        ];
    }

    /**
     * @param  list<string>  $lines
     * @return array<string, mixed>
     */
    private function script(string $listen, array $lines): array
    {
        return ['listen' => $listen, 'script' => ['type' => 'text/javascript', 'exec' => $lines]];
    }

    /**
     * @return array<string, mixed>
     */
    private function environment(): array
    {
        $values = [];

        foreach ($this->docs('collection')['environment']['values'] as $key => $value) {
            $values[] = ['key' => $key, 'value' => $value, 'type' => $key === 'token' ? 'secret' : 'default', 'enabled' => true];
        }

        return [
            'id' => $this->stableUuid('environment'),
            'name' => $this->docs('collection')['environment']['name'],
            'values' => $values,
            '_postman_variable_scope' => 'environment',
        ];
    }

    /**
     * A fixed ID, so re-importing replaces the existing collection instead of adding a copy.
     */
    private function stableUuid(string $name): string
    {
        $hash = md5("tayari-postman-{$name}");

        return sprintf('%s-%s-%s-%s-%s', substr($hash, 0, 8), substr($hash, 8, 4), substr($hash, 12, 4), substr($hash, 16, 4), substr($hash, 20, 12));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function writeJson(string $relativePath, array $data): void
    {
        $path = base_path($relativePath);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL);
    }
}
