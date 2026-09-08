<?php

namespace Tests;

use App\Models\Account;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Buat/ambil akun COA untuk kebutuhan test.
     *
     * Sebagian kode akun (1101, 1103, 2103, 5101, 6203, dst) sudah di-seed oleh
     * migrasi 2026_09_01. Account::create() pada kode yang sama otomatis kena
     * unique constraint accounts.code — jadi test wajib lewat sini, bukan
     * create() langsung.
     */
    protected function makeAccount(array $attributes): Account
    {
        $attributes += ['opening_balance' => 0, 'current_balance' => 0];

        $account = Account::firstOrCreate(
            ['code' => $attributes['code']],
            $attributes
        );

        // Kode yang sudah ada dari migrasi bisa punya nama/tipe berbeda dengan
        // yang diharapkan test — samakan supaya assertion-nya tetap bermakna.
        $account->fill($attributes)->save();

        return $account;
    }
}
