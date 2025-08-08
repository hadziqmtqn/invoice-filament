<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Backstage\TwoFactorAuth\Enums\TwoFactorType;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class User extends Authenticatable implements HasMedia, FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes, InteractsWithMedia, HasSlug, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
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
            'deleted_at' => 'timestamp',
            'two_factor_type' => TwoFactorType::class,
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        // TODO: Implement getSlugOptions() method.
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('username');
    }

    public function userProfile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'user_id');
    }

    // more
    protected function defaultAvatar(): Attribute
    {
        return Attribute::make(fn() => url('https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=random&size=128'));
    }

    protected function receivables(): Attribute
    {
        return Attribute::make(fn() => $this->invoices->sum(function (Invoice $invoice) {
            return $invoice->total_due ?? 0;
        }));
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->default_avatar ?: 'https://ui-avatars.com/api/?name=' . urlencode($this->name);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // TODO: Implement canAccessPanel() method.
        return $this->hasPermissionTo('access panel');
    }

    public function getRouteKeyName(): string
    {
        return 'username';
    }
}
