<?php

namespace App\Models\Ateliers;

use App\Enums\TypeService;
use Database\Factories\ServiceAtelierFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(ServiceAtelierFactory::class)]
class ServiceAtelier extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'nom', 'description', 'prix', 'duree', 'delai_jours', 'actif'];

    protected $attributes = ['actif' => true];

    protected function casts(): array
    {
        return [
            'type' => TypeService::class,
            'prix' => 'decimal:2',
            'duree' => 'integer',
            'delai_jours' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    public function prixFormate(): string
    {
        return $this->prix !== null ? 'dès '.number_format($this->prix, 2, ',', ' ').' €' : 'Sur devis';
    }

    public function dureeFormatee(): ?string
    {
        if (! $this->duree) {
            return null;
        }

        return (intdiv($this->duree, 60) ? intdiv($this->duree, 60).' h ' : '').($this->duree % 60 ? ($this->duree % 60).' min' : '');
    }
}
