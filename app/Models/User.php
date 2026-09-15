<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Mail\ResetPasswordEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

#[Fillable(['name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * v1 only ever creates one org per user at signup; the schema supports more,
     * but no UI exists yet to switch between them.
     */
    public function currentOrganization(): ?Organization
    {
        return $this->organizations()->first();
    }

    /**
     * Sends our own bilingual Mailable instead of Laravel's default
     * Illuminate\Auth\Notifications\ResetPassword notification, which only ever renders
     * in English. See App\Mail\ResetPasswordEmail.
     */
    public function sendPasswordResetNotification($token): void
    {
        Mail::to($this->email)->send(new ResetPasswordEmail($this, $token));
    }
}
