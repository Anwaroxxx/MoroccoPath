<?php

namespace Tests\Unit\Ingestion;

use App\Enums\VerificationStatus;
use App\Models\Campus;
use App\Models\Institution;
use App\Models\Program;
use App\Models\Source;
use App\Models\SourceReference;
use App\Services\Ingestion\SourceVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class SourceVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private Source $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->source = Source::create([
            'name' => 'Reverify test source',
            'slug' => 'reverify-test-source',
            'type' => 'official_institution',
            'trust_level' => 2,
        ]);
    }

    private function makeReference(string $status, ?string $verifiedAt): SourceReference
    {
        $institution = Institution::create([
            'name' => uniqid('inst-'),
            'canonical_name' => uniqid('inst-'),
            'slug' => uniqid('inst-'),
            'type' => 'coding_school',
            'status' => 'unknown',
        ]);

        Campus::create([
            'institution_id' => $institution->id,
            'name' => 'HQ', 'city' => 'X', 'region' => 'Y',
        ]);

        Program::create([
            'institution_id' => $institution->id,
            'name' => uniqid('prog-'), 'slug' => uniqid('prog-'),
            'study_mode' => 'full_time', 'status' => 'draft',
        ]);

        return SourceReference::create([
            'source_id' => $this->source->id,
            'referencable_type' => Institution::class,
            'referencable_id' => $institution->id,
            'academic_year' => '2026/2027',
            'verification_status' => $status,
            'last_verified_at' => $verifiedAt,
        ]);
    }

    public function test_old_verified_references_expire(): void
    {
        $old = $this->makeReference('verified', '2025-01-01');
        $fresh = $this->makeReference('verified', '2026-08-01');

        $result = (new SourceVerificationService(180))->refresh(Date::parse('2026-08-23'));

        $this->assertSame(1, $result['expired']);
        $this->assertSame(VerificationStatus::Expired, $old->refresh()->verification_status);
        $this->assertSame(VerificationStatus::Verified, $fresh->refresh()->verification_status);
    }

    public function test_verified_without_date_expires(): void
    {
        $reference = $this->makeReference('verified', null);

        (new SourceVerificationService(180))->refresh(Date::parse('2026-08-23'));

        $this->assertSame(VerificationStatus::Expired, $reference->refresh()->verification_status);
    }

    public function test_human_required_statuses_are_untouched(): void
    {
        $needsReview = $this->makeReference('needs_review', '2020-01-01');
        $conflicting = $this->makeReference('conflicting', '2020-01-01');
        $unknown = $this->makeReference('unknown', null);

        (new SourceVerificationService(180))->refresh(Date::parse('2026-08-23'));

        $this->assertSame(VerificationStatus::NeedsReview, $needsReview->refresh()->verification_status);
        $this->assertSame(VerificationStatus::Conflicting, $conflicting->refresh()->verification_status);
        $this->assertSame(VerificationStatus::Unknown, $unknown->refresh()->verification_status);
    }
}
