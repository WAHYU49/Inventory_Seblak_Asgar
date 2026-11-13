<?php

namespace App\Http\Controllers\Concerns;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait ProcessesIdempotentTransactions
{
    protected function processIdempotentTransaction(
        string $table,
        string $key,
        Closure $callback
    ): void {
        if (DB::table($table)->where('idempotency_key', $key)->exists()) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'Transaksi ini sudah pernah diproses. Silakan buat transaksi baru.',
            ]);
        }

        try {
            DB::transaction($callback);
        } catch (QueryException $exception) {
            if (! DB::table($table)->where('idempotency_key', $key)->exists()) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'idempotency_key' => 'Transaksi ini sudah pernah diproses. Silakan buat transaksi baru.',
            ]);
        }
    }
}
