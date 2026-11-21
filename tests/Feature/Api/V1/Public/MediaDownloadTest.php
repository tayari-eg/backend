<?php

namespace Tests\Feature\Api\V1\Public;

use App\Domains\Accounts\Enums\DocumentCollection;
use App\Domains\Accounts\Enums\StaffRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class MediaDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function userWithNationalId(): User
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $user->addMedia(UploadedFile::fake()->create('front.pdf', 10, 'application/pdf'))->toMediaCollection(DocumentCollection::NationalIdFront->value);

        return $user;
    }

    public function test_the_owner_gets_a_short_signed_url_that_streams_the_file(): void
    {
        $user = $this->userWithNationalId();
        Sanctum::actingAs($user);

        $url = $this->getJson(route('api.v1.auth.me'))->json('data.national_id.front.url');

        $this->assertNotNull($url);
        $this->assertStringContainsString('signature=', $url);
        $this->assertStringContainsString('expires=', $url);
        $this->get($url)->assertOk();
    }

    public function test_a_tampered_or_unsigned_url_is_rejected(): void
    {
        $user = $this->userWithNationalId();
        $media = $user->getFirstMedia(DocumentCollection::NationalIdFront->value);

        $this->getJson(route('api.v1.public.media.show', ['media' => $media->id]))->assertForbidden();
        $this->getJson(route('api.v1.public.media.show', ['media' => $media->id, 'signature' => 'forged']))->assertForbidden();
    }

    public function test_an_expired_url_is_rejected(): void
    {
        $user = $this->userWithNationalId();
        Sanctum::actingAs($user);
        $url = $this->getJson(route('api.v1.auth.me'))->json('data.national_id.front.url');

        $this->travel(config('tayari.media.signed_url_minutes') + 1)->minutes();

        $this->getJson($url)->assertForbidden();
    }

    public function test_the_files_live_on_the_private_disk(): void
    {
        $user = $this->userWithNationalId();

        $media = $user->getFirstMedia(DocumentCollection::NationalIdFront->value);

        $this->assertSame('local', $media->disk);
        Storage::disk('local')->assertExists($media->getPathRelativeToRoot());
    }

    public function test_only_the_owner_and_staff_with_permission_may_get_a_url(): void
    {
        $owner = $this->userWithNationalId();
        $media = Media::query()->sole();
        $stranger = User::factory()->create();
        $support = $this->createStaffUser(StaffRole::Support);

        $this->assertTrue($owner->can('view', $media));
        $this->assertFalse($stranger->can('view', $media));
        $this->assertTrue($support->can('view', $media));
    }
}
