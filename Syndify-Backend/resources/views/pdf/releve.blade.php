<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Relevé des Opérations</title>
    <style>
        /* 🟢 Font Poppins */
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 400;
            src: url('{{ public_path("fonts/Poppins-Regular.ttf") }}') format('truetype');
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 500;
            src: url('{{ public_path("fonts/Poppins-Medium.ttf") }}') format('truetype');
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 600;
            src: url('{{ public_path("fonts/Poppins-SemiBold.ttf") }}') format('truetype');
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 700;
            src: url('{{ public_path("fonts/Poppins-Bold.ttf") }}') format('truetype');
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 800;
            src: url('{{ public_path("fonts/Poppins-ExtraBold.ttf") }}') format('truetype');
        }

        /* 🟢 Marges exactes */
        @page {
            margin: 60px 60px 120px 60px; 
        }

        body {
            font-family: 'Poppins', Helvetica, Arial, sans-serif;
            font-size: 13px; 
            color: #1A1B41;
            line-height: 1.7;
            font-weight: 500; 
        }

        /* --- LOGO --- */
        .logo-container {
            margin-bottom: 50px;
        }

        /* --- HEADER TABLE --- */
        .header-table {
            width: 100%;
            margin-bottom: 50px;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }

        /* Gauche */
        .date-text {
            font-size: 13px;
            font-weight: 700;
            color: #1A1B41;
            margin-bottom: 5px;
        }
        .main-title {
            color: #1A1B41;
            font-size: 38px;
            font-weight: 800;
            margin: 0 0 10px 0;
            letter-spacing: -1px;
            line-height: 1.1;
        }
        .reference-text {
            font-size: 12px;
            color: #4A4D6B;
            font-weight: 600;
        }
        .reference-text span {
            color: #1A1B41;
            text-transform: capitalize;
        }

        /* Droite */
        .right-column {
            padding-left: 40px; 
            width: 50%;
        }
        .info-block {
            margin-bottom: 20px;
        }
        .info-title {
            font-size: 13px;
            font-weight: 700;
            color: #1A1B41;
            margin: 0 0 4px 0;
        }
        .info-value {
            font-size: 13px;
            font-weight: 400;
            color: #1A1B41;
            margin: 0;
            line-height: 1.4;
        }

        /* --- TABLEAU DES OPÉRATIONS --- */
        table.operations-table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0;
            margin-top: 10px; 
        }

        table.operations-table th { 
            background-color: #f8fafc; 
            color: #1A1B41; 
            font-weight: 700; 
            text-align: left; 
            padding: 12px 15px; 
            border-bottom: 2px solid #e2e8f0; 
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        table.operations-table td { 
            padding: 14px 15px; 
            border-bottom: 1px solid #f1f5f9; 
            vertical-align: middle;
            font-size: 12.5px;
        }

        .text-right { text-align: right; }
        
        .text-green { 
            color: #059669; 
            font-weight: 700;
        }

        .text-red { 
            color: #1A1B41; 
            font-weight: 700;
        }

        .badge-type {
            padding: 4px 8px;
            background: #f1f5f9;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            color: #4A4D6B;
        }

        /* --- FOOTER --- */
        .footer {
            position: fixed;
            bottom: -50px; 
            left: 0;
            right: 0;
            font-size: 12px;
            color: #4A4D6B;
            line-height: 1.6;
            font-weight: 500;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="logo-container">
        <svg width="60" height="60" viewBox="0 0 55 55" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="55" height="55" rx="14" fill="#1A1B41"/>
            <path d="M14 34L27.5 20L41 34" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M22 34L27.5 28L33 34" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M14 40H41" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
    </div>

    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <div class="date-text">Édité le {{ date('d/m/Y à H:i') }}</div>
                <h1 class="main-title">Relevé des<br>opérations</h1>
                <div class="reference-text">Type de budget > <span>{{ $type ?? 'Prévisionnel' }}</span></div>
            </td>

            <td class="right-column">
                <div class="info-block">
                    <p class="info-title">Résidence</p>
                    <p class="info-value">{{ $residence->nom ?? 'Non définie' }}</p>
                </div>

                <div class="info-block" style="margin-bottom: 0;">
                    <p class="info-title">Exercice</p>
                    <p class="info-value">{{ $exercice ?? 'Non défini' }}</p>
                </div>
            </td>
        </tr>
    </table>

    <table class="operations-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Libellé</th>
                <th class="text-right">Montant (MAD)</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($operations) && count($operations) > 0)
                @foreach($operations as $op)
                <tr>
                    <td style="color: #4A4D6B; font-weight: 600;">
                        {{ \Carbon\Carbon::parse($op->date)->format('d/m/Y') }}
                    </td>
                    <td><span class="badge-type">{{ $op->type }}</span></td>
                    <td style="font-weight: 600;">{{ $op->libelle }}</td>
                    <td class="text-right">
                        @if($op->montant > 0)
                            <span class="text-green">+{{ number_format($op->montant, 2, ',', ' ') }}</span>
                        @else
                            <span class="text-red">{{ number_format($op->montant, 2, ',', ' ') }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">Aucune opération trouvée pour ce relevé.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Document généré officiellement par le système de gestion de la résidence — {{ date('Y') }}
    </div>

</body>
</html>