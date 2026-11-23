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
use App\Domains\Orders\Actions\AcceptOfferAction;
use App\Domains\Orders\Actions\CancelOrderAction;
use App\Domains\Orders\Actions\ConfirmPickupAction;
use App\Domains\Orders\Actions\ConfirmReceiverAction;
use App\Domains\Orders\Actions\CreateOrderAction;
use App\Domains\Orders\Actions\DispatchOrderOffersAction;
use App\Domains\Orders\Enums\OrderCanceller;
use App\Domains\Orders\Enums\OrderReasonType;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Enums\ShippingPaidBy;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderOffer;
use App\Domains\Orders\Models\OrderReason;
use App\Domains\Pricing\Actions\CalculateShippingFeeAction;
use App\Domains\Pricing\Actions\CreatePricingRuleAction;
use App\Domains\Pricing\Data\GeoPoint;
use App\Domains\Pricing\Models\PricingRule;
use App\Domains\Tayars\Actions\ApproveTayarApplicationAction;
use App\Domains\Tayars\Actions\GoOnlineAction;
use App\Domains\Tayars\Actions\RejectTayarApplicationAction;
use App\Domains\Tayars\Actions\SubmitTayarApplicationAction;
use App\Domains\Tayars\Data\WorkSessionSettings;
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
     * Orders of the approved business; the fixed idempotency keys make a second run find them instead of adding more.
     *
     * The demo tayar accepts the third (accepted_by_demo_tayar) and brings it on the way, while the first stays looking
     * for a tayar.
     *
     * @var list<array{idempotency_key: string, category: string, pickup_address: string, pickup: array{string, string}, dropoff_address: string, dropoff: array{string, string}, receiver_name: string, receiver_phone: string, goods_value: string, shipping_paid_by: ShippingPaidBy, details: string, cancel_reason: ?string, accepted_by_demo_tayar: bool}>
     */
    private const array ORDERS = [
        ['idempotency_key' => '6f1c2a8e-0d1b-4c3e-9a51-000000000001', 'category' => 'Electronics',
            'pickup_address' => 'Abbas El Akkad St, Nasr City', 'pickup' => ['30.0561000', '31.3402000'],
            'dropoff_address' => 'Makram Ebeid St, Nasr City', 'dropoff' => ['30.0606000', '31.3456000'],
            'receiver_name' => 'Demo Receiver One', 'receiver_phone' => '01000000020', 'goods_value' => '1500.00',
            'shipping_paid_by' => ShippingPaidBy::Receiver, 'details' => 'A boxed phone charger. Handle with care.', 'cancel_reason' => null,
            'accepted_by_demo_tayar' => false],
        ['idempotency_key' => '6f1c2a8e-0d1b-4c3e-9a51-000000000002', 'category' => 'Meals',
            'pickup_address' => 'Talaat Harb St, Downtown', 'pickup' => ['30.0480000', '31.2390000'],
            'dropoff_address' => '26th of July St, Zamalek', 'dropoff' => ['30.0617000', '31.2194000'],
            'receiver_name' => 'Demo Receiver Two', 'receiver_phone' => '01000000021', 'goods_value' => '240.00',
            'shipping_paid_by' => ShippingPaidBy::Sender, 'details' => 'Two lunch boxes.', 'cancel_reason' => 'I no longer need the order',
            'accepted_by_demo_tayar' => false],
        ['idempotency_key' => '6f1c2a8e-0d1b-4c3e-9a51-000000000003', 'category' => 'Papers',
            'pickup_address' => 'Mostafa El Nahas St, Nasr City', 'pickup' => ['30.0590000', '31.3350000'],
            'dropoff_address' => 'Hassan El Maamoun St, Nasr City', 'dropoff' => ['30.0650000', '31.3440000'],
            'receiver_name' => 'Demo Receiver Three', 'receiver_phone' => '01000000022', 'goods_value' => '0.00',
            'shipping_paid_by' => ShippingPaidBy::Sender, 'details' => 'A signed contract in an envelope.', 'cancel_reason' => null,
            'accepted_by_demo_tayar' => true],
    ];

    /**
     * Where the approved demo tayar goes online: the area mode in Nasr City, close to the demo pickups.
     *
     * @var array{lat: float, lng: float, area: string}
     */
    private const array DEMO_TAYAR_ONLINE = ['lat' => 30.0575, 'lng' => 31.3390, 'area' => 'Nasr City'];

    private ?User $ordersSender = null;

    private ?User $demoTayar = null;

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

        // After the tayars, so the approved demo tayar is online when the orders look for one.
        if ($this->ordersSender !== null) {
            $this->seedOrders($this->ordersSender);
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

        if (! OrderReason::query()->ofType(OrderReasonType::Cancel)->exists() || ! OrderReason::query()->ofType(OrderReasonType::NotDelivered)->exists()) {
            $this->call(OrderReasonSeeder::class);
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

        if ($business['status'] === VerificationStatus::Approved) {
            $this->ordersSender = $user;
        }

        $this->addToSummary('business', $user, $profile->verification_status->value);
    }

    /**
     * The order was offered to the online demo tayar when it was created; a re-run offers it again if that expired.
     */
    private function acceptAsDemoTayar(Order $order): void
    {
        app(DispatchOrderOffersAction::class)->handle($order);

        $offer = OrderOffer::query()->live()->where('order_id', $order->id)->where('tayar_id', $this->demoTayar->id)->first();

        if ($offer === null) {
            $this->command?->warn("Demo order {$order->code} was not offered to the demo tayar, so it was not accepted.");

            return;
        }

        app(AcceptOfferAction::class)->handle($offer, $this->demoTayar);
    }

    /**
     * Online in the area mode in Nasr City, so the demo orders there are offered to this tayar.
     */
    private function goOnlineAsDemoTayar(TayarProfile $profile): void
    {
        $settings = new WorkSessionSettings(
            Mode::query()->where('code', ModeCode::Area)->firstOrFail(),
            Area::query()->whereNameIs('en', self::DEMO_TAYAR_ONLINE['area'])->firstOrFail(),
        );

        app(GoOnlineAction::class)->handle($profile, self::DEMO_TAYAR_ONLINE['lat'], self::DEMO_TAYAR_ONLINE['lng'], $settings);
    }

    /**
     * Priced and created through the order Actions, so the fee follows the demo pricing rules and the matching offers
     * them to the online demo tayar; one is then cancelled by the business and one accepted by the demo tayar.
     */
    private function seedOrders(User $sender): void
    {
        foreach (self::ORDERS as $demoOrder) {
            $category = Category::query()->whereNotNull('parent_id')->whereNameIs('en', $demoOrder['category'])->firstOrFail();
            $pickup = new GeoPoint(...$demoOrder['pickup']);
            $dropoff = new GeoPoint(...$demoOrder['dropoff']);
            $quote = app(CalculateShippingFeeAction::class)->handle($pickup, $dropoff, $category);

            $order = app(CreateOrderAction::class)->handle($sender, [
                'idempotency_key' => $demoOrder['idempotency_key'],
                'category_id' => $category->id,
                'details' => $demoOrder['details'],
                'pickup_address' => $demoOrder['pickup_address'],
                'pickup_lat' => $pickup->lat,
                'pickup_lng' => $pickup->lng,
                'pickup_contact_phone' => null,
                'dropoff_address' => $demoOrder['dropoff_address'],
                'dropoff_lat' => $dropoff->lat,
                'dropoff_lng' => $dropoff->lng,
                'receiver_name' => $demoOrder['receiver_name'],
                'receiver_phone' => PhoneNumber::from($demoOrder['receiver_phone'])->value,
                'goods_value' => $demoOrder['goods_value'],
                'shipping_paid_by' => $demoOrder['shipping_paid_by']->value,
                'shipping_fee' => (string) $quote->shippingFee,
            ]);

            if ($demoOrder['cancel_reason'] !== null && $order->canBeCancelledBy(OrderCanceller::Sender)) {
                $reason = OrderReason::query()->ofType(OrderReasonType::Cancel)->where('name->en', $demoOrder['cancel_reason'])->firstOrFail();
                app(CancelOrderAction::class)->handle($order, $sender, $reason, 'Demo order cancelled by the business.');
            }

            if ($demoOrder['accepted_by_demo_tayar'] && $this->demoTayar !== null && $order->status === OrderStatus::LookingForTayar) {
                $this->acceptAsDemoTayar($order);
            }

            if ($demoOrder['accepted_by_demo_tayar'] && $this->demoTayar !== null) {
                $this->bringOnTheWay($order->refresh());
            }
        }
    }

    /**
     * The demo tayar picks the order up (placeholder photo) and the receiver confirms, so it is ongoing: the receiver's
     * codes are made by the queued SendReceiverCodes job and, with the log SMS driver, appear in the log in local only.
     */
    private function bringOnTheWay(Order $order): void
    {
        ['lat' => $lat, 'lng' => $lng] = self::DEMO_TAYAR_ONLINE;

        if ($order->status === OrderStatus::Pending && $order->tayar_id === $this->demoTayar->id) {
            $order = app(ConfirmPickupAction::class)->handle($order, $this->demoTayar, [$this->placeholderFile('pickup-photo.png')], $lat, $lng);
        }

        if ($order->status === OrderStatus::PickedUp && $order->tayar_id === $this->demoTayar->id) {
            app(ConfirmReceiverAction::class)->handle($order, $this->demoTayar, $lat, $lng);
        }
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

        if ($profile->isApproved()) {
            $this->goOnlineAsDemoTayar($profile);
            $this->demoTayar = $user;
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
