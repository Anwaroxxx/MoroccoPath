<?php

namespace Tests\Feature\Explore;

use App\Models\Campus;
use App\Models\Career;
use App\Models\CareerPath;
use App\Models\EducationLevel;
use App\Models\Field;
use App\Models\Institution;
use App\Models\Program;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompareAndPathsTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'Compare test school',
            'canonical_name' => 'Compare test school',
            'slug' => 'compare-test-school',
            'type' => 'coding_school',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $this->institution->id,
            'name' => 'HQ', 'city' => 'Casablanca', 'region' => 'Casablanca-Settat',
        ]);
    }

    private function makeProgram(string $slug, string $status = 'published'): Program
    {
        return Program::create([
            'institution_id' => $this->institution->id,
            'name' => ucfirst($slug),
            'slug' => $slug,
            'study_mode' => 'full_time',
            'status' => $status,
        ]);
    }

    public function test_compare_shows_only_requested_published_programs(): void
    {
        $this->makeProgram('alpha');
        $this->makeProgram('beta');
        $this->makeProgram('gamma-draft', 'draft');

        $this->get('/compare?programs=alpha,beta,gamma-draft')
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('compare')
                    ->has('rows', 2)
                    ->where('rows.0.slug', 'alpha'),
            );
    }

    public function test_compare_is_limited_to_three_programs(): void
    {
        foreach (['one', 'two', 'three', 'four'] as $slug) {
            $this->makeProgram($slug);
        }

        $this->get('/compare?programs=one,two,three,four')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('rows', 3));
    }

    public function test_demo_career_path_renders_with_branches(): void
    {
        $this->seedTaxonomy();
        (new DemoContentSeeder)->run();

        $path = CareerPath::query()->where('slug', 'demo-software-developer')->firstOrFail();

        $this->get('/paths')
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->where('paths.0.slug', 'demo-software-developer'),
            );

        $this->get("/paths/{$path->slug}")
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('paths/[id]')
                    ->has('path.steps', 2) // two entry points
                    ->where('path.steps.0.children.0.children.0.title', '[DEMO] Junior developer'),
            );
    }

    private function seedTaxonomy(): void
    {
        Field::query()->firstOrCreate(['code' => 'TECHNOLOGY'], ['name' => 'Technology']);
        Career::query()->firstOrCreate(['code' => 'SOFTWARE_DEVELOPER'], ['name' => '[DEMO] Software Developer']);
        EducationLevel::query()->firstOrCreate(
            ['code' => 'NIVEAU_BAC'],
            ['name' => 'Niveau Bac', 'category' => 'academic', 'rank' => 20],
        );
        EducationLevel::query()->firstOrCreate(
            ['code' => 'BAC'],
            ['name' => 'Baccalauréat', 'category' => 'academic', 'rank' => 30],
        );
    }
}
