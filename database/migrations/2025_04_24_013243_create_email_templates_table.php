<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // $table->string('slug')->unique();
            $table->json('content_json');
            $table->text('content_html')->nullable();
            $table->boolean('is_autosave')->default(false);
            // $table->foreignId('parent_id')->nullable()->constrained('email_templates');
            $table->timestamps();

            $table->index(['user_id', 'is_autosave']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_templates');
    }
};
