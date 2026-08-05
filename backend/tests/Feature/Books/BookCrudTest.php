<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function can_list_books(): void
    {
        Sanctum::actingAs($this->user);
        Book::factory()->count(5)->create();

        $response = $this->getJson('/api/books');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'isbn', 'author', 'stock'],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ])
            ->assertJsonCount(5, 'data');
    }

    #[Test]
    public function can_create_a_book(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'title' => 'Clean Code',
            'isbn' => '9780132350884',
            'author' => 'Robert C. Martin',
            'description' => 'A handbook of agile software craftsmanship',
            'purchase_price' => 25.50,
            'sale_price' => 45.00,
            'stock' => 10,
        ];

        $response = $this->postJson('/api/books', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Clean Code'])
            ->assertJsonFragment(['isbn' => '9780132350884']);

        $this->assertDatabaseHas('books', ['title' => 'Clean Code']);
    }

    #[Test]
    public function cannot_create_book_without_authentication(): void
    {
        $response = $this->postJson('/api/books', [
            'title' => 'Clean Code',
            'isbn' => '9780132350884',
            'author' => 'Robert C. Martin',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function create_book_validates_required_fields(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/books', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'isbn', 'author']);
    }

    #[Test]
    public function create_book_validates_unique_isbn(): void
    {
        Sanctum::actingAs($this->user);
        Book::factory()->create(['isbn' => '9780132350884']);

        $response = $this->postJson('/api/books', [
            'title' => 'Another Book',
            'isbn' => '9780132350884',
            'author' => 'Some Author',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);
    }

    #[Test]
    public function can_show_a_book(): void
    {
        Sanctum::actingAs($this->user);
        $book = Book::factory()->create();

        $response = $this->getJson("/api/books/{$book->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $book->id])
            ->assertJsonFragment(['title' => $book->title]);
    }

    #[Test]
    public function can_update_a_book(): void
    {
        Sanctum::actingAs($this->user);
        $book = Book::factory()->create();

        $response = $this->putJson("/api/books/{$book->id}", [
            'title' => 'Updated Title',
            'stock' => 50,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated Title'])
            ->assertJsonFragment(['stock' => 50]);

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'Updated Title']);
    }

    #[Test]
    public function can_delete_a_book(): void
    {
        Sanctum::actingAs($this->user);
        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/books/{$book->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('books', ['id' => $book->id]);
    }

    #[Test]
    public function can_search_books(): void
    {
        Sanctum::actingAs($this->user);
        Book::factory()->create(['title' => 'Laravel Up and Running']);
        Book::factory()->create(['title' => 'Vue.js in Action']);
        Book::factory()->create(['title' => 'PHP Cookbook']);

        $response = $this->getJson('/api/books?search=laravel');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Laravel Up and Running']);
    }

    #[Test]
    public function can_get_low_stock_books(): void
    {
        Sanctum::actingAs($this->user);
        Book::factory()->count(3)->create(['stock' => 50]);
        Book::factory()->lowStock()->count(2)->create();

        $response = $this->getJson('/api/books/low-stock');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
