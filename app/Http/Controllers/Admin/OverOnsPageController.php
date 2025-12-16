<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OverOnsPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class OverOnsPageController extends Controller
{
    /** Alleen bestaande kolommen doorlaten */
    private function filterToColumns(array $attributes): array
    {
        $table = (new OverOnsPage())->getTable();
        $cols  = Schema::getColumnListing($table);
        return array_intersect_key($attributes, array_flip($cols));
    }

    public function edit()
    {
        // Defaults voor de Over Ons pagina
        $defaults = [
            'hero_title'            => 'Over Ons',
            'intro'                 => '<p>Hier komt de introductietekst van de Over Ons pagina.</p>',
            'hero_image_path'       => null,
            'seo_title'             => 'Over Ons',
            'seo_description'       => 'Leer meer over onze organisatie.',
            'published'             => true,
            'robots_index'          => true,
            'robots_follow'         => true,
            'canonical_url'         => null,
            'seo_image_path'        => null,
            'updated_by'            => null,
        ];

        $page = OverOnsPage::firstOrCreate(['id' => 1], $this->filterToColumns($defaults));

        // Alias mapping voor Vue
        $overOnsPage = [
            'id'               => $page->id,
            'title'            => $page->hero_title,
            'content'          => $page->intro,
            'image_path'       => $page->hero_image_path,
            'meta_title'       => $page->seo_title,
            'meta_description' => $page->seo_description,
            'canonical_url'    => $page->canonical_url ?? null,
            'robots_index'     => (bool) ($page->robots_index ?? true),
            'robots_follow'    => (bool) ($page->robots_follow ?? true),
            'seo_image_path'   => $page->seo_image_path ?? null,
            'published'        => (bool) $page->published,
        ];

        return Inertia::render('admin/pages/OverOns', [
            'page'        => $page,
            'overOnsPage' => $overOnsPage,
        ]);
    }

    public function update(Request $request)
    {
        $page = OverOnsPage::firstOrCreate(['id' => 1]);
        $max_file_size_kb = 12288; // 12 MB

        // 1) Valideer alle velden
        $data = $request->validate([
            // Aliassen uit Vue
            'title'                 => ['nullable','string','max:160'],
            'content'               => ['nullable','string'],
            'meta_title'            => ['nullable','string','max:160'],
            'meta_description'      => ['nullable','string'],
            'canonical_url'         => ['nullable','url','max:255'],

            // Uploads (12 MB max)
            'image'                 => ['nullable','image','max:' . $max_file_size_kb],
            'seo_image'             => ['nullable','image','max:' . $max_file_size_kb],

            // Directe DB velden of booleans
            'published'             => ['nullable','boolean'], // Noodzakelijk omdat we het via transform meesturen
            'robots_index'          => ['nullable','boolean'],
            'robots_follow'         => ['nullable','boolean'],
        ]);

        $updates = $data;

        // 2) Mapping van Vue aliassen naar DB-kolommen
        if (isset($updates['title'])) {
            $updates['hero_title'] = $updates['title'];
            unset($updates['title']);
        }
        if (isset($updates['content'])) {
            $updates['intro'] = $updates['content'];
            unset($updates['content']);
        }
        if (isset($updates['meta_title'])) {
            $updates['seo_title'] = $updates['meta_title'];
            unset($updates['meta_title']);
        }
        if (isset($updates['meta_description'])) {
            $updates['seo_description'] = $updates['meta_description'];
            unset($updates['meta_description']);
        }

        // 3) Hero Afbeelding Upload
        if ($request->hasFile('image') && Schema::hasColumn($page->getTable(), 'hero_image_path')) {
            if ($page->hero_image_path && Storage::disk('public')->exists($page->hero_image_path)) {
                Storage::disk('public')->delete($page->hero_image_path);
            }
            $path = $request->file('image')->store('pages/over-ons', 'public');
            $updates['hero_image_path'] = $path;
        }

        // 4) SEO Afbeelding Upload
        if ($request->hasFile('seo_image') && Schema::hasColumn($page->getTable(), 'seo_image_path')) {
            if ($page->seo_image_path && Storage::disk('public')->exists($page->seo_image_path)) {
                Storage::disk('public')->delete($page->seo_image_path);
            }
            $og = $request->file('seo_image')->store('pages/over-ons/og', 'public');
            $updates['seo_image_path'] = $og;
        }

        // 5) Alleen bestaande kolommen wegschrijven en update by
        $updates = $this->filterToColumns($updates);

        $page->fill($updates);
        if (Schema::hasColumn($page->getTable(), 'updated_by')) {
            // Aangenomen dat auth guard 'admin' de user ID bevat
            $page->updated_by = optional(auth('admin')->user())->id;
        }
        $page->save();

        return back()->with('success', 'Over Ons pagina opgeslagen.');
    }
}
