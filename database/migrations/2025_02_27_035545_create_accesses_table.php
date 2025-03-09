<?php

use App\Models\Files;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'users_id');
            $table->foreignIdFor(Files::class, 'files_id');
            $table->string('type');
            $table->string('fullname');
            $table->timestamps();
        });
    }   
    public function down(): void
    {
        Schema::dropIfExists('accesses');
    }
};
