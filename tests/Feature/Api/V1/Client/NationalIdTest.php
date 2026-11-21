<?php

namespace Tests\Feature\Api\V1\Client;

use App\Domains\Accounts\Events\NationalIdSubmitted;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NationalIdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /**
     * @return array<string, UploadedFile>
     */
    private function files(): array
    {
        return [
            'national_id_front' => UploadedFile::fake()->image('front.jpg'),
            'national_id_back' => UploadedFile::fake()->image('back.jpg'),
        ];
    }

    public function test_a_client_uploads_both_sides_to_the_private_disk(): void
    {
        Event::fake([NationalIdSubmitted::class]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->post(route('api.v1.client.national-id.store'), $this->files(), ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.national_id.status', 'pending');

        foreach (['national_id_front', 'national_id_back'] as $collection) {
            $media = $user->refresh()->getFirstMedia($collection);
            $this->assertSame('local', $media->disk);
            Storage::disk('local')->assertExists($media->getPathRelativeToRoot());
        }
        Event::assertDispatched(NationalIdSubmitted::class);
    }

    public function test_a_new_upload_clears_the_previous_review(): void
    {
        $reviewer = User::factory()->create();
        $user = User::factory()->create([
            'id_rejection_reason' => 'Blurry photo',
            'id_reviewed_by' => $reviewer->id,
            'id_reviewed_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $this->post(route('api.v1.client.national-id.store'), $this->files(), ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.national_id.status', 'pending')
            ->assertJsonPath('data.national_id.rejection_reason', null);

        $user->refresh();
        $this->assertNull($user->id_rejection_reason);
        $this->assertNull($user->id_reviewed_by);
        $this->assertNull($user->id_reviewed_at);
        $this->assertNull($user->id_verified_at);
    }

    public function test_both_sides_are_required(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->post(route('api.v1.client.national-id.store'), ['national_id_front' => UploadedFile::fake()->image('front.jpg')], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['national_id_back']);
    }
}
