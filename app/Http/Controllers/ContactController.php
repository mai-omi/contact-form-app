<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function thanks(): View
    {
        return view('contact.thanks');
    }

    public function export(ExportContactRequest $request): StreamedResponse
    {
        $contacts = Contact::with('category')
            ->filter($request)
            ->latest()
            ->get();

        $genderLabels = [1 => '男性', 2 => '女性', 3 => 'その他'];

        return response()->streamDownload(function () use ($contacts, $genderLabels) {
            echo "\xEF\xBB\xBF";

            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['ID', '氏名', '性別', 'メールアドレス', '電話番号', '住所', '建物名', 'お問い合わせの種類', '内容', '作成日']);

            foreach ($contacts as $contact) {
                fputcsv($handle, [
                    $contact->id,
                    $this->sanitizeCsvField($contact->last_name.' '.$contact->first_name),
                    $genderLabels[$contact->gender] ?? '',
                    $this->sanitizeCsvField($contact->email),
                    $this->sanitizeCsvField($contact->tel),
                    $this->sanitizeCsvField($contact->address),
                    $this->sanitizeCsvField($contact->building),
                    $this->sanitizeCsvField($contact->category->content ?? ''),
                    $this->sanitizeCsvField($contact->detail),
                    $contact->created_at->format('Y-m-d'),
                ]);
            }

            fclose($handle);
        }, 'contacts.csv', ['Content-Type' => 'text/csv']);
    }

    private function sanitizeCsvField(?string $value): ?string
    {
        if ($value !== null && preg_match('/^[=+\-@]/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
