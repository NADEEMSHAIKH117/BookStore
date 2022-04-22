<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Feedback extends Model
{
    use HasFactory;
    protected $table = 'feedback';
    protected $fillable = [
        'User_feedback',
        'Book_id',
        'rating'
    ];

    public function saveFeedback($request, $currentUser)
    {
        $feedback = new Feedback();
        $feedback->User_feedback = $request->input('User_feedback');
        $feedback->user_id = $currentUser->id;

        return $feedback;
    }

    public function saveRating($request, $currentUser)
    {
        $rating = new Feedback();
        $rating->book_id = $request->input('book_id');
        $rating->rating = $request->input('rating');
        $rating->user_id = $currentUser->id;

        return $rating;
    }

    public function avgRating($book_id){
        return Feedback::where('feedback.book_id', $book_id)
                         ->pluck('rating')->avg();                 
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
