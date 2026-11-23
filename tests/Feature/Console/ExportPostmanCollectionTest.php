<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Dotenv\Dotenv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\PendingCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExportPostmanCollectionTest extends TestCase
{
    // Scramble reads the table columns to describe models, so the schema must exist.
    use RefreshDatabase;

    // Write next to the test, never over the committed postman/ files.
    private const string OUTPUT_DIRECTORY = 'storage/framework/testing/postman';

    private const string DOCS_COPY_DIRECTORY = 'storage/framework/testing/postman-docs';

    private const string COLLECTION_FILE = 'Tayari.postman_collection.json';

    private const string ENVIRONMENT_FILE = 'Tayari.local.postman_environment.json';

    private const array TEMPLATE_HEADINGS = ['What it does', 'Screen', 'Who can call it', 'Request', 'Success', 'Errors'];

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path(self::OUTPUT_DIRECTORY));
        File::deleteDirectory(base_path(self::DOCS_COPY_DIRECTORY));

        parent::tearDown();
    }

    /**
     * @param  array<string, string>  $options
     */
    private function export(array $options = []): PendingCommand
    {
        return $this->artisan('postman:export', ['--output' => self::OUTPUT_DIRECTORY, ...$options]);
    }

    private function exportedFile(string $file): string
    {
        return File::get(base_path(self::OUTPUT_DIRECTORY.'/'.$file));
    }

    /**
     * @return array<string, mixed>
     */
    private function exportedCollection(): array
    {
        $this->export()->assertSuccessful();

        return json_decode($this->exportedFile(self::COLLECTION_FILE), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    private function docs(string $name): array
    {
        return require base_path("docs/postman/{$name}.php");
    }

    /**
     * Copies docs/postman with one file replaced, and returns the --docs option for it.
     *
     * @param  array<string, mixed>  $contents
     * @return array{--docs: string}
     */
    private function docsWith(string $name, array $contents): array
    {
        File::copyDirectory(base_path('docs/postman'), base_path(self::DOCS_COPY_DIRECTORY));
        File::put(base_path(self::DOCS_COPY_DIRECTORY."/{$name}.php"), '<?php return '.var_export($contents, true).';');

        return ['--docs' => self::DOCS_COPY_DIRECTORY];
    }

    /**
     * @param  array<string, mixed>  $collection
     * @return array<string, mixed>
     */
    private function topFolder(array $collection, string $name): array
    {
        return collect($collection['item'])->firstWhere('name', $name);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{folders: list<array<string, mixed>>, requests: list<array<string, mixed>>}
     */
    private function flatten(array $items): array
    {
        $flat = ['folders' => [], 'requests' => []];

        foreach ($items as $item) {
            if (! isset($item['item'])) {
                $flat['requests'][] = $item;

                continue;
            }

            $flat['folders'][] = $item;
            $children = $this->flatten($item['item']);
            $flat['folders'] = [...$flat['folders'], ...$children['folders']];
            $flat['requests'] = [...$flat['requests'], ...$children['requests']];
        }

        return $flat;
    }

    /**
     * @param  array<string, mixed>  $request
     */
    private function requestKey(array $request): string
    {
        return $request['request']['method'].' '.Str::before($request['request']['url']['raw'], '?');
    }

    public function test_it_writes_a_v2_1_collection_and_an_environment_with_the_new_variables(): void
    {
        $collection = $this->exportedCollection();

        $this->assertSame('https://schema.getpostman.com/json/collection/v2.1.0/collection.json', $collection['info']['schema']);
        $this->assertNotEmpty($collection['info']['name']);

        $environment = json_decode($this->exportedFile(self::ENVIRONMENT_FILE), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('environment', $environment['_postman_variable_scope']);
        $this->assertSame([
            'appUrl' => 'http://localhost:8000',
            'baseUrl' => 'http://localhost:8000/api/v1',
            'frontendUrl' => 'http://localhost:3000',
            'token' => '',
            'locale' => 'ar',
            'platform' => 'android',
            'appVersion' => '1.0.0',
        ], array_column($environment['values'], 'value', 'key'));
    }

    public function test_the_top_level_and_feature_folders_are_in_flow_order(): void
    {
        $collection = $this->exportedCollection();

        $this->assertSame(['Read me first', 'Changelog', 'Flutter app', 'React website', 'React admin panel'], array_column($collection['item'], 'name'));
        $this->assertSame([], $this->topFolder($collection, 'Read me first')['item']);
        $this->assertSame([], $this->topFolder($collection, 'Changelog')['item']);

        $this->assertSame(
            ['1. App start', '2. Sign up and login', '3. Account', '4. Push notifications', '5. Business profile', '6. National ID', '7. Tayar mode · Become a tayar', '8. Tayar mode · Application status', '9. Tayar mode · Switch mode', '10. Tayar mode · Go online and offline', '11. Tayar mode · Location updates', '12. Tayar mode · Vehicle'],
            array_column($this->topFolder($collection, 'Flutter app')['item'], 'name'),
        );
        $this->assertSame(
            ['1. Before login', '2. Sign up and login', '3. Account', '4. Business onboarding', '5. National ID'],
            array_column($this->topFolder($collection, 'React website')['item'], 'name'),
        );
        $this->assertSame(
            ['1. Login', '2. Business profiles review', '3. National IDs review', '4. Users', '5. Business types', '6. Staff', '7. Tayar applications review', '8. Tayars', '9. Vehicle types', '10. Account'],
            array_column($this->topFolder($collection, 'React admin panel')['item'], 'name'),
        );
    }

    public function test_every_v1_route_is_in_an_app_folder_or_listed_as_not_used_directly(): void
    {
        $collection = $this->exportedCollection();
        $inApps = collect(['Flutter app', 'React website', 'React admin panel'])
            ->flatMap(fn (string $app): array => $this->flatten($this->topFolder($collection, $app)['item'])['requests'])
            ->map(fn (array $request): string => $this->requestKey($request))
            ->unique();
        $notUsed = $this->docs('collection')['not_used_directly'];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (! str_starts_with((string) $route->getName(), 'api.v1.') || isset($notUsed[$route->getName()])) {
                continue;
            }

            $method = Arr::first(array_diff($route->methods(), ['HEAD']));
            $url = '{{baseUrl}}/'.preg_replace('/\{(\w+)\}/', ':$1', Str::after($route->uri(), 'api/v1/'));

            $this->assertContains("{$method} {$url}", $inApps, "Route [{$route->getName()}] is in no app folder.");
        }

        $this->assertStringContainsString('/api/v1/media/{media}', $this->topFolder($collection, 'Read me first')['description']);
    }

    public function test_every_request_follows_the_template_and_every_folder_has_a_description(): void
    {
        ['folders' => $folders, 'requests' => $requests] = $this->flatten($this->exportedCollection()['item']);

        foreach ($folders as $folder) {
            $this->assertNotSame('', trim($folder['description']), "Folder [{$folder['name']}] needs a description.");
        }

        foreach ($requests as $request) {
            foreach (self::TEMPLATE_HEADINGS as $heading) {
                $this->assertStringContainsString("### {$heading}", $request['request']['description'], "[{$request['name']}] needs a \"{$heading}\" section.");
            }
        }
    }

    public function test_the_command_fails_and_writes_nothing_when_an_endpoint_has_no_documentation(): void
    {
        $this->export($this->docsWith('endpoints', Arr::except($this->docs('endpoints'), ['api.v1.auth.me'])))
            ->expectsOutputToContain('route [api.v1.auth.me] has no documentation')
            ->assertFailed();

        $this->assertFileDoesNotExist(base_path(self::OUTPUT_DIRECTORY.'/'.self::COLLECTION_FILE));
        $this->assertFileDoesNotExist(base_path(self::OUTPUT_DIRECTORY.'/'.self::ENVIRONMENT_FILE));
    }

    public function test_the_command_fails_when_a_route_is_in_no_app_and_not_listed_as_unused(): void
    {
        $this->export($this->docsWith('collection', ['not_used_directly' => []] + $this->docs('collection')))
            ->expectsOutputToContain('Route [api.v1.public.media.show] is in no app folder')
            ->assertFailed();
    }

    public function test_the_collection_name_and_the_read_me_carry_the_latest_docs_version(): void
    {
        $collection = $this->exportedCollection();
        $latest = $this->docs('changelog')[0];

        $this->assertSame("Tayari API v1 · docs {$latest['version']}", $collection['info']['name']);
        $this->assertStringStartsWith("Docs version {$latest['version']} ({$latest['date']})\n\n", $this->topFolder($collection, 'Read me first')['description']);
    }

    public function test_the_changelog_folder_lists_every_version_newest_first(): void
    {
        $collection = $this->exportedCollection();
        $changelog = $this->topFolder($collection, 'Changelog');

        $this->assertSame('Changelog', $collection['item'][1]['name']);

        $position = -1;

        foreach ($this->docs('changelog') as $entry) {
            $heading = strpos($changelog['description'], "## {$entry['version']} ({$entry['date']})");
            $this->assertNotFalse($heading, "Version [{$entry['version']}] is missing from the Changelog folder.");
            $this->assertGreaterThan($position, $heading, 'Changelog versions must be newest first.');
            $position = $heading;

            foreach ($entry['changes'] as $change) {
                $this->assertStringContainsString("- {$change}", $changelog['description']);
            }
        }
    }

    /**
     * @return array<string, array{list<array<string, mixed>>, string}>
     */
    public static function invalidChangelogs(): array
    {
        $entry = fn (string $version): array => ['version' => $version, 'date' => '2026-11-21', 'changes' => ['Docs: A change.']];

        return [
            'empty' => [[], 'changelog.php has no entries'],
            'not semver' => [[$entry('1.0')], 'version [1.0] is not a valid semver version'],
            'out of order' => [[$entry('1.0.0'), $entry('1.1.0')], 'version [1.1.0] comes after the lower version [1.0.0]'],
            'duplicate' => [[$entry('1.0.0'), $entry('1.0.0')], 'version [1.0.0] is used by more than one entry'],
            'date not YYYY-MM-DD' => [[['version' => '1.0.0', 'date' => '21/11/2026', 'changes' => ['Docs: A change.']]], 'has the date [21/11/2026]: use a real date as YYYY-MM-DD'],
            'impossible date' => [[['version' => '1.0.0', 'date' => '2026-02-30', 'changes' => ['Docs: A change.']]], 'has the date [2026-02-30]'],
            'empty changes' => [[['version' => '1.0.0', 'date' => '2026-11-21', 'changes' => []]], 'version [1.0.0] needs changes'],
            'changes not a list' => [[['version' => '1.0.0', 'date' => '2026-11-21', 'changes' => 'A change.']], 'version [1.0.0] needs changes'],
            'line without a tag' => [[['version' => '1.0.0', 'date' => '2026-11-21', 'changes' => ['Docs: Tagged.', 'GET /api/v1/me — no tag.']]], 'version [1.0.0] has a line without a tag'],
            'unknown tag' => [[['version' => '1.0.0', 'date' => '2026-11-21', 'changes' => ['Fixed: GET /api/v1/me — not a tag.']]], 'version [1.0.0] has a line without a tag'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $changelog
     */
    #[DataProvider('invalidChangelogs')]
    public function test_the_command_fails_on_an_invalid_changelog(array $changelog, string $problem): void
    {
        $this->export($this->docsWith('changelog', $changelog))
            ->expectsOutputToContain($problem)
            ->assertFailed();
    }

    public function test_the_command_fails_when_a_placeholder_key_is_missing_from_env_example(): void
    {
        $envExample = self::DOCS_COPY_DIRECTORY.'/.env.example';
        File::ensureDirectoryExists(base_path(self::DOCS_COPY_DIRECTORY));
        File::put(base_path($envExample), preg_replace('/^OTP_RESEND_COOLDOWN_SECONDS=.*$/m', '', File::get(base_path('.env.example'))));

        $this->export(['--env-example' => $envExample])
            ->expectsOutputToContain('Placeholder [:otp_cooldown_seconds] needs OTP_RESEND_COOLDOWN_SECONDS')
            ->assertFailed();
    }

    public function test_placeholders_come_from_env_example_and_not_from_the_running_config(): void
    {
        $cooldown = Dotenv::parse(File::get(base_path('.env.example')))['OTP_RESEND_COOLDOWN_SECONDS'];
        $first = json_encode($this->exportedCollection(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $this->assertStringContainsString("countdown at {$cooldown} seconds", $first);

        config([
            'tayari.otp.resend_cooldown_seconds' => (int) $cooldown + 999,
            'tayari.otp.rate_limits.per_phone' => 999,
            'tayari.pagination.per_page' => 999,
        ]);

        $this->assertSame($first, json_encode($this->exportedCollection(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public function test_the_committed_postman_files_match_a_fresh_export(): void
    {
        $this->export()->assertSuccessful();

        foreach ([self::COLLECTION_FILE, self::ENVIRONMENT_FILE] as $file) {
            $this->assertSame(
                File::get(base_path("postman/{$file}")),
                $this->exportedFile($file),
                "postman/{$file} is out of date: run php artisan postman:export and commit the result.",
            );
        }
    }

    public function test_flutter_uses_bearer_and_the_react_folders_use_the_cookie_flow(): void
    {
        $collection = $this->exportedCollection();

        $flutter = $this->topFolder($collection, 'Flutter app');
        $this->assertSame(['type' => 'bearer', 'bearer' => [['key' => 'token', 'value' => '{{token}}', 'type' => 'string']]], $flutter['auth']);
        $this->assertStringContainsString("'X-Platform': '{{platform}}'", implode("\n", $flutter['event'][0]['script']['exec']));

        foreach (['React website' => '2. Sign up and login', 'React admin panel' => '1. Login'] as $app => $loginFolder) {
            $folder = $this->topFolder($collection, $app);
            $this->assertSame(['type' => 'noauth'], $folder['auth'], "[{$app}] must not use a Bearer token.");

            $script = implode("\n", $folder['event'][0]['script']['exec']);
            $this->assertStringContainsString("'XSRF-TOKEN'", $script);
            $this->assertStringContainsString("key: 'X-XSRF-TOKEN'", $script);
            $this->assertStringContainsString("'Origin': '{{frontendUrl}}'", $script);
            $this->assertStringContainsString("'X-Platform': 'web'", $script);

            $firstRequest = collect($folder['item'])->firstWhere('name', $loginFolder)['item'][0];
            $this->assertSame('Get CSRF cookie', $firstRequest['name']);
            $this->assertSame('{{appUrl}}/sanctum/csrf-cookie', $firstRequest['request']['url']['raw']);

            $keys = array_map(fn (array $request): string => $this->requestKey($request), $this->flatten($folder['item'])['requests']);
            $this->assertNotContains('POST {{baseUrl}}/auth/token', $keys);
            $this->assertNotContains('POST {{baseUrl}}/auth/devices', $keys);
        }
    }

    public function test_public_requests_have_no_auth_and_the_token_request_saves_the_token(): void
    {
        $flutter = $this->flatten($this->topFolder($this->exportedCollection(), 'Flutter app')['item'])['requests'];
        $byKey = collect($flutter)->keyBy(fn (array $request): string => $this->requestKey($request));

        $this->assertSame(['type' => 'noauth'], $byKey['GET {{baseUrl}}/app-config']['request']['auth']);
        $this->assertArrayNotHasKey('auth', $byKey['GET {{baseUrl}}/auth/me']['request']);

        $token = $byKey['POST {{baseUrl}}/auth/token'];
        $this->assertSame('test', $token['event'][0]['listen']);
        $script = implode("\n", $token['event'][0]['script']['exec']);
        $this->assertStringContainsString('pm.response.json().token', $script);
        $this->assertStringContainsString("pm.environment.set('token', token)", $script);
    }

    public function test_the_token_script_reads_the_field_the_token_endpoint_returns(): void
    {
        $user = User::factory()->create(['phone' => '+201000000000']);

        $this->postJson(route('api.v1.auth.token'), ['phone' => $user->phone, 'password' => 'password', 'device_name' => 'Postman'])
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_requests_carry_path_variables_query_parameters_and_example_bodies(): void
    {
        $requests = collect($this->flatten($this->exportedCollection()['item'])['requests'])
            ->keyBy(fn (array $request): string => $this->requestKey($request));

        $this->assertSame('businessProfile', $requests['POST {{baseUrl}}/admin/business-profiles/:businessProfile/approve']['request']['url']['variable'][0]['key']);
        $this->assertSame(['status', 'per_page'], array_column($requests['GET {{baseUrl}}/admin/business-profiles']['request']['url']['query'], 'key'));

        $register = json_decode($requests['POST {{baseUrl}}/auth/register']['request']['body']['raw'], true);
        $this->assertSame(['name', 'phone', 'password', 'password_confirmation', 'register_as'], array_keys($register));

        $fields = collect($requests['POST {{baseUrl}}/client/business-profile']['request']['body']['formdata'])->keyBy('key');
        $this->assertSame('file', $fields['national_id_front']['type']);
        $this->assertTrue($fields['tax_card']['disabled']);
    }

    public function test_no_request_name_is_a_raw_path(): void
    {
        foreach ($this->flatten($this->exportedCollection()['item'])['requests'] as $request) {
            $this->assertDoesNotMatchRegularExpression('#^(GET|POST|PUT|PATCH|DELETE) /#', $request['name']);
        }
    }

    public function test_the_output_is_the_same_on_every_run(): void
    {
        $this->export()->assertSuccessful();
        $first = $this->exportedFile(self::COLLECTION_FILE);

        $this->export()->assertSuccessful();

        $this->assertSame($first, $this->exportedFile(self::COLLECTION_FILE));
    }

    public function test_the_files_hold_no_real_looking_secrets(): void
    {
        $this->export()->assertSuccessful();
        $content = $this->exportedFile(self::COLLECTION_FILE).$this->exportedFile(self::ENVIRONMENT_FILE);

        preg_match_all('/(?:\+?20|0)?1[0125]\d{8}/', $content, $phones);
        foreach ($phones[0] as $phone) {
            $this->assertMatchesRegularExpression('/^(?:\+?20|0)?1000000000$/', $phone, "Use a clearly fake phone instead of [{$phone}].");
        }

        $this->assertDoesNotMatchRegularExpression('/\d+\|[A-Za-z0-9]{40}/', $content, 'A Sanctum token is in the collection.');
        $this->assertDoesNotMatchRegularExpression('/Bearer [A-Za-z0-9|]{20,}/', $content, 'A Bearer token is in the collection.');
        $this->assertDoesNotMatchRegularExpression('/eyJ[A-Za-z0-9_-]{10,}/', $content, 'A JWT is in the collection.');
    }
}
