<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CaptureQueryMetrics extends Command
{
    protected $signature = 'metrics:capture {--json : Output as JSON}';

    protected $description = 'Capture query metrics for hotspot routes';

    protected array $metrics = [];

    protected array $hotspotRoutes = [
        // Dashboards
        ['method' => 'GET', 'uri' => 'tata-usaha/dashboard', 'role' => 'TataUsaha', 'controller' => 'TataUsahaController@index'],
        ['method' => 'GET', 'uri' => 'guru-bk/dashboard', 'role' => 'GuruBk', 'controller' => 'GuruBkController@index'],
        ['method' => 'GET', 'uri' => 'guru-piket/dashboard', 'role' => 'GuruPiket', 'controller' => 'GuruPiketController@index'],
        ['method' => 'GET', 'uri' => 'wali-kelas/dashboard', 'role' => 'WaliKelas', 'controller' => 'WaliKelasController@index'],
        ['method' => 'GET', 'uri' => 'pengurus-kelas/dashboard', 'role' => 'PengurusKelas', 'controller' => 'PengurusKelasController@index'],
        ['method' => 'GET', 'uri' => 'siswa/dashboard', 'role' => 'Siswa', 'controller' => 'SiswaController@index'],
    ];

    public function handle(): int
    {
        $this->info('Capturing query metrics for hotspot routes...');
        $this->info('Timestamp: '.now()->toIso8601String());

        $results = [
            'timestamp' => now()->toIso8601String(),
            'environment' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'database_connection' => config('database.default'),
            ],
            'routes' => [],
        ];

        foreach ($this->hotspotRoutes as $route) {
            $this->info("Testing: {$route['method']} {$route['uri']}");

            $metrics = $this->captureRouteMetrics($route);
            $results['routes'][$route['uri']] = $metrics;

            $this->info("  Queries: {$metrics['query_count']}, Time: {$metrics['total_time_ms']}ms");
        }

        // Save to file
        $outputPath = base_path('.sisyphus/evidence/task-1-baseline-capture.json');
        file_put_contents(
            $outputPath,
            json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $this->info("\nResults saved to: {$outputPath}");

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));
        }

        return Command::SUCCESS;
    }

    protected function captureRouteMetrics(array $route): array
    {
        // Enable query logging
        DB::enableQueryLog();

        $startTime = microtime(true);

        try {
            // Simulate request by calling the controller method directly
            $this->simulateRoute($route);

            $endTime = microtime(true);
            $totalTime = ($endTime - $startTime) * 1000; // Convert to ms

            // Get all logged queries
            $queryLog = DB::getQueryLog();
            $queryCount = count($queryLog);

            // Calculate total query time
            $totalQueryTime = array_sum(array_map(function ($q) {
                return $q['time'] ?? 0;
            }, $queryLog));

            return [
                'query_count' => $queryCount,
                'total_time_ms' => round($totalTime, 2),
                'query_time_ms' => round($totalQueryTime, 2),
                'queries' => $queryLog,
                'status' => 'success',
            ];
        } catch (\Exception $e) {
            $queryLog = DB::getQueryLog();
            $queryCount = count($queryLog);

            return [
                'query_count' => $queryCount,
                'total_time_ms' => 0,
                'queries' => $queryLog,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        } finally {
            DB::flushQueryLog();
        }
    }

    protected function simulateRoute(array $route): void
    {
        // Parse controller and method
        [$controllerName, $method] = explode('@', $route['controller']);

        // Get controller instance
        $controller = app("App\\Http\\Controllers\\{$controllerName}");

        // Mock auth user based on role
        $this->mockAuthUser($route['role']);

        // Call the method with mocked request if needed
        if (method_exists($controller, $method)) {
            $reflection = new \ReflectionMethod($controller, $method);
            $params = $reflection->getParameters();

            $args = [];
            foreach ($params as $param) {
                $type = $param->getType();
                if ($type instanceof \ReflectionNamedType && $type->getName() === 'Illuminate\Http\Request') {
                    $args[] = new \Illuminate\Http\Request;
                } elseif ($type instanceof \ReflectionNamedType) {
                    $args[] = app($type->getName());
                } else {
                    $args[] = null;
                }
            }

            $controller->$method(...$args);
        }
    }

    protected function mockAuthUser(string $role): void
    {
        // Find a user with the appropriate role
        $roleId = match ($role) {
            'TataUsaha' => 6,
            'GuruBk' => 5,
            'GuruPiket' => 4,
            'WaliKelas' => 2,
            'PengurusKelas' => 3,
            'Siswa' => 1,
            default => 6,
        };

        $user = \App\Models\Akun::where('id_role', $roleId)->first();

        if ($user) {
            Auth::login($user);
        }
    }
}
