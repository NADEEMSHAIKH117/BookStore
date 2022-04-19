<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class Book extends Model
{
    use HasFactory;

    protected $table = "books";
    protected $fillable = [
        'name',
        'description',
        'author',
        'image',
        'Price',
        'quantity'
    ];

    public function adminOrUserVerification($currentUserId)
    {
        $adminId = User::select('id')->where([['role', '=', 'admin'], ['id', '=', $currentUserId]])->get();
        return $adminId;
    }

    public function findBook($bookId)
    {
        $book = Book::where('id', $bookId)->first();
        return $book;
    }

    public function getBookDetails($bookName)
    {
        return Book::select('id', 'name', 'quantity', 'author', 'Price')
            ->where('name', '=', $bookName)
            ->first();
    }

    public function saveBookDetails($request, $currentUser)
    {
        $book = new Book();
        $path = Storage::disk('s3')->put('book_images', $request->image);
        $url = env('AWS_URL') . $path;
        $book->name = $request->input('name');
        $book->description = $request->input('description');
        $book->author = $request->input('author');
        $book->image = $url;
        $book->Price = $request->input('Price');
        $book->quantity = $request->input('quantity');
        $book->user_id = $currentUser->id;

        return $book;
    }

    public function searchBook($searchKey)
    {
        $userbooks = Book::leftJoin('carts', 'carts.book_id', '=', 'books.id')
                 ->select('books.id', 'books.name', 'books.description', 'books.author', 'books.image', 'books.Price', 'books.quantity')
                ->Where('books.name', 'like', '%' . $searchKey . '%')
                ->orWhere('books.author', 'like', '%' . $searchKey . '%')
                ->orWhere('books.Price', 'like', '%' . $searchKey . '%')
                ->get();

        return $userbooks; 
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ascendingOrder(){
        return Book::orderBy('Price')->get();
    }

    public function descendingOrder(){
        return Book::orderBy('Price', 'desc')->get();
    }
}
