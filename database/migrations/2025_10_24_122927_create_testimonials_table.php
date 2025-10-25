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
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_user_id');
            $table->foreign('client_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->text('message');
            $table->enum('media_type', ['image', 'video'])->nullable();
            $table->string('media_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
