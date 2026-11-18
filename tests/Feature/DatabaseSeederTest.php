<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_creates_expected_record_counts(): void
    {
        $this->seed();

        $this->assertSame(1, User::count());
        $this->assertSame(5, Category::count());
        $this->assertSame(5, Tag::count());
        $this->assertSame(20, Contact::count());
    }

    public function test_seeded_admin_user_can_authenticate_with_known_credentials(): void
    {
        $this->seed();

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_seeded_contacts_have_between_one_and_three_tags(): void
    {
        $this->seed();

        foreach (Contact::all() as $contact) {
            $this->assertGreaterThanOrEqual(1, $contact->tags->count());
            $this->assertLessThanOrEqual(3, $contact->tags->count());
        }
    }

    public function test_seeded_contacts_have_a_valid_phone_number_format(): void
    {
        $this->seed();

        foreach (Contact::all() as $contact) {
            $this->assertMatchesRegularExpression('/^[0-9]{10,11}$/', $contact->tel);
        }
    }
}
