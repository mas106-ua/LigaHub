<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Acta del partido</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .header h2 {
            margin: 0;
        }
        .subheader {
            font-size: 13px;
            margin-top: 5px;
        }
        table.teams {
            width: 100%;
            margin: 30px 0;
            text-align: center;
            border-collapse: collapse;
        }
        table.teams td {
            padding: 10px;
            font-size: 14px;
        }
        .score {
            font-size: 26px;
            font-weight: bold;
        }
        .info {
            margin-top: 20px;
        }
        .info div {
            margin-bottom: 5px;
        }
        .footer {
            margin-top: 50px;
            font-size: 10px;
            text-align: center;
            color: #666;
        }
        hr {
            margin: 25px 0;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>{{ $league->name }}</h2>
    <div class="subheader">Jornada {{ $match->matchday_number }}</div>
</div>

<table class="teams">
    <tr>
        <td style="width: 40%;">{{ $homeTeam->name }}</td>
        <td class="score" style="width: 20%;">
            {{ $match->home_goals }} - {{ $match->away_goals }}
        </td>
        <td style="width: 40%;">{{ $awayTeam->name }}</td>
    </tr>
</table>

<hr>

<div class="info">
    <div><strong>Estado:</strong> {{ ucfirst($match->status) }}</div>
    <div><strong>Fecha:</strong>
        {{ $match->scheduled_at ? \Carbon\Carbon::parse($match->scheduled_at)->format('d/m/Y H:i') : '—' }}
    </div>
</div>

<div class="footer">
    Acta generada automáticamente · {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
