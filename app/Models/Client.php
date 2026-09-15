<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use BelongsToOrganization;

    public $timestamps = false;

    protected $fillable = ['organization_id', 'name', 'logo_url', 'brand_color', 'contact_emails'];

    protected function casts(): array
    {
        return [
            'contact_emails' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function monitors(): HasMany
    {
        return $this->hasMany(Monitor::class);
    }

    public function alertChannels(): HasMany
    {
        return $this->hasMany(AlertChannel::class);
    }

    public function statusPages(): HasMany
    {
        return $this->hasMany(StatusPage::class);
    }
}
