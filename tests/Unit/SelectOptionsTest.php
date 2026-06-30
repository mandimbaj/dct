<?php

namespace Tests\Unit;

use App\Support\SelectOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SelectOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_select_options', function ($table): void {
            $table->id();
            $table->string('code')->nullable();
        });

        Schema::create('test_select_option_translations', function ($table): void {
            $table->id();
            $table->foreignId('master_id');
            $table->string('language_code');
            $table->string('name');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_select_option_translations');
        Schema::dropIfExists('test_select_options');

        parent::tearDown();
    }

    public function test_database_search_finds_a_word_anywhere_in_translated_option_names(): void
    {
        $institutional = TestSelectOption::query()->create(['code' => 'IND-001']);
        $institutional->translations()->create([
            'language_code' => 'en',
            'name' => 'Institutional maternal mortality ratio',
        ]);

        $maternalReview = TestSelectOption::query()->create(['code' => 'IND-002']);
        $maternalReview->translations()->create([
            'language_code' => 'en',
            'name' => 'Maternal death review coverage',
        ]);

        $childHealth = TestSelectOption::query()->create(['code' => 'IND-003']);
        $childHealth->translations()->create([
            'language_code' => 'en',
            'name' => 'Child health coverage',
        ]);

        $this->assertSame([
            $institutional->getKey() => 'Institutional maternal mortality ratio',
            $maternalReview->getKey() => 'Maternal death review coverage',
        ], SelectOptions::fromDisplayNameQuery(TestSelectOption::query(), 'maternal'));
    }

    public function test_array_search_finds_location_names_that_contain_the_word(): void
    {
        $this->assertSame([
            1 => 'Democratic Republic of Congo',
            2 => 'Republic of Congo',
        ], SelectOptions::filterAndSort([
            1 => 'Democratic Republic of Congo',
            3 => 'Kenya',
            2 => 'Republic of Congo',
        ], 'congo'));
    }
}

class TestSelectOption extends Model
{
    protected $table = 'test_select_options';

    protected $guarded = [];

    public $timestamps = false;

    public function translations(): HasMany
    {
        return $this->hasMany(TestSelectOptionTranslation::class, 'master_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return (string) ($this->translations->first()?->name ?? $this->code);
    }
}

class TestSelectOptionTranslation extends Model
{
    protected $table = 'test_select_option_translations';

    protected $guarded = [];

    public $timestamps = false;
}
