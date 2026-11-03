<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->string('ref_document', 50)->nullable();
            $table->date('date');
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['posted', 'cancelled'])->default('posted');
            $table->string('cancel_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['date', 'status']);
        });

        Schema::create('receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cylinder_type_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty_full_in')->default(0);
            $table->unsignedInteger('qty_empty_out')->default(0);
            $table->timestamps();
        });

        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->date('date');
            $table->foreignId('outlet_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['posted', 'cancelled'])->default('posted');
            $table->string('cancel_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['date', 'status']);
        });

        Schema::create('distribution_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cylinder_type_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty_full_out')->default(0);
            $table->unsignedInteger('qty_empty_in')->default(0);
            $table->timestamps();
        });

        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->date('date');
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->string('review_note')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // system_qty dipotret saat opname diajukan (saat hitung fisik selesai).
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cylinder_type_id')->constrained()->restrictOnDelete();
            $table->enum('condition', ['isi', 'kosong', 'rusak']);
            $table->integer('system_qty')->default(0);
            $table->unsignedInteger('physical_qty')->nullable();
            $table->integer('difference')->default(0);
            $table->timestamps();

            $table->unique(['stock_opname_id', 'cylinder_type_id', 'condition'], 'opname_item_unique');
        });

        Schema::create('adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->date('date');
            $table->enum('type', ['rusak', 'hilang', 'koreksi', 'opname']);
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('stock_opname_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->string('review_note')->nullable();
            $table->timestamps();

            $table->index(['date', 'status']);
        });

        // from_condition = kondisi yang berkurang, to_condition = kondisi yang bertambah.
        // rusak: isi/kosong -> rusak | hilang: X -> null | koreksi: X -> null atau null -> X
        Schema::create('adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cylinder_type_id')->constrained()->restrictOnDelete();
            $table->enum('from_condition', ['isi', 'kosong', 'rusak'])->nullable();
            $table->enum('to_condition', ['isi', 'kosong', 'rusak'])->nullable();
            $table->unsignedInteger('qty');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjustment_items');
        Schema::dropIfExists('adjustments');
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('distribution_items');
        Schema::dropIfExists('distributions');
        Schema::dropIfExists('receipt_items');
        Schema::dropIfExists('receipts');
    }
};
