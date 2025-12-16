<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Illuminate\Support\Facades\Route as RouteFacade;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        // Check of we op admin routes zitten
        $isAdminRoute = $request->is('hoofdbeheerder/*');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                // Als admin route, gebruik admin als 'user' zodat bestaande components blijven werken
                'user' => $isAdminRoute
                    ? Auth::guard('admin')->user()
                    : $request->user(),
                'isAdmin' => $isAdminRoute,
            ],
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'contactPage' => function () {
                $page = \App\Models\ContactPage::first();
                return $page ? [
                    'title' => $page->hero_title ?? 'Heb je een vraag?',
                    'content' => $page->intro ?? 'Neem gerust contact met ons op via het formulier.',
                    'form_title' => $page->form_title ?? 'Contact',
                    'form_content' => $page->form_content ?? 'Vul het formulier in en we nemen zo snel mogelijk contact met je op.',
                    'button_text' => !empty($page->button_text) ? $page->button_text : 'Neem contact op',
                ] : null;
            },
        ];
    }

    /**
     * Genereer dynamische publieke navigatie (met config-override).
     */
    protected function publicNav(): array
    {
        $cfg = config('navigation.public', []);

        // Veilige route-resolutie (valt terug op pad als de route-naam niet bestaat)
        $homeHref = RouteFacade::has('home') ? route('home') : '/';
        $blogHref = RouteFacade::has('blog.index') ? route('blog.index') : '/blog';

        $defaults = [
            ['label' => 'Home', 'href' => $homeHref],
            ['label' => 'Blog', 'href' => $blogHref],
        ];

        $normalize = function (array $item): array {
            $href = $item['href']
                ?? $item['url']
                ?? (isset($item['route']) ? route($item['route']) : '#');

            return [
                'label' => $item['label'] ?? '',
                'href' => $href,
            ];
        };

        return [
            'links' => array_map($normalize, $cfg['links'] ?? $defaults),
            'groups' => array_map(function ($group) use ($normalize) {
                return [
                    'label' => $group['label'] ?? '',
                    'items' => array_map($normalize, $group['items'] ?? []),
                ];
            }, $cfg['groups'] ?? []),
            'cta' => isset($cfg['cta'])
                ? [
                    'label' => $cfg['cta']['label'] ?? null,
                    'href' => $cfg['cta']['href']
                        ?? $cfg['cta']['url']
                        ?? (isset($cfg['cta']['route']) ? route($cfg['cta']['route']) : null),
                ]
                : null,
        ];
    }
}
