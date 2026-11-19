<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TagController extends Controller
{
    public function store(StoreTagRequest $request): RedirectResponse
    {
        try {
            Tag::create($request->validated());
        } catch (QueryException $e) {
            return $this->redirectBackWithDuplicateNameError($e);
        }

        return redirect('/admin');
    }

    public function edit(Tag $tag): View
    {
        return view('admin.tags.edit', ['tag' => $tag]);
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        try {
            $tag->update($request->validated());
        } catch (QueryException $e) {
            return $this->redirectBackWithDuplicateNameError($e);
        }

        return redirect('/admin');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return redirect('/admin');
    }

    private function redirectBackWithDuplicateNameError(QueryException $e): RedirectResponse
    {
        if ($e->getCode() !== '23000') {
            throw $e;
        }

        return back()->withInput()->withErrors(['name' => 'そのタグ名は既に使用されています']);
    }
}
