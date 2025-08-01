<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only add user_id if it doesn't exist
        if (!Schema::hasColumn('claims', 'user_id')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->foreignId('user_id')->after('place_id')->constrained()->onDelete('cascade');
                
                // Add index on user_id only (status might not exist yet)
                $table->index('user_id');
            });
        }
        
        // Also add other missing columns from the model if they don't exist
        Schema::table('claims', function (Blueprint $table) {
            if (!Schema::hasColumn('claims', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending')->after('user_id');
            }
            
            if (!Schema::hasColumn('claims', 'verification_method')) {
                $table->enum('verification_method', ['email', 'phone', 'document', 'manual'])->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('claims', 'business_email')) {
                $table->string('business_email')->nullable();
            }
            
            if (!Schema::hasColumn('claims', 'business_phone')) {
                $table->string('business_phone')->nullable();
            }
            
            if (!Schema::hasColumn('claims', 'verification_notes')) {
                $table->text('verification_notes')->nullable();
            }
            
            if (!Schema::hasColumn('claims', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
            
            if (!Schema::hasColumn('claims', 'verified_at')) {
                $table->timestamp('verified_at')->nullable();
            }
            
            if (!Schema::hasColumn('claims', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            
            if (!Schema::hasColumn('claims', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable();
            }
            
            if (!Schema::hasColumn('claims', 'expires_at')) {
                $table->timestamp('expires_at')->nullable();
            }
            
            if (!Schema::hasColumn('claims', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users');
            }
            
            if (!Schema::hasColumn('claims', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->constrained('users');
            }
            
            if (!Schema::hasColumn('claims', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
        
        // Add composite index after ensuring both columns exist
        if (Schema::hasColumn('claims', 'user_id') && Schema::hasColumn('claims', 'status')) {
            Schema::table('claims', function (Blueprint $table) {
                // Check if index already exists
                $indexName = 'claims_user_id_status_index';
                $indexes = \DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'claims' AND indexname = ?", [$indexName]);
                
                if (empty($indexes)) {
                    $table->index(['user_id', 'status']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            if (Schema::hasColumn('claims', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};