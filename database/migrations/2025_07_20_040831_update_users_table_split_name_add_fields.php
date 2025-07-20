<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add new fields
            $table->string('firstname')->after('id')->nullable();
            $table->string('lastname')->after('firstname')->nullable();
            $table->enum('gender', ['male', 'female', 'prefer_not_to_say'])->nullable()->after('lastname');
            $table->date('birthdate')->nullable()->after('gender');
            
            // Add index for performance on name searches
            $table->index(['firstname', 'lastname']);
        });

        // Migrate existing data - split name into firstname and lastname
        $users = DB::table('users')->whereNotNull('name')->get();
        
        foreach ($users as $user) {
            $nameParts = explode(' ', trim($user->name), 2);
            $firstname = $nameParts[0] ?? '';
            $lastname = $nameParts[1] ?? '';
            
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                ]);
        }

        // Make firstname and lastname required after data migration
        Schema::table('users', function (Blueprint $table) {
            $table->string('firstname')->nullable(false)->change();
            $table->string('lastname')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, populate name field from firstname and lastname
        $users = DB::table('users')->whereNotNull('firstname')->get();
        
        foreach ($users as $user) {
            $fullName = trim($user->firstname . ' ' . $user->lastname);
            
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'name' => $fullName,
                ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['firstname', 'lastname']);
            $table->dropColumn(['firstname', 'lastname', 'gender', 'birthdate']);
        });
    }
};
