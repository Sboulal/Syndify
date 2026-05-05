<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Relevé des Opérations</title>
    <style>
        /* 🟢 IMPORT L-POLICE POPPINS */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        body { 
            font-family: 'Poppins', Helvetica, Arial, sans-serif; 
            font-size: 12px; 
            color: #334155; 
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        .header { 
            text-align: center; 
            margin-bottom: 40px; 
            padding-top: 20px;
        }

        /* 🟢 LOGO-LIKE SHAPE */
        .logo-box {
            width: 45px;
            height: 45px;
            background-color: #0B0F24;
            border-radius: 10px;
            margin: 0 auto 20px auto;
        }

        .header h1 { 
            margin: 0; 
            color: #0B0F24; 
            font-size: 26px; 
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .header p {
            margin: 5px 0;
            color: #64748b;
            font-weight: 500;
            font-size: 14px;
        }

        .info-section { 
            margin-bottom: 30px; 
            padding: 0 20px;
        }

        .info-section p { 
            margin: 4px 0; 
            font-weight: 500;
        }

        .info-label {
            color: #64748b;
            font-weight: 600;
            margin-right: 5px;
        }

        .info-value {
            color: #0B0F24;
            font-weight: 700;
        }

        table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0;
            margin-top: 10px; 
        }

        th { 
            background-color: #f8fafc; 
            color: #0B0F24; 
            font-weight: 700; 
            text-align: left; 
            padding: 12px 15px; 
            border-bottom: 2px solid #e2e8f0; 
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        td { 
            padding: 12px 15px; 
            border-bottom: 1px solid #f1f5f9; 
            vertical-align: middle;
        }

        .text-right { text-align: right; }
        
        /* 🟢 COLORS FOR AMOUNTS */
        .text-green { 
            color: #10b981; 
            font-weight: 700;
        }

        .text-red { 
            color: #ef4444; 
            font-weight: 700;
        }

        .badge-type {
            padding: 4px 8px;
            background: #f1f5f9;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            color: #475569;
        }

        .footer {
            position: fixed;
            bottom: 20px;
            width: 100%;
            text-align: center;
            color: #94a3b8;
            font-size: 10px;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo-box"></div>
        <h1>Relevé des Opérations</h1>
        <p>Budget {{ ucfirst($type) }} — Exercice {{ $exercice }}</p>
    </div>

    <div class="info-section">
        <p><span class="info-label">Résidence :</span> <span class="info-value">{{ $residence->nom ?? 'Non définie' }}</span></p>
        <p><span class="info-label">Date d'édition :</span> <span class="info-value">{{ date('d/m/Y H:i') }}</span></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Libellé</th>
                <th class="text-right">Montant (MAD)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($operations as $op)
            <tr>
                <td style="color: #64748b; font-weight: 600;">
                    {{ \Carbon\Carbon::parse($op->date)->format('d/m/Y') }}
                </td>
                <td><span class="badge-type">{{ $op->type }}</span></td>
                <td style="font-weight: 500; color: #1e293b;">{{ $op->libelle }}</td>
                <td class="text-right">
                    @if($op->montant > 0)
                        <span class="text-green">+{{ number_format($op->montant, 2, ',', ' ') }}</span>
                    @else
                        <span class="text-red">{{ number_format($op->montant, 2, ',', ' ') }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Document généré officiellement par le système Syndify — {{ date('Y') }}
    </div>

</body>
</html>