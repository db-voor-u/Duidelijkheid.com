<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ContactPageController extends Controller
{
    /** Alleen bestaande kolommen doorlaten */
    private function filterToColumns(array $attributes): array
    {
        $table = (new ContactPage())->getTable();
        $cols = Schema::getColumnListing($table);
        return array_intersect_key($attributes, array_flip($cols));
    }

    public function edit()
    {
        // Defaults passend bij jouw migratie (geen meta_* hier, die mappen we in update)
        $defaults = [
            'hero_title' => 'Contact',
            'hero_subtitle' => null,
            'intro' => '<p>Stel je vraag via het formulier.</p>',
            'form_title' => 'Contact',
            'form_content' => 'Vul het formulier in en we nemen zo snel mogelijk contact met je op.',
            'button_text' => 'Neem contact op',
            'hero_image_path' => null,
            'show_form' => true,
            'recipient_email' => null,
            'additional_recipients' => [], // JSON
            'seo_title' => 'Contact',
            'seo_description' => 'Neem contact met ons op.',
            'published' => true,
            'updated_by' => null,
        ];

        $page = ContactPage::firstOrCreate(['id' => 1], $this->filterToColumns($defaults));

        // Admin-Vue verwacht vaak 'contactPage' met 'title/content' etc.
        $contactPage = [
            'id' => $page->id,
            'title' => $page->hero_title,
            'content' => $page->intro,
            'form_title' => $page->form_title,
            'form_content' => $page->form_content,
            'button_text' => $page->button_text,
            'image_path' => $page->hero_image_path,
            'meta_title' => $page->seo_title,
            'meta_description' => $page->seo_description,
            // Niet in migratie? Laat leeg:
            'canonical_url' => $page->canonical_url ?? null,
            'robots_index' => $page->robots_index ?? true,
            'robots_follow' => $page->robots_follow ?? true,
            // Optioneel:
            'seo_image_path' => $page->seo_image_path ?? null,
            'show_form' => (bool) $page->show_form,
            'published' => (bool) $page->published,
        ];

        return Inertia::render('admin/pages/Contact', [
            // ruwe DB payload (met hero_* en seo_*)
            'page' => $page,
            // vriendelijke alias die jouw Admin-Vue al gebruikt
            'contactPage' => $contactPage,
        ]);
    }

    public function update(Request $request)
    {
        $page = ContactPage::firstOrCreate(['id' => 1]);

        // 1) Valideer beide naamsets
        $data = $request->validate([
            // DB-namen
            'hero_title' => ['nullable', 'string', 'max:160'],
            'hero_subtitle' => ['nullable', 'string'],
            'intro' => ['nullable', 'string'],
            'form_title' => ['nullable', 'string', 'max:160'],
            'form_content' => ['nullable', 'string'],
            'button_text' => ['nullable', 'string', 'max:60'],
            'show_form' => ['nullable', 'boolean'],
            'recipient_email' => ['nullable', 'string', 'max:160'],
            'additional_recipients' => ['nullable', 'array'],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string'],
            'published' => ['nullable', 'boolean'],
            'updated_by' => ['nullable', 'integer'],
            'hero_image_path' => ['nullable', 'string', 'max:255'],
            // Admin-vriendelijke namen (komen uit jouw huidige Vue)
            'title' => ['nullable', 'string', 'max:160'],
            'content' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
            // Uploads
            // 👇 AANGEPAST: Maximaal 12MB (12288 KB) voor hero afbeelding
            'image' => ['nullable', 'image', 'max:12288'],
            // 👇 AANGEPAST: Maximaal 12MB (12288 KB) voor SEO afbeelding
            'seo_image' => ['nullable', 'image', 'max:12288'],
            'seo_image_path' => ['nullable', 'string', 'max:255'],
            // Niet in jouw migratie, maar je Vue kan ze meesturen
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'robots_index' => ['nullable', 'boolean'],
            'robots_follow' => ['nullable', 'boolean'],
        ]);

        $updates = [];

        // 2) Mapping van admin-namen naar DB-kolommen
        $map = [
            'title' => 'hero_title',
            'content' => 'intro',
            'meta_title' => 'seo_title',
            'meta_description' => 'seo_description',
            'image_path' => 'hero_image_path',
            'hero_title' => 'hero_title',
            'hero_subtitle' => 'hero_subtitle',
            'intro' => 'intro',
            'form_title' => 'form_title',
            'form_content' => 'form_content',
            'button_text' => 'button_text',
            'seo_title' => 'seo_title',
            'seo_description' => 'seo_description',
            'show_form' => 'show_form',
            'published' => 'published',
            'recipient_email' => 'recipient_email',
        ];

        foreach ($map as $in => $col) {
            if (array_key_exists($in, $data) && Schema::hasColumn($page->getTable(), $col)) {
                $updates[$col] = $data[$in];
            }
        }

        // 3) additional_recipients normaliseren (array → JSON of array; zorg in model voor cast)
        if (array_key_exists('additional_recipients', $data) && Schema::hasColumn($page->getTable(), 'additional_recipients')) {
            $updates['additional_recipients'] = $data['additional_recipients'] ?? [];
        }

        // 4) Upload hero-afbeelding (→ hero_image_path)
        if ($request->hasFile('image') && Schema::hasColumn($page->getTable(), 'hero_image_path')) {
            if ($page->hero_image_path && Storage::disk('public')->exists($page->hero_image_path)) {
                Storage::disk('public')->delete($page->hero_image_path);
            }
            $path = $request->file('image')->store('pages/contact', 'public');
            $updates['hero_image_path'] = $path;
        }

        // 5) (Optioneel) SEO/OG afbeelding (→ seo_image_path) alleen als kolom bestaat
        if (Schema::hasColumn($page->getTable(), 'seo_image_path')) {
            if ($request->hasFile('seo_image')) {
                if ($page->seo_image_path && Storage::disk('public')->exists($page->seo_image_path)) {
                    Storage::disk('public')->delete($page->seo_image_path);
                }
                $og = $request->file('seo_image')->store('pages/contact/og', 'public');
                $updates['seo_image_path'] = $og;
            } elseif (!empty($data['seo_image_path'])) {
                $updates['seo_image_path'] = $data['seo_image_path'];
            }
        }

        // 6) Bools netjes defaulten als ze zijn meegegeven
        foreach (['show_form', 'published'] as $boolKey) {
            if (array_key_exists($boolKey, $data) && Schema::hasColumn($page->getTable(), $boolKey)) {
                $updates[$boolKey] = (bool) $data[$boolKey];
            }
        }

        // 7) Alleen bestaande kolommen wegschrijven
        $updates = $this->filterToColumns($updates);

        $page->fill($updates);
        if (Schema::hasColumn($page->getTable(), 'updated_by')) {
            $page->updated_by = optional(auth('admin')->user())->id;
        }
        $page->save();

        return back()->with('success', 'Contactpagina opgeslagen.');
    }
}
