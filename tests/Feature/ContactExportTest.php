<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactExportTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function csvRows(string $content): array
    {
        $withoutBom = substr($content, 3);
        $normalized = str_replace("\r\n", "\n", $withoutBom);
        $lines = array_values(array_filter(explode("\n", $normalized), fn ($line) => $line !== ''));

        return array_map('str_getcsv', $lines);
    }

    public function test_guest_cannot_export_and_is_redirected_to_login(): void
    {
        $response = $this->get('/contacts/export');

        $response->assertRedirect('/login');
    }

    public function test_export_without_filters_returns_all_contacts_in_descending_order(): void
    {
        $this->actingAsAdmin();
        $older = Contact::factory()->create(['created_at' => '2026-01-01']);
        $newer = Contact::factory()->create(['created_at' => '2026-02-01']);

        $response = $this->get('/contacts/export');

        $response->assertOk();
        $rows = $this->csvRows($response->streamedContent());
        $this->assertSame((string) $newer->id, $rows[1][0]);
        $this->assertSame((string) $older->id, $rows[2][0]);
    }

    public function test_export_filters_by_keyword(): void
    {
        $this->actingAsAdmin();
        $match = Contact::factory()->create(['first_name' => '山田']);
        Contact::factory()->create(['first_name' => '鈴木']);

        $response = $this->get('/contacts/export?keyword=山田');

        $rows = $this->csvRows($response->streamedContent());
        $this->assertCount(2, $rows);
        $this->assertSame((string) $match->id, $rows[1][0]);
    }

    public function test_export_filters_by_gender(): void
    {
        $this->actingAsAdmin();
        $match = Contact::factory()->create(['gender' => 2]);
        Contact::factory()->create(['gender' => 1]);

        $response = $this->get('/contacts/export?gender=2');

        $rows = $this->csvRows($response->streamedContent());
        $this->assertCount(2, $rows);
        $this->assertSame((string) $match->id, $rows[1][0]);
    }

    public function test_export_filters_by_category(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        $match = Contact::factory()->create(['category_id' => $category->id]);
        Contact::factory()->create();

        $response = $this->get('/contacts/export?category_id='.$category->id);

        $rows = $this->csvRows($response->streamedContent());
        $this->assertCount(2, $rows);
        $this->assertSame((string) $match->id, $rows[1][0]);
    }

    public function test_export_filters_by_date(): void
    {
        $this->actingAsAdmin();
        $match = Contact::factory()->create(['created_at' => '2026-03-15 10:00:00']);
        Contact::factory()->create(['created_at' => '2026-03-16 10:00:00']);

        $response = $this->get('/contacts/export?date=2026-03-15');

        $rows = $this->csvRows($response->streamedContent());
        $this->assertCount(2, $rows);
        $this->assertSame((string) $match->id, $rows[1][0]);
    }

    public function test_export_combines_multiple_filters(): void
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

        $response = $this->get('/contacts/export?keyword=山田&gender=1&category_id='.$category->id);

        $rows = $this->csvRows($response->streamedContent());
        $this->assertCount(2, $rows);
        $this->assertSame((string) $match->id, $rows[1][0]);
    }

    public function test_export_header_row_matches_expected_columns(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/contacts/export');

        $rows = $this->csvRows($response->streamedContent());
        $this->assertSame(
            ['ID', '氏名', '性別', 'メールアドレス', '電話番号', '住所', 'お問い合わせの種類', '内容', '作成日'],
            $rows[0]
        );
    }

    public function test_export_body_starts_with_utf8_bom(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/contacts/export');

        $this->assertStringStartsWith("\xEF\xBB\xBF", $response->streamedContent());
    }

    public function test_export_row_formats_name_gender_and_category_as_strings(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create(['content' => '商品トラブル']);
        Contact::factory()->create([
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/contacts/export');

        $rows = $this->csvRows($response->streamedContent());
        $this->assertSame('山田 太郎', $rows[1][1]);
        $this->assertSame('男性', $rows[1][2]);
        $this->assertSame('商品トラブル', $rows[1][6]);
    }

    public function test_export_escapes_fields_that_look_like_spreadsheet_formulas(): void
    {
        $this->actingAsAdmin();
        Contact::factory()->create([
            'detail' => '=HYPERLINK("http://evil.example")',
            'address' => '+81-90-1234-5678',
        ]);

        $response = $this->get('/contacts/export');

        $rows = $this->csvRows($response->streamedContent());
        $this->assertSame("'=HYPERLINK(\"http://evil.example\")", $rows[1][7]);
        $this->assertSame("'+81-90-1234-5678", $rows[1][5]);
    }

    public function test_export_redirects_to_admin_without_filtering_when_gender_is_invalid(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/contacts/export?gender=9');

        $response->assertRedirect('/admin');
        $response->assertSessionDoesntHaveErrors();
    }

    public function test_export_redirects_to_admin_without_filtering_when_category_id_does_not_exist(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/contacts/export?category_id=999999');

        $response->assertRedirect('/admin');
        $response->assertSessionDoesntHaveErrors();
    }

    public function test_export_redirects_to_admin_without_filtering_when_date_is_invalid(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/contacts/export?date=not-a-date');

        $response->assertRedirect('/admin');
        $response->assertSessionDoesntHaveErrors();
    }

    public function test_export_does_not_include_tags_column(): void
    {
        $this->actingAsAdmin();
        $contact = Contact::factory()->create();
        $tag = Tag::factory()->create();
        $contact->tags()->attach($tag->id);

        $response = $this->get('/contacts/export');

        $rows = $this->csvRows($response->streamedContent());
        $this->assertCount(9, $rows[0]);
    }
}
