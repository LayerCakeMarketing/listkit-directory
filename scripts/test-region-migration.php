<?php

/**
 * Test script to verify region migration success
 * Run this after the migration to ensure everything worked correctly
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Region;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== REGION MIGRATION TEST RESULTS ===\n\n";

// Test 1: Check for remaining duplicates
echo "1. CHECKING FOR DUPLICATE SLUGS:\n";
$duplicates = Region::select('slug')
    ->whereNotNull('slug')
    ->groupBy('slug')
    ->havingRaw('COUNT(*) > 1')
    ->get();

if ($duplicates->count() === 0) {
    echo "   ✅ SUCCESS: No duplicate slugs found\n";
} else {
    echo "   ❌ FAILURE: {$duplicates->count()} duplicate slugs still exist:\n";
    foreach ($duplicates as $duplicate) {
        echo "      - {$duplicate->slug}\n";
    }
}

// Test 2: Check unique constraint exists
echo "\n2. CHECKING UNIQUE CONSTRAINT ON SLUG:\n";
try {
    $indexes = DB::select("
        SELECT indexname, indexdef 
        FROM pg_indexes 
        WHERE tablename = 'regions' 
        AND indexdef LIKE '%UNIQUE%' 
        AND indexdef LIKE '%slug%'
    ");
    
    if (count($indexes) > 0) {
        echo "   ✅ SUCCESS: Unique constraint on slug exists\n";
        foreach ($indexes as $index) {
            echo "      - {$index->indexname}\n";
        }
    } else {
        echo "   ❌ FAILURE: No unique constraint found on slug column\n";
    }
} catch (Exception $e) {
    echo "   ❌ ERROR: Could not check constraints: {$e->getMessage()}\n";
}

// Test 3: Check that specific duplicate regions were removed
echo "\n3. CHECKING SPECIFIC DUPLICATE REMOVAL:\n";
$shouldBeRemoved = [90, 91, 92, 94, 95]; // IDs that should be deleted
$shouldExist = [82, 86, 6, 84, 85]; // IDs that should remain

$removedCount = 0;
foreach ($shouldBeRemoved as $id) {
    $exists = Region::find($id);
    if (!$exists) {
        $removedCount++;
    } else {
        echo "   ❌ ERROR: Region ID {$id} should have been removed but still exists\n";
    }
}

$existCount = 0;
foreach ($shouldExist as $id) {
    $exists = Region::find($id);
    if ($exists) {
        $existCount++;
    } else {
        echo "   ❌ ERROR: Region ID {$id} should exist but was removed\n";
    }
}

if ($removedCount === count($shouldBeRemoved) && $existCount === count($shouldExist)) {
    echo "   ✅ SUCCESS: All duplicate regions properly removed and kept regions preserved\n";
} else {
    echo "   ❌ PARTIAL: {$removedCount}/" . count($shouldBeRemoved) . " duplicates removed, {$existCount}/" . count($shouldExist) . " kept regions preserved\n";
}

// Test 4: Check foreign key integrity
echo "\n4. CHECKING FOREIGN KEY INTEGRITY:\n";

// Check directory_entries
$orphanedDirEntries = DB::table('directory_entries as de')
    ->leftJoin('regions as r1', 'de.state_region_id', '=', 'r1.id')
    ->leftJoin('regions as r2', 'de.city_region_id', '=', 'r2.id')
    ->leftJoin('regions as r3', 'de.neighborhood_region_id', '=', 'r3.id')
    ->leftJoin('regions as r4', 'de.region_id', '=', 'r4.id')
    ->where(function($query) {
        $query->where(function($q) {
            $q->whereNotNull('de.state_region_id')->whereNull('r1.id');
        })->orWhere(function($q) {
            $q->whereNotNull('de.city_region_id')->whereNull('r2.id');
        })->orWhere(function($q) {
            $q->whereNotNull('de.neighborhood_region_id')->whereNull('r3.id');
        })->orWhere(function($q) {
            $q->whereNotNull('de.region_id')->whereNull('r4.id');
        });
    })
    ->count();

// Check place_regions
$orphanedPlaceRegions = DB::table('place_regions')
    ->leftJoin('regions', 'place_regions.region_id', '=', 'regions.id')
    ->whereNull('regions.id')
    ->count();

// Check child regions
$orphanedChildren = DB::table('regions as children')
    ->leftJoin('regions as parents', 'children.parent_id', '=', 'parents.id')
    ->whereNotNull('children.parent_id')
    ->whereNull('parents.id')
    ->count();

$totalOrphaned = $orphanedDirEntries + $orphanedPlaceRegions + $orphanedChildren;

if ($totalOrphaned === 0) {
    echo "   ✅ SUCCESS: No orphaned foreign key references found\n";
} else {
    echo "   ❌ FAILURE: {$totalOrphaned} orphaned references found:\n";
    echo "      - Directory entries: {$orphanedDirEntries}\n";
    echo "      - Place regions: {$orphanedPlaceRegions}\n";
    echo "      - Child regions: {$orphanedChildren}\n";
}

// Test 5: Check park region types
echo "\n5. CHECKING PARK REGION SUPPORT:\n";

// Check if park columns exist
$parkColumns = ['park_system', 'park_designation', 'area_acres', 'established_date'];
$missingColumns = [];

foreach ($parkColumns as $column) {
    try {
        DB::table('regions')->select($column)->limit(1)->get();
    } catch (Exception $e) {
        $missingColumns[] = $column;
    }
}

if (empty($missingColumns)) {
    echo "   ✅ SUCCESS: All park columns exist\n";
    
    // Check for sample parks
    $parkCount = Region::whereIn('type', ['national_park', 'state_park'])->count();
    if ($parkCount > 0) {
        echo "   ✅ SUCCESS: {$parkCount} sample park regions created\n";
        
        $sampleParks = Region::whereIn('type', ['national_park', 'state_park'])
            ->select('name', 'type', 'park_system')
            ->get();
        
        foreach ($sampleParks as $park) {
            echo "      - {$park->name} ({$park->type}) - {$park->park_system}\n";
        }
    } else {
        echo "   ⚠️  WARNING: No sample parks created\n";
    }
} else {
    echo "   ❌ FAILURE: Missing park columns: " . implode(', ', $missingColumns) . "\n";
}

// Test 6: Verify data integrity
echo "\n6. VERIFYING DATA INTEGRITY:\n";

// Check that kept regions have expected data
$california = Region::where('slug', 'california')->first();
$mammothLakes = Region::where('slug', 'mammoth-lakes')->first();
$irvine = Region::where('slug', 'irvine')->first();

$integrityTests = [
    'California exists' => $california !== null,
    'California has children' => $california && $california->children()->count() > 5,
    'Mammoth Lakes exists' => $mammothLakes !== null,
    'Mammoth Lakes has proper parent' => $mammothLakes && $mammothLakes->parent_id === ($california ? $california->id : null),
    'Irvine exists' => $irvine !== null,
    'Irvine has children' => $irvine && $irvine->children()->count() > 0,
];

$passed = 0;
$total = count($integrityTests);

foreach ($integrityTests as $test => $result) {
    if ($result) {
        echo "   ✅ {$test}\n";
        $passed++;
    } else {
        echo "   ❌ {$test}\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Data integrity tests: {$passed}/{$total} passed\n";

if ($duplicates->count() === 0 && $totalOrphaned === 0 && $passed === $total) {
    echo "🎉 MIGRATION SUCCESSFUL! All tests passed.\n";
    exit(0);
} else {
    echo "⚠️  MIGRATION ISSUES DETECTED. Please review the failures above.\n";
    exit(1);
}