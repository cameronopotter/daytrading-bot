<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'alpaca_key_id',
        'alpaca_secret',
        'alpaca_is_paper',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'alpaca_key_id',
        'alpaca_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'alpaca_key_id' => 'encrypted',
            'alpaca_secret' => 'encrypted',
            'alpaca_is_paper' => 'boolean',
        ];
    }

    public function hasAlpacaCredentials(): bool
    {
        return !empty($this->alpaca_key_id) && !empty($this->alpaca_secret);
    }
}
