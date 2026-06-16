<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TournamentTeam extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tournament_id',
        'name',
        'team_label',
        'player_one_id',
        'player_two_id',
        'player_three_id',
        'position',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function playerOne(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_one_id');
    }

    public function playerTwo(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_two_id');
    }

    public function playerThree(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_three_id');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'tournament_team_players')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }
}
