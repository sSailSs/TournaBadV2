<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Round extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'round_number',
        'status',
        'generated_at',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function waitingPlayers(): HasMany
    {
        return $this->hasMany(RoundWaitingPlayer::class);
    }
}
