<?php

namespace Tests\Feature\Domains\Accounts;

use App\Domains\Accounts\Actions\SubmitNationalIdAction;
use App\Domains\Accounts\Events\NationalIdSubmitted;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Tests\TestCase;

class SubmitNationalIdActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_failed_back_side_removes_the_new_front_and_keeps_the_review(): void
    {
        Storage::fake('local');
        Event::fake([NationalIdSubmitted::class]);
        $reviewer = User::factory()->create();
        $user = User::factory()->create(['id_rejection_reason' => 'Blurry photo', 'id_reviewed_by' => $reviewer->id, 'id_reviewed_at' => now()]);
        $brokenBack = UploadedFile::fake()->create('back.pdf', 10, 'application/pdf');
        unlink($brokenBack->getPathname());

        try {
            app(SubmitNationalIdAction::class)->handle($user, UploadedFile::fake()->create('front.pdf', 10, 'application/pdf'), $brokenBack);
            $this->fail('Storing the back side was expected to fail.');
        } catch (FileDoesNotExist) {
            // Expected: the back file is missing.
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('media', 0);
        $this->assertSame('Blurry photo', $user->refresh()->id_rejection_reason);
        Event::assertNotDispatched(NationalIdSubmitted::class);
    }
}
