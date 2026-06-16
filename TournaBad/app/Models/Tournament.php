<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'creator_id',
        'name',
        'starts_on',
        'courts_count',
        'round_duration_minutes',
        'round_duration_seconds',
        'alarm_audio_path',
        'status',
        'description',
        'format',
        'allow_2v1',
        'allow_1v1',
        'team_assignment_mode',
        'team_size',
        'team_display_mode',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'allow_2v1' => 'bool',
            'allow_1v1' => 'bool',
            'team_size' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(TournamentTeam::class);
    }
}
