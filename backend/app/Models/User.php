<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use App\Models\League;
use App\Models\CompetitionAdminScope;
use App\Models\MatchModel;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar_url',
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
        ];
    }

    // Un superadmin puede tocar todo
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    // Scopes de admin por nivel/CCAA
    public function competitionAdminScopes()
    {
        return $this->hasMany(CompetitionAdminScope::class);
    }

    /**
     * ¿Puede este usuario gestionar esta liga?
     *
     * Cubre:
     *  - superadmin global
     *  - owner de la liga
     *  - admin/owner en league_memberships
     *  - admin por level+region en competition_admin_scopes
     */
    public function canManageLeague(League $league): bool
    {
        // 1) Superadmin
        if ($this->isSuperAdmin()) {
            return true;
        }

        // 2) Owner directo de la liga (para privadas, por ejemplo)
        if ($league->owner_user_id && $league->owner_user_id === $this->id) {
            return true;
        }

        // 3) Admin/owner en league_memberships
        $isLeagueAdmin = DB::table('league_memberships')
            ->where('league_id', $league->id)
            ->where('user_id', $this->id)
            ->whereIn('role_in_league', ['owner', 'admin'])
            ->exists();

        if ($isLeagueAdmin) {
            return true;
        }

        // 4) Scope por nivel (pro/semi/amateur) + region (CCAA)
        $league->loadMissing('category', 'region');

        $level    = optional($league->category)->level; // pro / semi / amateur
        $regionId = $league->region_id;

        return $this->competitionAdminScopes()
            ->where(function ($q) use ($level) {
                // level NULL = cualquier nivel
                $q->whereNull('level');
                if ($level) {
                    $q->orWhere('level', $level);
                }
            })
            ->where(function ($q) use ($regionId) {
                // region_id NULL = todas las CCAA
                $q->whereNull('region_id');
                if ($regionId) {
                    $q->orWhere('region_id', $regionId);
                }
            })
            ->exists();
    }

    public function canManageCompetition(Competition $competition): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->role !== 'admin') {
            return false;
        }

        // b) Admin/owner de alguna League de esa Competition
        $hasLeagueAccess = DB::table('leagues as l')
            ->where('l.competition_id', $competition->id)
            ->where(function ($q) {
                $q->where('l.owner_user_id', $this->id)
                  ->orWhereExists(function ($sq) {
                      $sq->select(DB::raw(1))
                          ->from('league_memberships as lm')
                          ->whereColumn('lm.league_id', 'l.id')
                          ->where('lm.user_id', $this->id)
                          ->whereIn('lm.role_in_league', ['owner', 'admin']);
                  });
            })
            ->exists();

        if ($hasLeagueAccess) {
            return true;
        }

        // a) Scopes por level + region
        return $this->competitionAdminScopes()
            ->where(function ($q) use ($competition) {
                $q->whereNull('level');
                if ($competition->level) {
                    $q->orWhere('level', $competition->level);
                }
            })
            ->where(function ($q) use ($competition) {
                $q->whereNull('region_id');
                if ($competition->region_id) {
                    $q->orWhere('region_id', $competition->region_id);
                }
            })
            ->exists();
    }

    public function canManageMatch(MatchModel $match): bool
    {
        // 1) Superadmin global
        if ($this->isSuperAdmin()) {
            return true;
        }

        // 2) Reutilizamos canManageLeague sobre la liga del partido
        //    Si la relación ya viene cargada, usamos esa; si no, la pedimos.
        $league = $match->relationLoaded('league')
            ? $match->league
            : $match->league()->first();

        if (!$league) {
            return false;
        }

        return $this->canManageLeague($league);
    }

}
