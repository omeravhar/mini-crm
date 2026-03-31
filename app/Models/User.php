<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;


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
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        ];
    }

    public function ownedLeads()
    {
        return $this->hasMany(Lead::class, 'owner_id');
    }

    public function createdLeads()
    {
        return $this->hasMany(Lead::class, 'created_by');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'owner_id');
    }

    public function authActivityLogs()
    {
        return $this->hasMany(AuthActivityLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }



     public static function checkLoginUser(array $credentials){
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return ['error' => 'email', 'message' => 'Incorrect email or password'];
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            return ['error' => 'password', 'message' => 'Incorrect password'];
        }

        return $user;
    }
}
