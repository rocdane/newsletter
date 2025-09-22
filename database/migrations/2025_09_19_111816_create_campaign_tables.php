<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\CampaignStatus;
use App\Enums\EmailStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('subject');
            $table->longText('content');
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('status')->default(CampaignStatus::DRAFT->value);
            $table->string('batch_id')->nullable();
            $table->timestamps();
            $table->json('metadata')->nullable();
            $table->index(['status', 'created_at']);
        });

        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['email', 'is_active']);
        });

        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->constrained()->onDelete('cascade');
            $table->foreignId('campaign_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('tracking_token')->unique();
            $table->string('status')->default(EmailStatus::PENDING->value);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamps();
            $table->json('metadata')->nullable();
            $table->index(['status', 'delivered_at']);
            $table->index('tracking_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('subscribers');
        Schema::dropIfExists('emails');
    }
};
