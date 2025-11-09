<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompetitionIndexRequest;
use App\Http\Resources\CompetitionResource;
use App\Models\League;
use Illuminate\Http\JsonResponse;

class CompetitionController extends Controller
{
    public function index(CompetitionIndexRequest $request): JsonResponse
    {
        $region  = (string) $request->query('region', '');
        $season  = (string) $request->query('season', '');
        $search  = (string) $request->query('search', '');
        $perPage = (int) $request->query('per_page', 12);
        $gender  = (string) $request->query('gender', ''); 
        $level   = (string) $request->query('level', '');  

        $query = League::query()
            ->select('leagues.*')
            ->with(['region:id,name,code', 'season:id,code,start_date', 'category:id,name,level,gender'])
            ->leftJoin('seasons', 'seasons.id', '=', 'leagues.season_id')
            ->official()
            ->public();

        if ($region !== '') {
            $query->whereHas('region', fn ($q) => $q->where('code', $region));
        }

        if ($season !== '') {
            $query->whereHas('season', fn ($q) => $q->where('code', $season));
        }

        if ($search !== '') {
            $query->where('leagues.name', 'like', '%'.str_replace('%','\%',$search).'%');
        }

        if ($gender !== '' || $level !== '') {
            $query->whereHas('category', function ($q) use ($gender, $level) {
                if ($gender !== '') $q->where('gender', $gender);
                if ($level  !== '') $q->where('level',  $level);
            });
        }

        $query->orderByDesc('seasons.start_date')->orderBy('leagues.name');

        $paginator = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => CompetitionResource::collection($paginator)->resolve(),
            'meta' => [
                'page'     => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total'    => $paginator->total(),
            ],
        ]);
    }
}
