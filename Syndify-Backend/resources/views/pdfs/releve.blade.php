<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Relevé des Opérations</title>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 13px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; color: #1E3A34; font-size: 24px; }
        .info { margin-bottom: 20px; }
        .info p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #f4f5f7; color: #555; font-weight: bold; text-align: left; padding: 10px; border-bottom: 2px solid #ddd; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .text-green { color: #16a34a; }
        .text-red { color: #dc2626; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Relevé des Opérations</h1>
        <p>Budget {{ ucfirst($type) }} - Exercice: {{ $exercice }}</p>
    </div>

    <div class="info">
        <p><strong>Résidence :</strong> {{ $residence->nom ?? 'Non définie' }}</p>
        <p><strong>Date d'édition :</strong> {{ date('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type d'opération</th>
                <th>Libellé</th>
                <th class="text-right">Montant (MAD)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($operations as $op)
            <tr>
                <td>{{ \Carbon\Carbon::parse($op->date)->format('d/m/Y') }}</td>
                <td>{{ $op->type }}</td>
                <td>{{ $op->libelle }}</td>
                <td class="text-right">
                    @if($op->montant > 0)
                        <strong class="text-green">+{{ number_format($op->montant, 2, ',', ' ') }}</strong>
                    @else
                        <strong class="text-red">{{ number_format($op->montant, 2, ',', ' ') }}</strong>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>