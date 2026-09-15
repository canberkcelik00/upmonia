<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'slug', 'plan'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function monitors(): HasMany
    {
        return $this->hasMany(Monitor::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function alertChannels(): HasMany
    {
        return $this->hasMany(AlertChannel::class);
    }

    public function maintenanceWindows(): HasMany
    {
        return $this->hasMany(MaintenanceWindow::class);
    }

    public function statusPages(): HasMany
    {
        return $this->hasMany(StatusPage::class);
    }

    /**
     * Alert channels have no locale of their own (matches the source schema), so incident
     * alert emails render in the org owner's language rather than per-recipient.
     */
    public function ownerLocale(): string
    {
        $ownerMembership = $this->memberships()->where('role', 'owner')->first()
            ?? $this->memberships()->first();

        return $ownerMembership?->user?->locale ?? config('app.locale');
    }
}
