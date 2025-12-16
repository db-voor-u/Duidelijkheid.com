<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(): Response
    {
        /** @var \App\Models\Admin|null $admin */
        $admin = auth()->guard('admin')->user();

        // 1. Contact stats (Efficient count queries)
        $totalMessages = ContactMessage::count();
        $unreadContactCount = ContactMessage::where('is_spam', false)->where('status', 'new')->count();
        $newTodayCount = ContactMessage::where('is_spam', false)->whereDate('created_at', today())->count();
        $latest5 = ContactMessage::latest('created_at')->take(5)->get(['id', 'name', 'email', 'subject', 'status', 'created_at']);

        // 2. Queue stats (Direct DB query, faster than Schema check + DB check every time)
        // Assume tables exist or catch exception if they don't (cleaner than checking schema every request)
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            $pendingJobs = null;
            $failedJobs = null;
        }

        // 3. System stats
        $diskUsage = $this->getDiskUsage();
        $memUsage = $this->getMemoryUsage();
        $sysStatus = $this->dbAlive() ? 'online' : 'offline';

        // 4. Versions (Cached if possible, but fast enough)
        [$dbLabel, $dbVersion] = $this->getDatabaseVersion();

        // 5. Activity
        $activity = $this->getRecentActivity();

        // 6. Users & Blogs
        $userCount = User::count();
        $activeProjects = Blog::count();

        $overOnsCount = \App\Models\OverOnsBlog::whereHas('category', function ($q) {
            $q->where('type', 'over_ons');
        })->count();

        $innovatieCount = \App\Models\OverOnsBlog::whereHas('category', function ($q) {
            $q->where('type', 'innovatie');
        })->count();

        return Inertia::render('admin/AdminDashboard', [
            // Admin data is already in 'auth.user', sending it again is redundant but kept for 'last_login' formatting override
            'admin' => [
                'id' => $admin?->id,
                'name' => $admin?->name,
                'email' => $admin?->email,
                'created_at' => optional($admin?->created_at)->format('d-m-Y'),
                'last_login' => optional($admin?->last_login_at)->format('d-m-Y H:i:s'),
            ],

            'stats' => [
                'total_users' => $userCount,
                'active_projects' => $activeProjects,
                'over_ons_count' => $overOnsCount,
                'innovatie_count' => $innovatieCount,
                'pending_tasks' => $unreadContactCount,
                'system_status' => $sysStatus,
                'disk_usage' => $diskUsage,
                'memory_usage' => $memUsage,
            ],

            'contactSummary' => [
                'total' => $totalMessages,
                'unread' => $unreadContactCount,
                'new_today' => $newTodayCount,
                'latest' => $latest5->map(fn($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'email' => $m->email,
                    'subject' => $m->subject,
                    'status' => $m->status,
                    'created_at' => optional($m->created_at)->toIso8601String(),
                ]),
            ],

            'versions' => [
                'php' => PHP_VERSION,
                'laravel' => Application::VERSION,
                'node' => $this->getNodeVersion(),
                'os' => php_uname('s') . ' ' . php_uname('r'),
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'db_version' => $dbVersion,
                'db_label' => $dbLabel,
            ],

            'queue' => [
                'pending' => $pendingJobs,
                'failed' => $failedJobs,
            ],

            'recentActivity' => $activity,
        ]);
    }

    /* ========================= Helpers ========================= */

    private function dbAlive(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function getNodeVersion(): string
    {
        // Try config first
        $configV = config('system.node_version');
        if ($configV)
            return $configV;

        // Try exec
        try {
            if (function_exists('exec')) {
                $output = [];
                exec('node -v 2>&1', $output, $returnVar);
                if ($returnVar === 0 && !empty($output)) {
                    // Output is typically "v18.16.0"
                    return $output[0];
                }
            }
        } catch (\Throwable) {
            // fail silently
        }

        return '—';
    }

    /** Generic DB version fetching */
    private function getDatabaseVersion(): array
    {
        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName(); // 'mysql', 'pgsql', 'sqlite', 'sqlsrv'

            // Default label based on driver
            $label = match ($driver) {
                'mysql' => 'MySQL',
                'pgsql' => 'PostgreSQL',
                'sqlite' => 'SQLite',
                'sqlsrv' => 'SQL Server',
                default => ucfirst($driver),
            };

            $version = null;

            if ($driver === 'sqlite') {
                $row = DB::selectOne('select sqlite_version() as v');
                $version = $row?->v;
            } else {
                // MySQL & Postgres both support 'select version()'
                // But structure of output text differs
                $row = DB::selectOne('select version() as v');
                $raw = $row?->v ?? '';

                if ($driver === 'pgsql') {
                    // Extract "17.4" from "PostgreSQL 17.4 on ..."
                    if (preg_match('/PostgreSQL\s+([\d\.]+)/i', $raw, $m)) {
                        $version = $m[1];
                    } else {
                        $version = $raw;
                    }
                } elseif ($driver === 'mysql') {
                    // MySQL often returns just "8.0.32" or "10.6.12-MariaDB"
                    // We can just use it directly or clean it up if needed.
                    $version = $raw;
                } else {
                    $version = $raw;
                }
            }

            return [$label, $version ?? '—'];
        } catch (\Throwable) {
            return ['Database', '—'];
        }
    }

    private function getDiskUsage(): ?array
    {
        try {
            $path = base_path();
            $total = @disk_total_space($path);
            $free = @disk_free_space($path);
            if ($total === false || $free === false)
                return null;

            $used = max($total - $free, 0);
            $percentage = $total > 0 ? (int) round(($used / $total) * 100) : 0;

            return [
                'total' => $this->bytesToHuman($total),
                'used' => $this->bytesToHuman($used),
                'free' => $this->bytesToHuman($free),
                'percentage' => $percentage,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function getMemoryUsage(): ?array
    {
        try {
            $limitStr = ini_get('memory_limit') ?: '0';
            $limit = $this->parsePhpSize($limitStr);
            $current = function_exists('memory_get_usage') ? memory_get_usage(true) : 0;

            if ($limit <= 0) {
                return ['current' => $this->bytesToHuman($current), 'limit' => '—', 'percentage' => 0];
            }

            return [
                'current' => $this->bytesToHuman($current),
                'limit' => $this->bytesToHuman($limit),
                'percentage' => (int) round(($current / $limit) * 100),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function getRecentActivity(): array
    {
        $list = [];
        $i = 1;

        if ($c = ContactMessage::latest('created_at')->first()) {
            $list[] = [
                'id' => $i++,
                'user' => 'Contact',
                'action' => strtoupper($c->status),
                'description' => 'Nieuw contactbericht: ' . $c->subject,
                'created_at' => optional($c->created_at)->format('d-m-Y H:i:s'),
            ];
        }
        if ($b = Blog::latest('created_at')->first()) {
            $list[] = [
                'id' => $i++,
                'user' => 'Blog',
                'action' => 'Aangemaakt',
                'description' => $b->title,
                'created_at' => optional($b->created_at)->format('d-m-Y H:i:s'),
            ];
        }
        $list[] = [
            'id' => $i++,
            'user' => 'Systeem',
            'action' => 'Status',
            'description' => 'Applicatie actief',
            'created_at' => now()->format('d-m-Y H:i:s'),
        ];

        return array_slice($list, 0, 5);
    }

    /* ---------------- Bytes & sizes ---------------- */

    private function bytesToHuman(int|float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return number_format($bytes, $i === 0 ? 0 : 2, ',', '.') . ' ' . $units[$i];
    }

    private function parsePhpSize(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1')
            return 0;
        $unit = strtolower(substr($value, -1));
        $num = (float) $value;

        return match ($unit) {
            'g' => (int) round($num * 1024 ** 3),
            'm' => (int) round($num * 1024 ** 2),
            'k' => (int) round($num * 1024),
            default => (int) $num,
        };
    }
}
