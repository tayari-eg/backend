<?php

namespace Database\Seeders;

use App\Domains\Accounts\Actions\ApproveBusinessProfileAction;
use App\Domains\Accounts\Actions\CreateBusinessProfileAction;
use App\Domains\Accounts\Actions\CreateStaffUserAction;
use App\Domains\Accounts\Actions\RejectBusinessProfileAction;
use App\Domains\Accounts\Actions\SwitchAppModeAction;
use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\AppMode;
use App\Domains\Accounts\Enums\BusinessLinkType;
use App\Domains\Accounts\Enums\DocumentCollection;
use App\Domains\Accounts\Enums\Locale;
use App\Domains\Accounts\Enums\RegisterAs;
use App\Domains\Accounts\Enums\StaffPermission;
use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Enums\VerificationStatus;
use App\Domains\Accounts\Models\BusinessType;
use App\Domains\Catalog\Actions\CreateAreaAction;
use App\Domains\Catalog\Actions\CreateCategoryAction;
use App\Domains\Catalog\Enums\ModeCode;
use App\Domains\Catalog\Models\Area;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Pricing\Actions\CreatePricingRuleAction;
use App\Domains\Pricing\Models\PricingRule;
use App\Domains\Tayars\Actions\ApproveTayarApplicationAction;
use App\Domains\Tayars\Actions\RejectTayarApplicationAction;
use App\Domains\Tayars\Actions\SubmitTayarApplicationAction;
use App\Domains\Tayars\Enums\TayarDocument;
use App\Domains\Tayars\Enums\TayarStatus;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\VehicleType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Demo data for manual testing of every screen. Never called from DatabaseSeeder: run it with
 * php artisan db:seed --class=DemoDataSeeder. Idempotent: rows are matched on phone, name or code.
 */
class DemoDataSeeder extends Seeder
{
    /**
     * A 1×1 transparent PNG.
     */
    private const string PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private const string PDF = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
        ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";

    private const string ADMIN_PHONE = '01000000001';

    /**
     * Main categories with their subcategories; a subcategory takes its parent's is_food.
     *
     * @var list<array{name: array{ar: string, en: string}, is_food: bool, children: list<array{ar: string, en: string}>}>
     */
    private const array CATEGORIES = [
        ['name' => ['ar' => 'طعام', 'en' => 'Food'], 'is_food' => true, 'children' => [
            ['ar' => 'وجبات', 'en' => 'Meals'],
            ['ar' => 'بقالة', 'en' => 'Groceries'],
        ]],
        ['name' => ['ar' => 'مستندات', 'en' => 'Documents'], 'is_food' => false, 'children' => [
            ['ar' => 'أوراق', 'en' => 'Papers'],
        ]],
        ['name' => ['ar' => 'طرود', 'en' => 'Parcels'], 'is_food' => false, 'children' => [
            ['ar' => 'إلكترونيات', 'en' => 'Electronics'],
            ['ar' => 'ملابس', 'en' => 'Clothes'],
            ['ar' => 'أدوات منزلية', 'en' => 'Home items'],
        ]],
        ['name' => ['ar' => 'صيدلية', 'en' => 'Pharmacy'], 'is_food' => false, 'children' => [
            ['ar' => 'أدوية', 'en' => 'Medicines'],
        ]],
    ];

    /**
     * Approximate district centres.
     *
     * @var list<array{name: array{ar: string, en: string}, city: array{ar: string, en: string}, center_lat: string, center_lng: string, radius_km: string}>
     */
    private const array AREAS = [
        ['name' => ['ar' => 'مدينة نصر', 'en' => 'Nasr City'], 'city' => ['ar' => 'القاهرة', 'en' => 'Cairo'], 'center_lat' => '30.0566000', 'center_lng' => '31.3301000', 'radius_km' => '6.00'],
        ['name' => ['ar' => 'مصر الجديدة', 'en' => 'Heliopolis'], 'city' => ['ar' => 'القاهرة', 'en' => 'Cairo'], 'center_lat' => '30.0911000', 'center_lng' => '31.3225000', 'radius_km' => '5.00'],
        ['name' => ['ar' => 'المعادي', 'en' => 'Maadi'], 'city' => ['ar' => 'القاهرة', 'en' => 'Cairo'], 'center_lat' => '29.9602000', 'center_lng' => '31.2569000', 'radius_km' => '4.00'],
        ['name' => ['ar' => 'الزمالك', 'en' => 'Zamalek'], 'city' => ['ar' => 'القاهرة', 'en' => 'Cairo'], 'center_lat' => '30.0609000', 'center_lng' => '31.2194000', 'radius_km' => '1.50'],
        ['name' => ['ar' => 'وسط البلد', 'en' => 'Downtown'], 'city' => ['ar' => 'القاهرة', 'en' => 'Cairo'], 'center_lat' => '30.0478000', 'center_lng' => '31.2400000', 'radius_km' => '2.00'],
        ['name' => ['ar' => 'القاهرة الجديدة (التجمع الخامس)', 'en' => 'New Cairo (Fifth Settlement)'], 'city' => ['ar' => 'القاهرة', 'en' => 'Cairo'], 'center_lat' => '30.0084000', 'center_lng' => '31.4913000', 'radius_km' => '8.00'],
        ['name' => ['ar' => 'السادس من أكتوبر', 'en' => '6th of October'], 'city' => ['ar' => 'الجيزة', 'en' => 'Giza'], 'center_lat' => '29.9285000', 'center_lng' => '30.9188000', 'radius_km' => '10.00'],
        ['name' => ['ar' => 'الشيخ زايد', 'en' => 'Sheikh Zayed'], 'city' => ['ar' => 'الجيزة', 'en' => 'Giza'], 'center_lat' => '30.0450000', 'center_lng' => '30.9800000', 'radius_km' => '6.00'],
    ];

    /**
     * EGP amounts per mode; food_extra only where the mode allows food.
     *
     * @var array<string, array{base_fare: string, price_per_km: string, minimum_fare: string, food_extra: ?string}>
     */
    private const array PRICES = [
        'long_distance' => ['base_fare' => '50.00', 'price_per_km' => '3.50', 'minimum_fare' => '80.00', 'food_extra' => null],
        'zone' => ['base_fare' => '20.00', 'price_per_km' => '5.00', 'minimum_fare' => '30.00', 'food_extra' => '10.00'],
        'area' => ['base_fare' => '25.00', 'price_per_km' => '4.00', 'minimum_fare' => '35.00', 'food_extra' => '10.00'],
    ];

    /**
     * @var array{base_fare: string, price_per_km: string, minimum_fare: string, food_extra: string}
     */
    private const array ZONE_BICYCLE_PRICE = ['base_fare' => '15.00', 'price_per_km' => '4.00', 'minimum_fare' => '25.00', 'food_extra' => '8.00'];

    private const string TAYAR_SHARE_PERCENT = '80.00';

    /**
     * @var list<array{string, string, StaffRole}>
     */
    private const array STAFF = [
        [self::ADMIN_PHONE, 'Demo Admin', StaffRole::Admin],
        ['01000000002', 'Demo Ops', StaffRole::Ops],
        ['01000000003', 'Demo Support', StaffRole::Support],
    ];

    /**
     * @var list<array{phone: string, name: string, business_name: string, business_type: string, status: VerificationStatus, reason: ?string}>
     */
    private const array BUSINESSES = [
        ['phone' => '01000000004', 'name' => 'Demo Business Approved', 'business_name' => 'Demo Approved Restaurant', 'business_type' => 'Restaurant', 'status' => VerificationStatus::Approved, 'reason' => null],
        ['phone' => '01000000005', 'name' => 'Demo Business Pending', 'business_name' => 'Demo Pending Pharmacy', 'business_type' => 'Pharmacy', 'status' => VerificationStatus::Pending, 'reason' => null],
        ['phone' => '01000000006', 'name' => 'Demo Business Rejected', 'business_name' => 'Demo Rejected Online Store', 'business_type' => 'Online store', 'status' => VerificationStatus::Rejected, 'reason' => 'The national ID photo is blurry. Please upload a clearer photo.'],
    ];

    /**
     * @var list<array{phone: string, name: string, national_id_number: string, vehicle_type: string, vehicle: array{plate_number: ?string, model: string, color: string, year: int}, status: TayarStatus, reason: ?string}>
     */
    private const array TAYARS = [
        ['phone' => '01000000007', 'name' => 'Demo Tayar Approved', 'national_id_number' => '29501010100011', 'vehicle_type' => 'Motorcycle',
            'vehicle' => ['plate_number' => 'ط ي ر 1001', 'model' => 'Bajaj Boxer', 'color' => 'Red', 'year' => 2022], 'status' => TayarStatus::Approved, 'reason' => null],
        ['phone' => '01000000008', 'name' => 'Demo Tayar Pending', 'national_id_number' => '29603150200022', 'vehicle_type' => 'Bicycle',
            'vehicle' => ['plate_number' => null, 'model' => 'Mountain bike', 'color' => 'Blue', 'year' => 2023], 'status' => TayarStatus::Pending, 'reason' => null],
        ['phone' => '01000000009', 'name' => 'Demo Tayar Rejected', 'national_id_number' => '29807200300033', 'vehicle_type' => 'Car',
            'vehicle' => ['plate_number' => 'ط ي ر 1003', 'model' => 'Hyundai Verna', 'color' => 'Silver', 'year' => 2018], 'status' => TayarStatus::Rejected, 'reason' => 'The criminal record (الفيش) is older than three months. Please upload a recent one.'],
    ];

    /**
     * Registered with a verified phone but not applied yet, to test the onboarding forms.
     *
     * @var list<array{phone: string, name: string, register_as: RegisterAs}>
     */
    private const array NEW_ACCOUNTS = [
        ['phone' => '01000000010', 'name' => 'Demo Business New', 'register_as' => RegisterAs::Business],
        ['phone' => '01000000011', 'name' => 'Demo Tayar New', 'register_as' => RegisterAs::Tayar],
    ];

    /**
     * @var list<array{string, string, string, string, string}>
     */
    private array $summary = [];

    /**
     * Every demo phone, as written in the docs (local format).
     *
     * @return list<string>
     */
    public static function phones(): array
    {
        return [
            ...array_column(self::STAFF, 0),
            ...array_column(self::BUSINESSES, 'phone'),
            ...array_column(self::TAYARS, 'phone'),
            ...array_column(self::NEW_ACCOUNTS, 'phone'),
        ];
    }

    public function run(): void
    {
        $password = config('tayari.demo.users_password');

        if (blank($password)) {
            $this->command?->error('Demo data skipped: set DEMO_USERS_PASSWORD in .env first. Nothing was created.');

            return;
        }

        $this->seedDependencies();
        $this->seedCategories();
        $this->seedAreas();
        $this->seedPricingRules();

        $this->seedStaff($password);
        $reviewer = User::query()->where('phone', PhoneNumber::from(self::ADMIN_PHONE)->value)->first();

        if ($reviewer === null || ! $reviewer->isStaff()) {
            $this->command?->error('Demo accounts stopped: '.self::ADMIN_PHONE.' must be the Demo Admin staff account, who reviews the demo applications. No business or tayar demo data was created.');

            return;
        }

        foreach (self::BUSINESSES as $business) {
            $this->seedBusiness($business, $password, $reviewer);
        }

        foreach (self::TAYARS as $tayar) {
            $this->seedTayar($tayar, $password, $reviewer);
        }

        foreach (self::NEW_ACCOUNTS as $account) {
            $user = $this->customer($account['phone'], $account['name'], $password, $account['register_as']);

            if ($user !== null) {
                $this->addToSummary($account['register_as']->value, $user, 'not applied');
            }
        }

        $this->command?->table(['Role', 'Name', 'Phone', 'Status', 'Next step'], $this->summary);
        $this->command?->info('Every demo account uses the DEMO_USERS_PASSWORD password.');
    }

    /**
     * Runs the normal seeders whose data is missing.
     */
    private function seedDependencies(): void
    {
        if (Permission::query()->count() < count(StaffPermission::cases()) || Role::query()->count() < count(StaffRole::cases())) {
            $this->call(RolesAndPermissionsSeeder::class);
        }

        if (! BusinessType::query()->exists()) {
            $this->call(BusinessTypeSeeder::class);
        }

        if (! VehicleType::query()->exists()) {
            $this->call(VehicleTypeSeeder::class);
        }

        if (Mode::query()->count() < count(ModeCode::cases())) {
            $this->call(ModeSeeder::class);
        }
    }

    private function seedCategories(): void
    {
        $createCategory = app(CreateCategoryAction::class);

        foreach (self::CATEGORIES as $mainIndex => $main) {
            $parent = Category::query()->main()->whereNameIs('en', $main['name']['en'])->first()
                ?? $createCategory->handle($main['name'], null, $main['is_food'], true, $mainIndex + 1);

            foreach ($main['children'] as $childIndex => $childName) {
                if (! Category::query()->under($parent->id)->whereNameIs('en', $childName['en'])->exists()) {
                    $createCategory->handle($childName, $parent->id, null, true, $childIndex + 1);
                }
            }
        }
    }

    private function seedAreas(): void
    {
        $createArea = app(CreateAreaAction::class);

        foreach (self::AREAS as $index => $area) {
            if (! Area::query()->whereNameIs('en', $area['name']['en'])->exists()) {
                $createArea->handle([...$area, 'is_active' => true, 'sort_order' => $index + 1]);
            }
        }
    }

    /**
     * One "all vehicles" rule per mode, plus a cheaper bicycle rule in the zone mode.
     */
    private function seedPricingRules(): void
    {
        $createPricingRule = app(CreatePricingRuleAction::class);
        $bicycle = $this->vehicleType('Bicycle');

        foreach (Mode::query()->get() as $mode) {
            $this->createPricingRuleIfMissing($createPricingRule, $mode, null, self::PRICES[$mode->code->value]);

            if ($mode->code === ModeCode::Zone) {
                $this->createPricingRuleIfMissing($createPricingRule, $mode, $bicycle, self::ZONE_BICYCLE_PRICE);
            }
        }
    }

    /**
     * @param  array{base_fare: string, price_per_km: string, minimum_fare: string, food_extra: ?string}  $prices
     */
    private function createPricingRuleIfMissing(CreatePricingRuleAction $createPricingRule, Mode $mode, ?VehicleType $vehicleType, array $prices): void
    {
        if (PricingRule::query()->where('mode_id', $mode->id)->where('vehicle_type_id', $vehicleType?->id)->exists()) {
            return;
        }

        $createPricingRule->handle([
            'mode_id' => $mode->id,
            'vehicle_type_id' => $vehicleType?->id,
            ...$prices,
            'food_extra' => $mode->allows_food ? $prices['food_extra'] : null,
            'tayar_share_percent' => self::TAYAR_SHARE_PERCENT,
            'is_active' => true,
        ]);
    }

    private function seedStaff(string $password): void
    {
        $createStaff = app(CreateStaffUserAction::class);

        foreach (self::STAFF as [$phone, $name, $role]) {
            $phoneNumber = PhoneNumber::from($phone);
            $user = User::query()->where('phone', $phoneNumber->value)->first();

            if ($user !== null && ! $user->isStaff()) {
                $this->command?->warn("Skipped {$name}: {$phoneNumber->value} already belongs to a non-staff account.");

                continue;
            }

            // Refreshed so the database defaults (status) are loaded.
            $user ??= $createStaff->handle($name, $phoneNumber, $password, $role)->refresh();
            $this->addToSummary($role->value, $user, $user->status->value);
        }
    }

    /**
     * @param  array{phone: string, name: string, business_name: string, business_type: string, status: VerificationStatus, reason: ?string}  $business
     */
    private function seedBusiness(array $business, string $password, User $reviewer): void
    {
        $user = $this->customer($business['phone'], $business['name'], $password, RegisterAs::Business);

        if ($user === null) {
            return;
        }

        $profile = $user->businessProfile ?? app(CreateBusinessProfileAction::class)->handle(
            $user,
            [
                'business_type_id' => BusinessType::query()->whereNameIs('en', $business['business_type'])->firstOrFail()->id,
                'business_name' => $business['business_name'],
                'description' => 'Demo business for manual testing.',
                'avg_daily_orders' => 25,
                'tax_registration_number' => $business['status'] === VerificationStatus::Approved ? '123-456-789' : null,
            ],
            [
                DocumentCollection::NationalIdFront->value => $this->placeholderFile('national-id-front.png'),
                DocumentCollection::NationalIdBack->value => $this->placeholderFile('national-id-back.png'),
                ...($business['status'] === VerificationStatus::Approved ? [
                    DocumentCollection::CommercialRegister->value => $this->placeholderFile('commercial-register.pdf'),
                    DocumentCollection::TaxCard->value => $this->placeholderFile('tax-card.pdf'),
                ] : []),
            ],
            [['type' => BusinessLinkType::Website->value, 'url' => 'https://example.com']],
        );

        if ($profile->isPending() && $business['status'] === VerificationStatus::Approved) {
            $profile = app(ApproveBusinessProfileAction::class)->handle($profile, $reviewer);
        }

        if ($profile->isPending() && $business['status'] === VerificationStatus::Rejected) {
            $profile = app(RejectBusinessProfileAction::class)->handle($profile, $reviewer, $business['reason']);
        }

        $this->addToSummary('business', $user, $profile->verification_status->value);
    }

    /**
     * @param  array{phone: string, name: string, national_id_number: string, vehicle_type: string, vehicle: array{plate_number: ?string, model: string, color: string, year: int}, status: TayarStatus, reason: ?string}  $tayar
     */
    private function seedTayar(array $tayar, string $password, User $reviewer): void
    {
        $user = $this->customer($tayar['phone'], $tayar['name'], $password, RegisterAs::Tayar);

        if ($user === null) {
            return;
        }

        $profile = $user->tayarProfile ?? $this->submitTayarApplication($user, $tayar);

        if ($profile->isPending() && $tayar['status'] === TayarStatus::Approved) {
            $profile = app(ApproveTayarApplicationAction::class)->handle($profile, $reviewer);
        }

        if ($profile->isPending() && $tayar['status'] === TayarStatus::Rejected) {
            $profile = app(RejectTayarApplicationAction::class)->handle($profile, $reviewer, $tayar['reason']);
        }

        if ($profile->isApproved() && $user->last_mode !== AppMode::Tayar) {
            app(SwitchAppModeAction::class)->handle($user, AppMode::Tayar);
        }

        $this->addToSummary('tayar', $user, $profile->status->value);
    }

    /**
     * Placeholder documents are only written here, so a re-run with an existing application writes no temp files.
     *
     * @param  array{phone: string, name: string, national_id_number: string, vehicle_type: string, vehicle: array{plate_number: ?string, model: string, color: string, year: int}, status: TayarStatus, reason: ?string}  $tayar
     */
    private function submitTayarApplication(User $user, array $tayar): TayarProfile
    {
        $vehicleType = $this->vehicleType($tayar['vehicle_type']);
        $documents = [
            TayarDocument::NationalIdFront->value => $this->placeholderFile('national-id-front.png'),
            TayarDocument::NationalIdBack->value => $this->placeholderFile('national-id-back.png'),
            TayarDocument::CriminalRecord->value => $this->placeholderFile('criminal-record.pdf'),
            TayarDocument::PersonalPhoto->value => $this->placeholderFile('personal-photo.png'),
        ];

        if ($vehicleType->requires_license) {
            $documents[TayarDocument::DrivingLicense->value] = $this->placeholderFile('driving-license.png');
        }

        return app(SubmitTayarApplicationAction::class)->handle(
            $user,
            $tayar['national_id_number'],
            ['vehicle_type_id' => $vehicleType->id, ...$tayar['vehicle']],
            $documents,
            [
                'vehicle_license' => $vehicleType->requires_license ? $this->placeholderFile('vehicle-license.png') : null,
                'vehicle_photos' => [$this->placeholderFile('vehicle-front.png'), $this->placeholderFile('vehicle-side.png')],
            ],
        );
    }

    /**
     * A customer as RegisterUserAction makes one, with the phone verified as VerifyPhoneAction does; no OTP is sent.
     * Null when the phone belongs to another kind of account.
     */
    private function customer(string $phone, string $name, string $password, RegisterAs $registerAs): ?User
    {
        $phoneNumber = PhoneNumber::from($phone);
        $user = User::query()->where('phone', $phoneNumber->value)->first();

        if ($user !== null) {
            if ($user->register_as !== $registerAs) {
                $this->command?->warn("Skipped {$name}: {$phoneNumber->value} already belongs to another account.");

                return null;
            }

            return $user;
        }

        $user = new User([
            'name' => $name,
            'phone' => $phoneNumber->value,
            'password' => $password,
            'account_type' => $registerAs->accountType(),
            'locale' => Locale::Ar,
        ]);
        $user->forceFill(['register_as' => $registerAs, 'phone_verified_at' => now()])->save();

        // Refreshed so the database defaults (status, last_mode) are loaded.
        return $user->refresh();
    }

    private function addToSummary(string $role, User $user, string $status): void
    {
        $nextStep = $user->fresh(['businessProfile', 'tayarProfile'])->onboardingStep()->value;

        $this->summary[] = [$role, $user->name, $user->phone, $status, $nextStep];
    }

    private function vehicleType(string $englishName): VehicleType
    {
        return VehicleType::query()->whereNameIs('en', $englishName)->first()
            ?? throw new RuntimeException("Demo data needs the vehicle type [{$englishName}] from VehicleTypeSeeder.");
    }

    /**
     * A tiny PNG or PDF written to a temporary file; Media Library moves it onto the private disk.
     */
    protected function placeholderFile(string $fileName): UploadedFile
    {
        $isPdf = str_ends_with($fileName, '.pdf');
        $path = tempnam(sys_get_temp_dir(), 'tayari-demo-');
        file_put_contents($path, $isPdf ? self::PDF : base64_decode(self::PNG_BASE64));

        return new UploadedFile($path, $fileName, $isPdf ? 'application/pdf' : 'image/png', null, true);
    }
}
