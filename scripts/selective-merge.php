<?php

/**
 * Selective Database Merge Script
 * 
 * This script carefully merges production regions and directory_entries
 * while preserving local development data and handling column differences
 */

$config = [
    'local' => [
        'host' => 'localhost',
        'dbname' => 'illum_local',
        'user' => 'ericslarson',
        'password' => ''
    ],
    'temp_prod' => [
        'host' => 'localhost',
        'dbname' => 'temp_production',
        'user' => 'ericslarson',
        'password' => ''
    ]
];

// Connect to databases
try {
    $localDb = new PDO("pgsql:host={$config['local']['host']};dbname={$config['local']['dbname']}", 
                       $config['local']['user'], 
                       $config['local']['password']);
    $localDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $prodDb = new PDO("pgsql:host={$config['temp_prod']['host']};dbname={$config['temp_prod']['dbname']}", 
                      $config['temp_prod']['user'], 
                      $config['temp_prod']['password']);
    $prodDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to databases\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

// Function to get common columns between two tables
function getCommonColumns($db1, $db2, $table) {
    $query = "SELECT column_name FROM information_schema.columns 
              WHERE table_name = :table AND table_schema = 'public' 
              ORDER BY ordinal_position";
    
    $stmt1 = $db1->prepare($query);
    $stmt1->execute(['table' => $table]);
    $cols1 = $stmt1->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt2 = $db2->prepare($query);
    $stmt2->execute(['table' => $table]);
    $cols2 = $stmt2->fetchAll(PDO::FETCH_COLUMN);
    
    return array_intersect($cols1, $cols2);
}

try {
    echo "\n=== Starting Selective Merge ===\n";
    
    // Begin transaction
    $localDb->beginTransaction();
    
    // 1. Backup local development data
    echo "\n1. Backing up local development data...\n";
    $localDb->exec("CREATE TEMP TABLE temp_likes AS SELECT * FROM likes");
    $localDb->exec("CREATE TEMP TABLE temp_comments AS SELECT * FROM comments");
    $localDb->exec("CREATE TEMP TABLE temp_reposts AS SELECT * FROM reposts");
    $localDb->exec("CREATE TEMP TABLE temp_list_chains AS SELECT * FROM list_chains");
    $localDb->exec("CREATE TEMP TABLE temp_list_chain_items AS SELECT * FROM list_chain_items");
    echo "✓ Backed up development data\n";
    
    // 2. Get common columns for regions
    echo "\n2. Analyzing table structures...\n";
    $regionColumns = getCommonColumns($localDb, $prodDb, 'regions');
    $regionColumnsList = implode(', ', array_map(function($col) { return '"' . $col . '"'; }, $regionColumns));
    echo "✓ Common region columns: " . count($regionColumns) . "\n";
    
    // 3. Get common columns for directory_entries
    $entryColumns = getCommonColumns($localDb, $prodDb, 'directory_entries');
    $entryColumnsList = implode(', ', array_map(function($col) { return '"' . $col . '"'; }, $entryColumns));
    echo "✓ Common directory_entries columns: " . count($entryColumns) . "\n";
    
    // 4. Clear existing data
    echo "\n3. Clearing existing regions and directory_entries...\n";
    $localDb->exec("SET session_replication_role = 'replica'");
    $localDb->exec("TRUNCATE TABLE regions CASCADE");
    $localDb->exec("TRUNCATE TABLE directory_entries CASCADE");
    echo "✓ Cleared existing data\n";
    
    // 5. Copy regions
    echo "\n4. Copying regions from production...\n";
    $stmt = $prodDb->query("SELECT $regionColumnsList FROM regions");
    $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $insertStmt = $localDb->prepare(
        "INSERT INTO regions (" . $regionColumnsList . ") VALUES (" . 
        implode(', ', array_map(function($col) { return ':' . $col; }, $regionColumns)) . ")"
    );
    
    foreach ($regions as $region) {
        $insertStmt->execute($region);
    }
    echo "✓ Imported " . count($regions) . " regions\n";
    
    // 6. Copy directory_entries
    echo "\n5. Copying directory_entries from production...\n";
    $stmt = $prodDb->query("SELECT $entryColumnsList FROM directory_entries");
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($entries) > 0) {
        $insertStmt = $localDb->prepare(
            "INSERT INTO directory_entries (" . $entryColumnsList . ") VALUES (" . 
            implode(', ', array_map(function($col) { return ':' . $col; }, $entryColumns)) . ")"
        );
        
        foreach ($entries as $entry) {
            $insertStmt->execute($entry);
        }
        echo "✓ Imported " . count($entries) . " directory entries\n";
    } else {
        echo "⚠ No directory entries found in production\n";
    }
    
    // 7. Restore development data
    echo "\n6. Restoring local development data...\n";
    $localDb->exec("INSERT INTO likes SELECT * FROM temp_likes");
    $localDb->exec("INSERT INTO comments SELECT * FROM temp_comments");
    $localDb->exec("INSERT INTO reposts SELECT * FROM temp_reposts");
    $localDb->exec("INSERT INTO list_chains SELECT * FROM temp_list_chains");
    $localDb->exec("INSERT INTO list_chain_items SELECT * FROM temp_list_chain_items");
    echo "✓ Restored development data\n";
    
    // 8. Re-enable constraints and update sequences
    echo "\n7. Finalizing...\n";
    $localDb->exec("SET session_replication_role = 'origin'");
    
    // Update sequences
    $localDb->exec("SELECT setval('regions_id_seq', COALESCE((SELECT MAX(id) FROM regions), 1))");
    $localDb->exec("SELECT setval('directory_entries_id_seq', COALESCE((SELECT MAX(id) FROM directory_entries), 1))");
    
    // Commit transaction
    $localDb->commit();
    echo "✓ Transaction committed\n";
    
    // 9. Verify results
    echo "\n=== Verification ===\n";
    $counts = $localDb->query("
        SELECT 'Regions' as table_name, COUNT(*) as count FROM regions
        UNION ALL SELECT 'Directory Entries', COUNT(*) FROM directory_entries
        UNION ALL SELECT 'Likes', COUNT(*) FROM likes
        UNION ALL SELECT 'Comments', COUNT(*) FROM comments
        UNION ALL SELECT 'Reposts', COUNT(*) FROM reposts
        UNION ALL SELECT 'List Chains', COUNT(*) FROM list_chains
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($counts as $row) {
        echo sprintf("%-20s: %d\n", $row['table_name'], $row['count']);
    }
    
    echo "\n✓ Merge completed successfully!\n";
    
} catch (Exception $e) {
    $localDb->rollBack();
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Transaction rolled back.\n";
    exit(1);
}