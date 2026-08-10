<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Penanda "sudah dibaca sampai pesan mana" per orang per percakapan.
 *
 * Menyimpan batas baca, bukan status per pesan: dengan begitu jumlah barisnya
 * tetap sekecil jumlah percakapan, bukan tumbuh mengikuti jumlah pesan.
 */
class InternalMessageRead extends Model
{
    protected $fillable = ['user_id', 'conversation_key', 'last_read_message_id'];
}
