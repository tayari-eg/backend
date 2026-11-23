<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Demo data

Fake accounts and catalog data for testing every screen by hand. Never run it on a database with real users; the database is wiped before launch.

1. Set `DEMO_USERS_PASSWORD` in `.env` (the password of every demo account). While it is empty, the seeder refuses to run and creates nothing.
2. Run `php artisan db:seed --class=DemoDataSeeder`. `php artisan db:seed` alone never adds demo data.

It adds any missing roles, business types, vehicle types and modes, then categories (every main category with at least one subcategory), 8 Cairo and Giza areas, pricing rules and the accounts below, with placeholder documents on the private disk. Running it again creates nothing twice. The phones, the shared password and each account's `next_step` are also in the Postman collection (**Read me first › Demo accounts**).

| Phone | Account |
| --- | --- |
| 01000000001 | Demo Admin (staff, admin) |
| 01000000002 | Demo Ops (staff, ops) |
| 01000000003 | Demo Support (staff, support) |
| 01000000004 | Demo Business Approved (approved business profile, restaurant) |
| 01000000005 | Demo Business Pending (business profile waiting for review) |
| 01000000006 | Demo Business Rejected (rejected business profile, with a reason) |
| 01000000007 | Demo Tayar Approved (approved, motorcycle approved and active, tayar mode) |
| 01000000008 | Demo Tayar Pending (application waiting for review, bicycle) |
| 01000000009 | Demo Tayar Rejected (rejected with a reason, can reapply after the wait) |
| 01000000010 | Demo Business New (registered as a business, no business profile yet) |
| 01000000011 | Demo Tayar New (registered as a tayar, no application yet) |

The super admin comes from `SUPER_ADMIN_*`, as with `php artisan db:seed`.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
