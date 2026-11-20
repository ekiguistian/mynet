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
        Schema::table('branches', function (Blueprint $table): void {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('attendance_radius_m')->default(250);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_path')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->longText('mikrotik_password')->nullable();
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->string('check_in_photo_path')->nullable();
            $table->string('check_out_photo_path')->nullable();
            $table->string('check_in_ip', 45)->nullable();
            $table->string('check_out_ip', 45)->nullable();
        });

        Schema::create('finance_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->string('type', 10)->default('both')->index();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['branch_id', 'name', 'type']);
        });

        Schema::table('finance_entries', function (Blueprint $table): void {
            $table->foreignId('finance_category_id')->nullable()
                ->constrained('finance_categories')->nullOnDelete();
        });

        Schema::create('company_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('profile_key', 40)->default('primary')->unique();
            $table->string('company_name', 160)->nullable();
            $table->string('owner_name', 160)->nullable();
            $table->string('owner_phone', 30)->nullable();
            $table->string('responsible_name', 160)->nullable();
            $table->string('responsible_phone', 30)->nullable();
            $table->string('noc_phone', 30)->nullable();
            $table->string('cs_phone', 30)->nullable();
            $table->string('email', 180)->nullable();
            $table->text('address')->nullable();
            $table->json('social_media')->nullable();
            $table->timestamps();
        });

        Schema::create('otp_challenges', function (Blueprint $table): void {
            $table->id();
            $table->uuid('batch_uuid')->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 40)->index();
            $table->string('channel', 20);
            $table->longText('recipient');
            $table->longText('requested_value')->nullable();
            $table->string('code_hash', 255);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'purpose', 'expires_at']);
            $table->index(['batch_uuid', 'channel']);
        });

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 40)->index();
            $table->nullableMorphs('subject');
            $table->string('description')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_entries', function (Blueprint $table): void {
            $table->dropForeign(['finance_category_id']);
            $table->dropColumn('finance_category_id');
        });

        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('otp_challenges');
        Schema::dropIfExists('company_profiles');
        Schema::dropIfExists('finance_categories');

        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropColumn([
                'check_in_latitude', 'check_in_longitude', 'check_out_latitude', 'check_out_longitude',
                'check_in_photo_path', 'check_out_photo_path', 'check_in_ip', 'check_out_ip',
            ]);
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('mikrotik_password');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['avatar_path', 'phone_verified_at']);
        });

        Schema::table('branches', function (Blueprint $table): void {
            $table->dropColumn(['latitude', 'longitude', 'attendance_radius_m']);
        });
    }
};
