<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * New accounts are active, matching the column default.
     */
    protected $attributes = [
        'is_active' => true,
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Human label for the role, matching the login page choices.
     */
    public function roleLabel(): string
    {
        return $this->isAdmin() ? 'Healthcare Worker' : 'Patient';
    }

    /**
     * The patient record a portal account belongs to.
     */
    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * The healthcare worker patients message from their portal: the first active staff account.
     */
    public static function careTeamContact(): ?self
    {
        return static::where('role', 'admin')->active()->orderBy('id')->first();
    }

    /**
     * End this account's other sign-ins after a password change, reset or deactivation:
     * deletes its stored sessions (database session driver) and invalidates "remember me".
     */
    public function signOutOtherSessions(?string $keepSessionId = null): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $this->getKey())
                ->when($keepSessionId, fn ($query) => $query->where('id', '!=', $keepSessionId))
                ->delete();
        }

        $this->setRememberToken(Str::random(60));
        $this->save();
    }

    /**
     * Readable temporary password (no symbols) that staff can hand over in person.
     */
    public static function temporaryPassword(): string
    {
        return Str::password(10, symbols: false);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
