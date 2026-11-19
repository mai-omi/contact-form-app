<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'first_name',
        'last_name',
        'gender',
        'email',
        'tel',
        'address',
        'building',
        'detail',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'contact_tag');
    }

    public function scopeFilter(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = addcslashes($request->query('keyword'), '%_\\');

                $query->where(function ($query) use ($keyword) {
                    $query->where('first_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->when(
                $request->filled('gender') && (int) $request->query('gender') !== 0,
                fn ($query) => $query->where('gender', $request->query('gender'))
            )
            ->when(
                $request->filled('category_id'),
                fn ($query) => $query->where('category_id', $request->query('category_id'))
            )
            ->when(
                $request->filled('date'),
                fn ($query) => $query->whereDate('created_at', $request->query('date'))
            );
    }
}
