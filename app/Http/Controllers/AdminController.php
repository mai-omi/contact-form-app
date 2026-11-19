<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        return view('admin.index', [
            'contacts' => Contact::with(['category', 'tags'])->latest()->paginate(7),
            'categories' => Category::all(),
            'tags' => Tag::all(),
        ]);
    }
}
