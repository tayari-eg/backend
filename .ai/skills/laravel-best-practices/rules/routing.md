# Routing and Controller Best Practices

## Use Implicit Route Model Binding

Let Laravel resolve models from route parameters when the default lookup and missing-model behavior fit the endpoint.

Instead of manual lookup:

```php
public function show(int $id): View
{
    $post = Post::findOrFail($id);

    return view('posts.show', ['post' => $post]);
}
```

Use route model binding:

```php
public function show(Post $post): View
{
    return view('posts.show', ['post' => $post]);
}
```

## Scope Nested Bindings

Use scoped bindings when a nested resource must belong to its parent. This constrains model resolution; it does not replace authorization.

```php
Route::get('/users/{user}/posts/{post}', function (User $user, Post $post) {
    // The resolved post belongs to the resolved user.
})->scopeBindings();
```

## Use Resource Routes for Resourceful Actions

Use `Route::resource()` or `Route::apiResource()` when the endpoint follows Laravel's resource-controller actions. Define explicit routes when the behavior does not fit that vocabulary.

```php
Route::resource('posts', PostController::class);

// Alternatively, for an API-only resource:
Route::apiResource('posts', ApiPostController::class);
```

`apiResource()` omits the HTML-oriented `create` and `edit` routes. It does not itself add an `/api` prefix; that prefix comes from the application's API route configuration.

## Organize Controllers Around Resources

As a general default, organize each controller around one resource and use Laravel's standard resource actions: `index`, `show`, `create`, `store`, `edit`, `update`, and `destroy`. This keeps routes predictable and prevents controllers from accumulating unrelated behavior.

When a controller needs a custom action such as `publish`, `approve`, or `archive`, first consider whether that behavior represents a separate resource. A focused resource controller gives the behavior its own authorization, validation, and middleware boundary.

Custom action on the primary controller:

```php
Route::post('/podcasts/{podcast}/publish', [PodcastController::class, 'publish']);
```

The published podcast modeled as a resource:

```php
Route::post('/published-podcasts/{podcast}', [PublishedPodcastController::class, 'store'])
    ->name('published-podcasts.store');

Route::delete('/published-podcasts/{podcast}', [PublishedPodcastController::class, 'destroy'])
    ->name('published-podcasts.destroy');
```

```php
class PublishedPodcastController extends Controller
{
    public function store(Podcast $podcast): RedirectResponse
    {
        $podcast->publish();

        return back();
    }

    public function destroy(Podcast $podcast): RedirectResponse
    {
        $podcast->unpublish();

        return back();
    }
}
```

Treat a custom verb as a design signal, not proof that another controller is required. Use query parameters for simple filtering, and keep an explicit action route when modeling the operation as a resource would obscure the domain or conflict with established project conventions.

## Keep Controllers Focused on HTTP Concerns

Controllers should coordinate HTTP input, authorization, validation, an application operation, and the response. Extract substantial or reusable business logic, but do not introduce an action or service merely to satisfy an arbitrary line limit.

```php
public function store(StorePostRequest $request, CreatePostAction $create): RedirectResponse
{
    $post = $create->handle($request->validated());

    return redirect()->route('posts.show', $post);
}
```

A form request can perform validation and authorization before the controller runs. Do not repeat its rules in the controller. In Tayari, validation always goes in a form request, even for simple endpoints; see the validation rules.

## Version the API in the URL and Split It by Who Uses It

Tayari uses the Laravel 13 skeleton: there is no `app/Http/Kernel.php` and no `RouteServiceProvider`. Every API route is versioned in the URL (`/api/v1/...`). There are no unversioned API routes.

There is one API, split by **who** uses it, not by platform. The React website and the Flutter app call the same endpoints:

| Prefix | Used by | Route file |
| --- | --- | --- |
| `/api/v1/...` | everyone, no auth (e.g. `app-config`) | `routes/api/v1/public.php` |
| `/api/v1/auth/...` | everyone (OTP, login, logout) | `routes/api/v1/auth.php` |
| `/api/v1/client/...` | customers and businesses, web and app | `routes/api/v1/client.php` |
| `/api/v1/tayar/...` | the Tayar app | `routes/api/v1/tayar.php` |
| `/api/v1/admin/...` | the admin panel | `routes/api/v1/admin.php` |

Register the files in `withRouting(then: ...)` in `bootstrap/app.php`, with the `api/v1` prefix, the `api.v1.` name prefix, and a name per file (plus a URL prefix for every file except `public.php`). There is no `routes/api.php`, so no unversioned API routes exist:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    then: function (): void {
        Route::middleware(['api', EnsureAppVersionIsSupported::class])->prefix('api/v1')->name('api.v1.')->group(function (): void {
            Route::name('public.')->group(base_path('routes/api/v1/public.php'));
            Route::prefix('auth')->name('auth.')->group(base_path('routes/api/v1/auth.php'));
            Route::prefix('client')->name('client.')->group(base_path('routes/api/v1/client.php'));
            Route::prefix('tayar')->name('tayar.')->group(base_path('routes/api/v1/tayar.php'));
            Route::prefix('admin')->name('admin.')->group(base_path('routes/api/v1/admin.php'));
        });
    },
)
```

Each file applies its own middleware (`auth:sanctum` plus the side check for client, Tayar or admin; `permission:...` for admin). A route must sit in the file for the side that calls it, so a client token can't reach a Tayar route and the other way round. The same Sanctum token works for every version.

Register middleware aliases and groups in `withMiddleware()` in `bootstrap/app.php` (the Spatie `role`, `permission` and `role_or_permission` aliases are already there).

### Only the HTTP layer is versioned

Controllers, requests and resources live under a version folder: `app/Http/Controllers/Api/V1/{Public,Auth,Client,Tayar,Admin}`, `app/Http/Requests/Api/V1/{Public,Auth,Client,Tayar,Admin}`, `app/Http/Resources/Api/V1/{Public,Auth,Client,Tayar,Admin}`. Resources used by more than one side (e.g. `MediaResource`) live at the `app/Http/Resources/Api/V1/` root. `app/Domains` (Models, Actions, Enums, Events) is never versioned: every version calls the same Actions.

### When to add v2

A new version is only for **breaking changes**: a removed or renamed response field, a changed field type, a new required parameter, or changed behaviour that old apps can't handle. New endpoints, new response fields and new optional parameters stay in v1.

When v2 is needed, add `routes/api/v2/*.php` registered the same way (`api/v2`, `api.v2.`), and copy only the changed controllers, requests and resources into `V2`. Unchanged v2 routes point to the V1 controllers. v1 stays until most app users have updated.

### Platform and app version headers

Clients send `X-Platform` (`web` | `android` | `ios`); the Flutter app also sends `X-App-Version`. `GET /api/v1/app-config` (public, route name `api.v1.public.app-config`) returns `min_supported_version` and `latest_version` per platform (`android`, `ios`) plus the server time in UTC. There is no `force_update` flag: an app below `min_supported_version` must update. A middleware returns `426 Upgrade Required` for app versions below the minimum. Log `X-App-Version` to track old-version usage.

### Responses

API responses always go through API Resources: never return a model or a model collection directly. Every version uses one error format:

```json
{ "message": "...", "errors": { "field": ["..."] } }
```
