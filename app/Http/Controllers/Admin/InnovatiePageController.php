<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InnovatiePage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InnovatiePageController extends Controller
{
    public function edit()
    {
        $page = InnovatiePage::firstOrCreate(
            ['id' => 1],
            [
                'title' => 'Gedreven door Innovatie',
                'content' => '<p>Wij bouwen razendsnelle, schaalbare applicaties met de allernieuwste technologie.</p>',
            ]
        );

        return Inertia::render('admin/pages/innovatie/Form', [
            'page' => $page,
            'success' => session('success'),
        ]);
    }

    public function update(Request $request)
    {
        $page = InnovatiePage::firstOrFail();

        $messages = [
            'required' => 'Dit veld is verplicht.',
            'string' => 'Dit veld moet tekst zijn.',
            'max' => [
                'string' => 'Mag niet meer dan :max tekens bevatten.',
                'file' => 'Bestand mag niet groter zijn dan :max kilobytes.',
            ],
            'boolean' => 'Dit veld moet waar of onwaar zijn.',
            'image' => 'Dit moet een geldig afbeeldingsbestand zijn.',
            'url' => 'Voer een geldige URL in.',
        ];

        $rules = [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'published' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|url',
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',
        ];

        // Custom validatie voor image
        if ($request->hasFile('image')) {
            $rules['image'] = 'image|max:10240';
        } else {
            $rules['image'] = [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (is_string($value) && $value === 'DELETE')
                        return;
                    if (!is_null($value))
                        $fail('Ongeldig bestand.');
                }
            ];
        }

        // Custom validatie voor seo_image
        if ($request->hasFile('seo_image')) {
            $rules['seo_image'] = 'image|max:5120';
        } else {
            $rules['seo_image'] = [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (is_string($value) && $value === 'DELETE')
                        return;
                    if (!is_null($value))
                        $fail('Ongeldig bestand.');
                }
            ];
        }

        $data = $request->validate($rules, $messages);

        // Afhandeling Image
        if ($request->hasFile('image')) {
            if ($page->image_path)
                Storage::disk('public')->delete($page->image_path);
            $data['image_path'] = $request->file('image')->store('innovatie/hero', 'public');
        } elseif ($request->input('image') === 'DELETE') {
            if ($page->image_path)
                Storage::disk('public')->delete($page->image_path);
            $data['image_path'] = null;
        }

        // Afhandeling SEO Image
        if ($request->hasFile('seo_image')) {
            if ($page->seo_image_path)
                Storage::disk('public')->delete($page->seo_image_path);
            $data['seo_image_path'] = $request->file('seo_image')->store('innovatie/seo', 'public');
        } elseif ($request->input('seo_image') === 'DELETE') {
            if ($page->seo_image_path)
                Storage::disk('public')->delete($page->seo_image_path);
            $data['seo_image_path'] = null;
        }

        unset($data['image'], $data['seo_image']);

        $page->update($data);

        return back()->with('success', 'Pagina bijgewerkt.');
    }
}
