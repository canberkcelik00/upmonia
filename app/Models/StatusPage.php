<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StatusPage extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'client_id', 'slug', 'title', 'brand_color', 'logo_url',
        'enabled', 'show_history', 'indexable',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'show_history' => 'boolean',
            'indexable' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Explicit opt-in join — a status page never implicitly shows "all monitors".
     */
    public function monitors(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class, 'status_page_monitors')
            ->withPivot('display_name', 'sort_order')
            ->orderByPivot('sort_order');
    }
}
