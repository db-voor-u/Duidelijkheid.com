<?php

use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\ContactPageController;
use App\Http\Controllers\Admin\OverOnsPageController;
use App\Http\Controllers\Admin\WelcomeController;
use App\Http\Controllers\Admin\OverOnsBlogController; // Admin Controller
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\BlogPublicController;
use App\Http\Controllers\ContactPublicController;
use App\Http\Controllers\InnovatiePublicController;
use App\Http\Controllers\PublicOverOnsPageController; // Publieke controller voor Over Ons pagina
use App\Http\Controllers\OverOnsPublicController; // Publieke controller voor Over Ons BLOG
use App\Http\Controllers\Admin\Settings\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\Settings\PasswordController as AdminPasswordController;
use App\Http\Controllers\Auth\AdminPasswordResetLinkController;
use App\Http\Controllers\Auth\AdminNewPasswordController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::post('/test-logout-no-auth', function (Request $request) {
    \Log::info('Test logout called - no auth', [
        'method' => $request->method(),
        'cookies' => $request->cookies->all(),
        'csrf_input' => $request->input('_token'),
        'csrf_header' => $request->header('X-CSRF-TOKEN'),
    ]);
    return response()->json([
        'ok' => true,
        'cookies' => $request->cookies->all(),
        'csrf_input' => $request->input('_token'),
        'csrf_header' => $request->header('X-CSRF-TOKEN'),
    ]);
});

// Voeg tijdelijk toe aan web. php (BOVENAAN, buiten andere groups)
Route::post('/test-logout-no-auth', function (Request $request) {
    \Log::info('Test logout called - no auth middleware', [
        'method' => $request->method(),
        'csrf' => $request->header('X-CSRF-TOKEN') ?? $request->input('_token'),
        'user_agent' => $request->userAgent(),
    ]);

    return response()->json([
        'message' => 'Test logout received',
        'method' => $request->method(),
        'csrf_ok' => !empty($request->header('X-CSRF-TOKEN') ?? $request->input('_token'))
    ]);
});


// Public Feedback (Rate limited: 5 requests per minute per IP)
Route::post('/feedback', [App\Http\Controllers\FeedbackController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('feedback.store');

/*
|--------------------------------------------------------------------------
| Publieke routes
|--------------------------------------------------------------------------
*/
Route::get('/', [BlogPublicController::class, 'home'])->name('welcome.show');

// SEO: Sitemap.xml
Route::get('/sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

Route::get('/blog', [BlogPublicController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogPublicController::class, 'show'])->name('blog.show');
Route::get('/blog/{post:slug}/download', [BlogPublicController::class, 'download'])->name('blog.download');

// Publieke Over Ons route
Route::get('/over-ons', [PublicOverOnsPageController::class, 'show'])->name('over.ons');

// Publieke Innovatie route
Route::get('/innovatie', [InnovatiePublicController::class, 'show'])->name('innovatie.show');
Route::get('/innovatie/{post:slug}', [OverOnsPublicController::class, 'show'])->name('innovatie.blog.show');

Route::get('/neurodiversiteit', [\App\Http\Controllers\NeurodiversiteitPublicController::class, 'show'])->name('neurodiversiteit.show');

// Publieke Blog Detailpagina voor Over Ons artikelen
Route::get('/over-ons-blog/{post:slug}', [OverOnsPublicController::class, 'show'])->name('overons-blog.show');


// Publieke contact routes
Route::get('/contact', [ContactPublicController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactPublicController::class, 'submit'])
    ->middleware('throttle:10,1')   // optioneel: beperk spam
    ->name('contact.submit');

// Algemene voorwaarden
Route::get('/algemene-voorwaarden', [App\Http\Controllers\TermsPublicController::class, 'show'])->name('terms.show');

// Privacybeleid
Route::get('/privacy', [App\Http\Controllers\PrivacyPolicyPublicController::class, 'show'])->name('privacy.show');

Route::get('/login', fn() => redirect()->route('admin.login'))->name('login');


/*
| Admin (hoofdbeheerder)
|--------------------------------------------------------------------------
*/
Route::prefix('hoofdbeheerder')->name('admin.')->group(function () {

    // Guest-only (nog niet ingelogde admin)
    Route::middleware('admin.guest')->group(function () {
        Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AdminLoginController::class, 'login'])->name('login.submit');

        // Wachtwoord herstellen
        Route::get('/forgot-password', [AdminPasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('/forgot-password', [AdminPasswordResetLinkController::class, 'store'])->name('password.email');
        Route::get('/reset-password/{token}', [AdminNewPasswordController::class, 'create'])->name('password.reset');
        Route::post('/reset-password', [AdminNewPasswordController::class, 'store'])->name('password.store');
    });

    // Beveiligde admin-routes
    Route::middleware('auth:admin')->group(function () {
        // ✅ CORRECTE LOGOUT ROUTE
        Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::redirect('instellingen', '/hoofdbeheerder/instellingen/profiel');

        Route::get('instellingen/profiel', [AdminProfileController::class, 'edit'])->name('settings.profile.edit');
        Route::patch('instellingen/profiel', [AdminProfileController::class, 'update'])->name('settings.profile.update');
        // Route::delete('instellingen/profiel', [AdminProfileController::class, 'destroy'])->name('settings.profile.destroy');

        Route::get('instellingen/wachtwoord', [AdminPasswordController::class, 'edit'])->name('settings.password.edit');
        Route::put('instellingen/wachtwoord', [AdminPasswordController::class, 'update'])
            ->middleware('throttle:6,1')
            ->name('settings.password.update');

        Route::get('instellingen/uiterlijk', function () {
            return Inertia::render('admin/settings/Appearance');
        })->name('settings.appearance.edit');



        // Gebruikersbeheer
        Route::get('/gebruikers', [AdminUsersController::class, 'index'])->name('users.index');
        Route::get('/gebruikers/aanmaken', [AdminUsersController::class, 'create'])->name('users.create');
        Route::post('/gebruikers', [AdminUsersController::class, 'store'])->name('users.store');
        Route::put('/gebruikers/{user}', [AdminUsersController::class, 'update'])->name('users.update');
        Route::delete('/gebruikers/{user}', [AdminUsersController::class, 'destroy'])->name('users.destroy');

        // Pagina-inhoud: Welcome
        Route::get('/pages/welcome', [WelcomeController::class, 'edit'])->name('pages.welcome.edit');
        Route::put('/pages/welcome', [WelcomeController::class, 'update'])->name('pages.welcome.update');

        // Pagina-inhoud: Blog hero/SEO
        Route::get('/pages/blog', [BlogController::class, 'pageEdit'])->name('pages.blog.edit');
        Route::put('/pages/blog', [BlogController::class, 'pageUpdate'])->name('pages.blog.update');

        // Pagina-inhoud: Contact (landing/hero/SEO)
        Route::get('/pages/contact', [ContactPageController::class, 'edit'])->name('pages.contact.edit');
        Route::put('/pages/contact', [ContactPageController::class, 'update'])->name('pages.contact.update');

        // Pagina-inhoud: Over Ons (Admin Edit)
        Route::get('/pages/over-ons', [OverOnsPageController::class, 'edit'])->name('pages.overons.edit');
        Route::put('/pages/over-ons', [OverOnsPageController::class, 'update'])->name('pages.overons.update');

        // Pagina-inhoud: Algemene Voorwaarden (Admin Edit)
        Route::get('/pages/terms', [App\Http\Controllers\Admin\TermsPageController::class, 'edit'])->name('pages.terms.edit');
        Route::put('/pages/terms', [App\Http\Controllers\Admin\TermsPageController::class, 'update'])->name('pages.terms.update');

        // Pagina-inhoud: Privacybeleid (Admin Edit)
        Route::get('/pages/privacy', [App\Http\Controllers\Admin\PrivacyPolicyController::class, 'edit'])->name('pages.privacy.edit');
        Route::put('/pages/privacy', [App\Http\Controllers\Admin\PrivacyPolicyController::class, 'update'])->name('pages.privacy.update');

        // Pagina-inhoud: Innovatie (Admin Edit)
        Route::get('/pages/innovatie', [App\Http\Controllers\Admin\InnovatiePageController::class, 'edit'])->name('pages.innovatie.edit');
        Route::put('/pages/innovatie', [App\Http\Controllers\Admin\InnovatiePageController::class, 'update'])->name('pages.innovatie.update');

        // -------------------------------------------------------------
        // ADMIN ROUTES VOOR 'OVER ONS BLOG'
        // -------------------------------------------------------------
        Route::prefix('over-ons-blog')->name('overons-blog.')->group(function () {
            // Index / Overzicht
            Route::get('/', [OverOnsBlogController::class, 'index'])->name('index');
            // Aanmaken
            Route::get('/aanmaken', [OverOnsBlogController::class, 'create'])->name('create');
            Route::post('/', [OverOnsBlogController::class, 'store'])->name('store');
            // Bewerken, Updaten, Verwijderen (met ID)
            // {post} bindt nu direct aan de ID van het OverOnsBlog model
            Route::get('/{post}/bewerken', [OverOnsBlogController::class, 'edit'])->name('edit');
            Route::put('/{post}', [OverOnsBlogController::class, 'update'])->name('update');
            Route::delete('/{post}', [OverOnsBlogController::class, 'destroy'])->name('destroy');
        });

        // -------------------------------------------------------------
        // ADMIN ROUTES VOOR 'INNOVATIE BLOG'
        // -------------------------------------------------------------
        Route::prefix('innovatie')->name('innovatieBlog.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\InnovatieBlogController::class, 'index'])->name('index');
            Route::get('/aanmaken', [\App\Http\Controllers\Admin\InnovatieBlogController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\InnovatieBlogController::class, 'store'])->name('store');
            Route::get('/{post}/bewerken', [\App\Http\Controllers\Admin\InnovatieBlogController::class, 'edit'])->name('edit');
            Route::put('/{post}', [\App\Http\Controllers\Admin\InnovatieBlogController::class, 'update'])->name('update');
            Route::delete('/{post}', [\App\Http\Controllers\Admin\InnovatieBlogController::class, 'destroy'])->name('destroy');
        });
        // -------------------------------------------------------------

        // Categorieën management
        Route::get('/categorieen', [App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categorieen', [App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categorieen/{category}', [App\Http\Controllers\Admin\CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categorieen/{category}', [App\Http\Controllers\Admin\CategoryController::class, 'destroy'])->name('categories.destroy');

        // Blog posts (hoofd blog)
        Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
        Route::get('/blog/aanmaken', [BlogController::class, 'create'])->name('blog.create');
        Route::post('/blog', [BlogController::class, 'store'])->name('blog.store');
        Route::get('/blog/{post:slug}/bewerken', [BlogController::class, 'edit'])->name('blog.edit');
        Route::put('/blog/{post:slug}', [BlogController::class, 'update'])->name('blog.update');
        Route::delete('/blog/{post:slug}', [BlogController::class, 'destroy'])->name('blog.destroy');

        // Contact inbox (beheer)
        Route::get('/contact/opstellen', [ContactMessageController::class, 'create'])->name('contact.compose');
        Route::get('/contact/nieuw', [ContactMessageController::class, 'create'])->name('contact.create');
        Route::post('/contact/send', [ContactMessageController::class, 'send'])->name('contactSend');
        Route::get('/contact', [ContactMessageController::class, 'index'])->name('contact.index');
        Route::post('/contact', [ContactMessageController::class, 'store'])->name('contact.store');
        Route::get('/contact/{message}', [ContactMessageController::class, 'show'])->name('contact.show');
        Route::post('/contact/{message}/reply', [ContactMessageController::class, 'reply'])->name('contact.reply');
        Route::patch('/contact/{message}/status', [ContactMessageController::class, 'updateStatus'])->name('contact.status');
        Route::get('/contact/{message}/history', [ContactMessageController::class, 'history'])->name('contact.history');
        Route::delete('/contact/{message}', [ContactMessageController::class, 'destroy'])->name('contact.destroy');

        // Feedback
        Route::get('/feedback', [App\Http\Controllers\Admin\FeedbackController::class, 'index'])->name('feedback.index');
        Route::get('/feedback/{feedback}', [App\Http\Controllers\Admin\FeedbackController::class, 'show'])->name('feedback.show');
        Route::delete('/feedback/{feedback}', [App\Http\Controllers\Admin\FeedbackController::class, 'destroy'])->name('feedback.destroy');
        Route::patch('/feedback/{feedback}/read', [App\Http\Controllers\Admin\FeedbackController::class, 'toggleRead'])->name('feedback.toggleRead');
    });
});

// Shortcut naar dashboard / login
Route::get('/hoofdbeheerder', function () {
    return Auth::guard('admin')->check()
        ? redirect('/hoofdbeheerder/dashboard')
        : redirect('/hoofdbeheerder/login');
});


require __DIR__ . '/settings.php';
