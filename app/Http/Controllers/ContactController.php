<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('contact.index', [
            'categories' => Category::all(),
            'tags' => Tag::all(),
        ]);
    }

    public function confirm(StoreContactRequest $request): View
    {
        $validated = $request->validated();

        $category = Category::findOrFail($validated['category_id']);
        $tags = Tag::whereIn('id', $validated['tag_ids'] ?? [])->get();

        return view('contact.confirm', [
            'validated' => $validated,
            'category' => $category,
            'tags' => $tags,
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $contact = Contact::create($validated);

            if (! empty($validated['tag_ids'])) {
                $contact->tags()->attach($validated['tag_ids']);
            }
        });

        return redirect('/thanks');
    }
}
