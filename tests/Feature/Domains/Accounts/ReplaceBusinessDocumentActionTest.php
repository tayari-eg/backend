<?php

namespace Tests\Feature\Domains\Accounts;

use App\Domains\Accounts\Actions\ReplaceBusinessDocumentAction;
use App\Domains\Accounts\Enums\DocumentCollection;
use App\Domains\Accounts\Enums\VerificationStatus;
use App\Domains\Accounts\Models\BusinessProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Tests\TestCase;

class ReplaceBusinessDocumentActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_failed_upload_leaves_the_status_and_the_existing_file_unchanged(): void
    {
        Storage::fake('local');
        $profile = BusinessProfile::factory()->approved()->create();
        $existing = $profile->addMedia(UploadedFile::fake()->create('tax-card.pdf', 10, 'application/pdf'))->toMediaCollection('tax_card');
        $brokenFile = UploadedFile::fake()->create('new-tax-card.pdf', 10, 'application/pdf');
        unlink($brokenFile->getPathname());

        try {
            app(ReplaceBusinessDocumentAction::class)->handle($profile, DocumentCollection::TaxCard, $brokenFile);
            $this->fail('Storing the document was expected to fail.');
        } catch (FileDoesNotExist) {
            // Expected: the file is missing.
        }

        $this->assertSame(VerificationStatus::Approved, $profile->refresh()->verification_status);
        $this->assertSame([$existing->getPathRelativeToRoot()], Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('media', 1);
    }
}
