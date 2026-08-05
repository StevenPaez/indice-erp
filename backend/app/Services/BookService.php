<?php

namespace App\Services;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BookService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Book::query()
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->search($term))
            ->when($filters['low_stock'] ?? false, fn ($q) => $q->where('stock', '<=', 10))
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_dir'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(StoreBookRequest $request): Book
    {
        return Book::create($request->validated());
    }

    public function update(Book $book, UpdateBookRequest $request): Book
    {
        $book->update($request->validated());
        return $book->fresh();
    }

    public function delete(Book $book): bool
    {
        return $book->delete();
    }

    public function getLowStock(): Collection
    {
        return Book::where('stock', '<=', 10)->get();
    }
}
