<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class SatCredencial extends Model
{
    protected $table = 'sat_credenciales';

    protected $fillable = [
        'user_id', 'rfc', 'nombre', 'cer_path', 'key_path',
        'key_password_encrypted', 'vigencia',
    ];

    protected $casts = ['vigencia' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPassword(): string
    {
        return Crypt::decryptString($this->key_password_encrypted);
    }

    public function getCerContents(): string
    {
        return Storage::disk('local')->get($this->cer_path);
    }

    public function getKeyContents(): string
    {
        return Storage::disk('local')->get($this->key_path);
    }

    public function estaVigente(): bool
    {
        return $this->vigencia === null || $this->vigencia->isFuture();
    }
}
