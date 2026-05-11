<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu d'encaissement</title>
    <style>
        /* 🟢 IMPORT L-POLICE POPPINS MN LOCAL (DomPDF kay-bghiha haka) */
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

        /* 🟢 MARGES EXACTES */
        @page {
            margin: 60px 60px 120px 60px; 
        }

        body { 
            font-family: 'Poppins', Helvetica, Arial, sans-serif; 
            font-size: 13px; 
            color: #1A1B41; /* 🟢 L-loun Navy Blue l-asli */
            line-height: 1.7; 
            font-weight: 500;
        }

        /* --- LOGO --- */
        .logo-container {
            margin-bottom: 50px;
        }

        /* --- HEADER --- */
        .table-header { 
            width: 100%; 
            margin-bottom: 60px; 
            border-collapse: collapse;
        }
        .table-header td { 
            vertical-align: top; 
        }
        
        .date-text { 
            font-size: 13px; 
            font-weight: 700; 
            color: #1A1B41; 
            margin-bottom: 5px; 
        }
        
        .title { 
            color: #1A1B41; 
            font-size: 42px; 
            font-weight: 800; 
            margin: 0 0 10px 0; 
            letter-spacing: -1px; 
            line-height: 1.1;
        }
        
        .ref { 
            font-size: 12px; 
            color: #4A4D6B; 
            font-weight: 600; 
        }
        .ref span { color: #1A1B41; }
        
        /* --- DROITE (Infos) --- */
        .right-box { 
            padding-left: 40px; 
            width: 50%;
        }
        .section-title { 
            font-size: 13px; 
            font-weight: 700; 
            color: #1A1B41; 
            margin: 0 0 4px 0; 
        }
        .section-content { 
            font-size: 13px; 
            font-weight: 400;
            color: #1A1B41; 
            margin: 0 0 20px 0; 
            line-height: 1.4;
        }
        
        /* --- CONTENU --- */
        .content { 
            margin-top: 20px; 
        }
        .content p { 
            margin: 0 0 25px 0; 
            text-align: left;
        }
        strong { 
            font-weight: 700; 
        }
        
        /* --- FOOTER --- */
        .footer { 
            position: fixed; 
            bottom: -50px; 
            left: 0; 
            right: 0; 
            font-size: 12px; 
            color: #1A1B41; 
            line-height: 1.6; 
            font-weight: 500;
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

    <table class="table-header">
        <tr>
            <td style="width: 50%;">
                <div class="date-text">Le {{ \Carbon\Carbon::parse($date_paiement ?? now())->translatedFormat('j F Y') }}</div>
                <h1 class="title">Reçu<br>d'encaissement</h1>
                <div class="ref">Référence > <span>{{ $reference ?? 'REC-2026-001' }}</span></div>
            </td>
            
            <td class="right-box">
                <div style="margin-bottom: 20px;">
                    <p class="section-title">Destinataire</p>
                    <p class="section-content">Monsieur/Madame {{ $destinataire ?? '[Nom du copropriétaire]' }}</p>
                </div>

                <div style="margin-bottom: 20px;">
                    <p class="section-title">Adresse</p>
                    <p class="section-content">{{ $adresse ?? '[Adresse de la copropriété]' }}</p>
                </div>

                <div style="margin-bottom: 0;">
                    <p class="section-title">Lots</p>
                    <p class="section-content">{{ $lots ?? '[Liste des lots]' }}</p>
                </div>
            </td>
        </tr>
    </table>

    <div class="content">
        <p>Cher(e) {{ $destinataire ?? '[Nom du propriétaire]' }},</p>

        <p>Nous vous confirmons la réception de votre paiement d'un montant de <strong>{{ number_format($montant ?? 0, 2, ',', ' ') }} MAD</strong>, effectué le <strong>{{ $date_paiement ?? '[Date]' }}</strong>, via <strong>{{ $mode_paiement ?? 'Virement bancaire' }}</strong>. Ce paiement concerne les charges liées à la résidence pour la période de <strong>{{ $periode ?? '[Période]' }}</strong>.</p>

        <p style="margin-bottom: 5px;">Le paiement couvre les frais suivants :</p>
        <p>Type de frais réglés : <strong>{{ $type_frais ?? 'Cotisation Prévisionnelle' }}</strong><br>
        Période concernée : <strong>{{ $periode ?? '[Période]' }}</strong></p>

        <p>Nous vous remercions pour votre prompt règlement. Ce reçu fait foi et peut être utilisé comme preuve de paiement.</p>

        <p>Cordialement.</p>
    </div>

    <div class="footer">
        Contact : {{ $telephone ?? '[Numéro]' }} / {{ $email ?? '[Email]' }}<br>
        Numéro de compte bancaire : {{ $iban ?? '[IBAN]' }}<br>
        Disclaimer légal : Ce document est un reçu officiel et doit être traité comme tel.
    </div>

</body>
</html>