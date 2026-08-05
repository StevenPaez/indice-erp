<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookCollection;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Services\BookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BookController
{
    public function __construct(
        private readonly BookService $bookService,
    ) {}

    public function index(Request $request): BookCollection
    {
        $books = $this->bookService->list($request->only([
            'search', 'low_stock', 'sort_by', 'sort_dir', 'per_page',
        ]));

        return BookCollection::make($books);
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = $this->bookService->create($request);

        return (new BookResource($book))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Book $book): BookResource
    {
        return new BookResource($book);
    }

    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        $book = $this->bookService->update($book, $request);

        return new BookResource($book);
    }

    public function destroy(Book $book): JsonResponse
    {
        $this->bookService->delete($book);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function lowStock(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return BookResource::collection($this->bookService->getLowStock());
    }
}
