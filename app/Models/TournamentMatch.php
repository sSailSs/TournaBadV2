<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TournamentMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'round_id',
        'tournament_id',
        'court_number',
        'match_type',
        'status',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'match_players')
            ->withTrashed()
            ->withPivot('team_number')
            ->withTimestamps();
    }

    public function score(): HasOne
    {
        return $this->hasOne(MatchScore::class, 'tournament_match_id');
    }
}
