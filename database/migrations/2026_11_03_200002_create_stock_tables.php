<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Saldo terkini per jenis tabung + kondisi. Hanya diubah lewat StockService.
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cylinder_type_id')->constrained()->restrictOnDelete();
            $table->enum('condition', ['isi', 'kosong', 'rusak']);
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->unique(['cylinder_type_id', 'condition']);
        });

        // Buku besar (ledger) setiap mutasi stok, qty bertanda (+/-).
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cylinder_type_id')->constrained()->restrictOnDelete();
            $table->enum('condition', ['isi', 'kosong', 'rusak']);
            $table->string('type', 20);
            $table->integer('qty');
            $table->integer('balance_after');
            $table->string('reference_type', 50);
            $table->unsignedBigInteger('reference_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->dateTime('moved_at');
            $table->timestamps();

            $table->index(['cylinder_type_id', 'condition', 'moved_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        // Penghitung nomor dokumen per prefix per bulan (PN/SJ/PY/SO).
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 5);
            $table->string('period', 6);
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['prefix', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stocks');
    }
};
