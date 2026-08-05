<?php

namespace Tests\Unit\Services;

use App\Http\Requests\StoreBookRequest;
use App\Models\Book;
use App\Services\BookService;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookService $bookService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookService = app(BookService::class);
    }

    #[Test]
    public function can_list_books_with_pagination(): void
    {
        Book::factory()->count(25)->create();

        $result = $this->bookService->list(['per_page' => 10]);

        $this->assertCount(10, $result->items());
        $this->assertEquals(25, $result->total());
        $this->assertEquals(3, $result->lastPage());
    }

    #[Test]
    public function can_filter_books_by_search(): void
    {
        Book::factory()->create(['title' => 'Laravel Guide']);
        Book::factory()->create(['title' => 'Vue Guide']);
        Book::factory()->create(['title' => 'PHP Manuel']);

        $result = $this->bookService->list(['search' => 'Laravel']);

        $this->assertCount(1, $result->items());
        $this->assertEquals('Laravel Guide', $result->items()[0]->title);
    }

    #[Test]
    public function can_get_low_stock_books(): void
    {
        Book::factory()->count(3)->create(['stock' => 50]);
        Book::factory()->lowStock()->count(2)->create();

        $result = $this->bookService->getLowStock();

        $this->assertCount(2, $result);
    }

    #[Test]
    public function book_has_correct_defaults(): void
    {
        $book = Book::factory()->create();

        $this->assertNotNull($book->title);
        $this->assertNotNull($book->isbn);
        $this->assertGreaterThanOrEqual(0, $book->purchase_price);
        $this->assertGreaterThanOrEqual(0, $book->sale_price);
    }
}
