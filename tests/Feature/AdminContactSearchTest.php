<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminContactSearchTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_index_searches_by_first_name_keyword(): void
    {
        $this->actingAsAdmin();
        $match = Contact::factory()->create(['first_name' => '山田']);
        Contact::factory()->create(['first_name' => '鈴木']);

        $response = $this->get('/admin?keyword=山田');

        $response->assertOk();
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->pluck('id')->all() === [$match->id]);
    }

    public function test_index_searches_by_last_name_keyword(): void
    {
        $this->actingAsAdmin();
        $match = Contact::factory()->create(['last_name' => '太郎']);
        Contact::factory()->create(['last_name' => '花子']);

        $response = $this->get('/admin?keyword=太郎');

        $response->assertOk();
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->pluck('id')->all() === [$match->id]);
    }

    public function test_index_searches_by_email_keyword(): void
    {
        $this->actingAsAdmin();
        $match = Contact::factory()->create(['email' => 'yamada@example.com']);
        Contact::factory()->create(['email' => 'suzuki@example.com']);

        $response = $this->get('/admin?keyword=yamada');

        $response->assertOk();
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->pluck('id')->all() === [$match->id]);
    }

    public function test_index_treats_underscore_in_keyword_as_a_literal_character(): void
    {
        $this->actingAsAdmin();
        Contact::factory()->create(['email' => 'yamada_1@example.com']);
        $shouldNotMatch = Contact::factory()->create(['email' => 'yamadaX1@example.com']);

        $response = $this->get('/admin?keyword=yamada_1');

        $response->assertOk();
        $response->assertViewHas('contacts', fn ($contacts) => ! $contacts->pluck('id')->contains($shouldNotMatch->id));
    }

    public function test_index_returns_no_results_when_keyword_matches_nothing(): void
    {
        $this->actingAsAdmin();
        Contact::factory()->create(['first_name' => '山田']);

        $response = $this->get('/admin?keyword=該当なし');

        $response->assertOk();
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->total() === 0);
    }

    public function test_index_filters_by_gender(): void
    {
        $this->actingAsAdmin();
        $match = Contact::factory()->create(['gender' => 2]);
        Contact::factory()->create(['gender' => 1]);

        $response = $this->get('/admin?gender=2');

        $response->assertOk();
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->pluck('id')->all() === [$match->id]);
    }

    public function test_index_does_not_filter_by_gender_when_zero(): void
    {
        $this->actingAsAdmin();
        Contact::factory()->create(['gender' => 1]);
        Contact::factory()->create(['gender' => 2]);

        $response = $this->get('/admin?gender=0');

        $response->assertOk();
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->total() === 2);
    }

    public function test_index_does_not_filter_by_gender_when_omitted(): void
    {
        $this->actingAsAdmin();
        Contact::factory()->create(['gender' => 1]);
        Contact::factory()->create(['gender' => 2]);

        $response = $this->get('/admin');

        $response->assertOk();
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->total() === 2);
    }

    public function test_index_filters_by_category(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        $match = Contact::factory()->create(['category_id' => $category->id]);
        Contact::factory()->create();

        $response = $this->get('/admin?category_id='.$category->id);

        $response->assertOk();
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->pluck('id')->all() === [$match->id]);
    }

    public function test_index_filters_by_date(): void
    {
        $this->actingAsAdmin();
        $match = Contact::factory()->create(['created_at' => '2026-03-15 10:00:00']);
        Contact::factory()->create(['created_at' => '2026-03-16 10:00:00']);

        $response = $this->get('/admin?date=2026-03-15');

        $response->assertOk();
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->pluck('id')->all() === [$match->id]);
    }

    public function test_index_combines_multiple_filters_with_and(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        $match = Contact::factory()->create([
            'first_name' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
        ]);
        Contact::factory()->create([
            'first_name' => '山田',
            'gender' => 2,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/admin?keyword=山田&gender=1&category_id='.$category->id);

        $response->assertOk();
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->pluck('id')->all() === [$match->id]);
    }

    public function test_index_redirects_to_admin_without_filtering_when_gender_is_invalid(): void
    {
        $this->actingAsAdmin();
        Contact::factory()->count(2)->create();

        $response = $this->get('/admin?gender=9');

        $response->assertRedirect('/admin');
        $response->assertSessionDoesntHaveErrors();
    }

    public function test_index_redirects_to_admin_without_filtering_when_category_id_does_not_exist(): void
    {
        $this->actingAsAdmin();
        Contact::factory()->count(2)->create();

        $response = $this->get('/admin?category_id=999999');

        $response->assertRedirect('/admin');
        $response->assertSessionDoesntHaveErrors();
    }

    public function test_index_redirects_to_admin_without_filtering_when_date_is_invalid(): void
    {
        $this->actingAsAdmin();
        Contact::factory()->count(2)->create();

        $response = $this->get('/admin?date=not-a-date');

        $response->assertRedirect('/admin');
        $response->assertSessionDoesntHaveErrors();
    }

    public function test_reset_link_returns_to_unfiltered_initial_state(): void
    {
        $this->actingAsAdmin();
        Contact::factory()->count(3)->create();

        $response = $this->get('/admin');

        $response->assertOk();
        $response->assertSee('href="/admin"', false);
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->total() === 3);
    }

    public function test_index_eager_loads_category_and_tags_to_avoid_n_plus_one(): void
    {
        $this->actingAsAdmin();
        Contact::factory()->count(7)->create();

        DB::enableQueryLog();
        $this->get('/admin')->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(15, $queryCount);
    }

    public function test_index_passes_categories_and_tags_to_view(): void
    {
        $this->actingAsAdmin();
        Category::factory()->count(2)->create();

        $response = $this->get('/admin');

        $response->assertOk();
        $response->assertViewHas('categories', fn ($categories) => $categories->count() === 2);
        $response->assertViewHas('tags');
    }
}
