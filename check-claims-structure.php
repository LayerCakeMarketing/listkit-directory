<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Claims table structure ===\n";
$columns = \DB::select("
    SELECT column_name, data_type, is_nullable, column_default
    FROM information_schema.columns
    WHERE table_name = 'claims'
    ORDER BY ordinal_position
");

foreach ($columns as $column) {
    echo "{$column->column_name}: {$column->data_type}";
    if ($column->is_nullable === 'NO') echo " NOT NULL";
    if ($column->column_default) echo " DEFAULT {$column->column_default}";
    echo "\n";
}

echo "\n=== Sample claims data ===\n";
$claims = \DB::table('claims')->limit(5)->get();
foreach ($claims as $claim) {
    echo json_encode($claim) . "\n";
}