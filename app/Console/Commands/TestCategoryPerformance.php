<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\Place;
use App\Services\CategoryRoutingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TestCategoryPerformance extends Command
{
    protected $signature = 'categories:test-performance {--clear-cache}';
    protected $description = 'Test category hierarchy query performance';

    private CategoryRoutingService $routingService;

    public function __construct(CategoryRoutingService $routingService)
    {
        parent::__construct();
        $this->routingService = $routingService;
    }

    public function handle()
    {
        if ($this->option('clear-cache')) {
            Cache::flush();
            $this->info('Cache cleared');
        }

        $this->info('Testing Category Hierarchy Performance');
        $this->newLine();

        // Test 1: Basic hierarchy queries
        $this->testBasicHierarchyQueries();
        
        // Test 2: URL routing queries
        $this->testUrlRoutingQueries();
        
        // Test 3: Place lookup queries
        $this->testPlaceLookupQueries();
        
        // Test 4: Path-based queries
        $this->testPathBasedQueries();

        // Show query statistics
        $this->showQueryStatistics();
    }

    private function testBasicHierarchyQueries()
    {
        $this->info('1. Basic Hierarchy Queries');
        
        // Test old method vs new method
        $startTime = microtime(true);
        $oldMethod = Category::with('children')->whereNull('parent_id')->get();
        $oldTime = (microtime(true) - $startTime) * 1000;
        
        $startTime = microtime(true);
        $newMethod = Category::forUrlRouting()->get();
        $newTime = (microtime(true) - $startTime) * 1000;
        
        $this->table([
            'Method', 'Time (ms)', 'Query Count', 'Memory Usage'
        ], [
            ['Old (with children)', number_format($oldTime, 2), $this->getQueryCount(), $this->formatBytes(memory_get_peak_usage())],
            ['New (forUrlRouting)', number_format($newTime, 2), $this->getQueryCount(), $this->formatBytes(memory_get_peak_usage())],
        ]);
        
        $improvement = (($oldTime - $newTime) / $oldTime) * 100;
        $this->info("Performance improvement: " . number_format($improvement, 1) . "%");
        $this->newLine();
    }

    private function testUrlRoutingQueries()
    {
        $this->info('2. URL Routing Queries');
        
        $categories = ['restaurants', 'shopping', 'healthcare'];
        $times = [];
        
        foreach ($categories as $categorySlug) {
            $startTime = microtime(true);
            $category = $this->routingService->findCategoryForUrl($categorySlug);
            $endTime = (microtime(true) - $startTime) * 1000;
            
            $times[] = [
                $categorySlug,
                $category ? $category->name : 'Not found',
                number_format($endTime, 2),
                $category ? $category->path : 'N/A'
            ];
        }
        
        $this->table(['Slug', 'Category', 'Time (ms)', 'Path'], $times);
        $this->newLine();
    }

    private function testPlaceLookupQueries()
    {
        $this->info('3. Place Lookup Queries (Old vs New)');
        
        // Get a sample category
        $category = Category::whereNull('parent_id')->first();
        if (!$category) {
            $this->warn('No categories found for testing');
            return;
        }
        
        // Test old recursive method
        $startTime = microtime(true);
        $oldCount = $category->getAllDirectoryEntries()->count();
        $oldTime = (microtime(true) - $startTime) * 1000;
        
        // Test new optimized method
        $startTime = microtime(true);
        $newCount = $category->getAllPlacesOptimized()->count();
        $newTime = (microtime(true) - $startTime) * 1000;
        
        $this->table([
            'Method', 'Count', 'Time (ms)', 'Queries'
        ], [
            ['Old (recursive)', $oldCount, number_format($oldTime, 2), 'Multiple'],
            ['New (single query)', $newCount, number_format($newTime, 2), '1'],
        ]);
        
        if ($oldCount === $newCount) {
            $this->info('✓ Results match between old and new methods');
        } else {
            $this->warn("✗ Results don't match: Old={$oldCount}, New={$newCount}");
        }
        
        $improvement = (($oldTime - $newTime) / $oldTime) * 100;
        $this->info("Performance improvement: " . number_format($improvement, 1) . "%");
        $this->newLine();
    }

    private function testPathBasedQueries()
    {
        $this->info('4. Path-Based Queries');
        
        $paths = ['/restaurants', '/shopping', '/healthcare'];
        $results = [];
        
        foreach ($paths as $path) {
            $startTime = microtime(true);
            $category = Category::findByPath($path);
            $endTime = (microtime(true) - $startTime) * 1000;
            
            $subtreeStart = microtime(true);
            $subtreeCount = $category ? $category->getSubtreeCategories()->count() : 0;
            $subtreeTime = (microtime(true) - $subtreeStart) * 1000;
            
            $results[] = [
                $path,
                $category ? 'Found' : 'Not found',
                number_format($endTime, 2),
                $subtreeCount,
                number_format($subtreeTime, 2)
            ];
        }
        
        $this->table([
            'Path', 'Status', 'Lookup Time (ms)', 'Subtree Count', 'Subtree Time (ms)'
        ], $results);
        $this->newLine();
    }

    private function showQueryStatistics()
    {
        $this->info('Query Statistics & Recommendations');
        $stats = $this->routingService->getQueryStats();
        
        $this->table(['Metric', 'Value'], [
            ['Total Categories', $stats['total_categories']],
            ['Parent Categories', $stats['parent_categories']],
            ['Child Categories', $stats['child_categories']],
            ['Maximum Depth', $stats['max_depth']],
            ['Categories with Places', $stats['categories_with_places']],
            ['Avg Places per Category', number_format($stats['avg_places_per_category'], 1)],
        ]);

        // Performance recommendations
        $this->info('Performance Recommendations:');
        
        if ($stats['max_depth'] > 3) {
            $this->warn('• Consider limiting category depth to 3 levels for optimal performance');
        } else {
            $this->info('✓ Category depth is optimal (≤3 levels)');
        }
        
        if ($stats['avg_places_per_category'] > 100) {
            $this->warn('• Consider adding more subcategories to distribute places more evenly');
        } else {
            $this->info('✓ Place distribution across categories is good');
        }
        
        $this->newLine();
        $this->info('Index Usage Analysis:');
        $this->showIndexUsage();
    }

    private function showIndexUsage()
    {
        // Check if our new indexes are being used
        $indexQueries = [
            'categories_path_index' => "SELECT * FROM categories WHERE path LIKE '/restaurants%'",
            'categories_parent_id_order_index_index' => "SELECT * FROM categories WHERE parent_id IS NULL ORDER BY order_index",
            'categories_hierarchy_depth_idx' => "SELECT * FROM categories WHERE parent_id = 1 AND slug = 'italian'"
        ];

        foreach ($indexQueries as $indexName => $query) {
            $explain = DB::select("EXPLAIN (FORMAT JSON) " . $query);
            $planJson = json_decode($explain[0]->{'QUERY PLAN'}, true);
            
            // Check if the index is being used
            $indexUsed = $this->checkIfIndexUsed($planJson, $indexName);
            
            if ($indexUsed) {
                $this->info("✓ {$indexName} is being used");
            } else {
                $this->warn("✗ {$indexName} may not be optimal");
            }
        }
    }

    private function checkIfIndexUsed($plan, $indexName): bool
    {
        // Simplified check for index usage in PostgreSQL explain plan
        $planStr = json_encode($plan);
        return strpos($planStr, $indexName) !== false;
    }

    private function getQueryCount()
    {
        // This would require query logging to be accurate
        return DB::getQueryLog() ? count(DB::getQueryLog()) : 'Unknown';
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}