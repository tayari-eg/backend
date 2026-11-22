<?php

namespace Tests\Feature\Domains\Accounts;

use App\Domains\Accounts\Actions\CreateBusinessProfileAction;
use App\Domains\Accounts\Events\BusinessProfileSubmitted;
use App\Domains\Accounts\Models\BusinessType;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Tests\TestCase;

class CreateBusinessProfileActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_failed_second_document_removes_the_first_file_and_the_profile(): void
    {
        Storage::fake('local');
        Event::fake([BusinessProfileSubmitted::class]);
        $user = User::factory()->business()->create();
        $brokenBack = UploadedFile::fake()->create('back.pdf', 10, 'application/pdf');
        unlink($brokenBack->getPathname());

        try {
            app(CreateBusinessProfileAction::class)->handle(
                $user,
                ['business_type_id' => BusinessType::factory()->create()->id, 'business_name' => 'Koshary Corner'],
                [
                    'national_id_front' => UploadedFile::fake()->create('front.pdf', 10, 'application/pdf'),
                    'national_id_back' => $brokenBack,
                ],
                [['type' => 'website', 'url' => 'https://koshary.example']],
            );
            $this->fail('Storing the second document was expected to fail.');
        } catch (FileDoesNotExist) {
            // Expected: the second file is missing.
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('business_profiles', 0);
        $this->assertDatabaseCount('business_links', 0);
        $this->assertDatabaseCount('media', 0);
        Event::assertNotDispatched(BusinessProfileSubmitted::class);
    }

    public function test_a_double_submit_answers_like_the_sequential_duplicate(): void
    {
        Storage::fake('local');
        $user = User::factory()->business()->create();
        $attributes = ['business_type_id' => BusinessType::factory()->create()->id, 'business_name' => 'Koshary Corner'];
        app(CreateBusinessProfileAction::class)->handle($user, $attributes, [], []);

        // The policy check of the second request ran before the first one saved, so only the unique index stops it.
        try {
            app(CreateBusinessProfileAction::class)->handle($user, $attributes, [], []);
            $this->fail('A second profile for the same user was expected to fail.');
        } catch (AuthorizationException $exception) {
            $this->assertSame(__('business.profile_exists'), $exception->getMessage());
        }

        $this->assertDatabaseCount('business_profiles', 1);
    }
}
