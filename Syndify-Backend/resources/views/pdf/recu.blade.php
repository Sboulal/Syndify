<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu d'encaissement</title>
    <style>
        /* 🟢 IMPORT L-POLICE POPPINS */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');

        /* 🟢 CONFIGURATION L-ALWAN SYNDIFY-DARK (#0B0F24) */
        body { 
            font-family: 'Poppins', Helvetica, Arial, sans-serif; 
            font-size: 13px; 
            color: #334155; /* text-slate-700 */
            line-height: 1.6; 
        }
        
        .table-header { width: 100%; margin-bottom: 50px; }
        .table-header td { vertical-align: top; }
        
        .logo-placeholder { 
            width: 50px; 
            height: 50px; 
            background-color: #0B0F24; /* syndify-dark */
            border-radius: 12px; 
            margin-bottom: 20px; 
        }
        
        .date-text { font-size: 12px; font-weight: 700; color: #0B0F24; margin-bottom: 5px; }
        
        /* 🟢 STYLE DYAL L-TITRE B-LHESSAB DESIGN J-PEG */
        .title { 
            color: #0B0F24; 
            font-size: 34px; 
            font-weight: 800; 
            margin: 0; 
            letter-spacing: -1px; 
            line-height: 1.1;
        }
        
        .ref { font-size: 12px; color: #64748b; font-weight: 600; margin-top: 10px; }
        
        .right-box { padding-left: 50px; }
        .section-title { font-size: 13px; font-weight: 700; color: #0B0F24; margin: 0 0 5px 0; }
        .section-content { font-size: 13px; color: #475569; margin: 0 0 20px 0; }
        
        .content { margin-top: 10px; color: #334155; }
        .content p { margin-bottom: 15px; }
        strong { color: #0B0F24; font-weight: 700; }
        
        .footer { 
            position: fixed; 
            bottom: 0; 
            left: 0; 
            right: 0; 
            font-size: 12px; 
            color: #0B0F24; 
            line-height: 1.6; 
            font-weight: 500;
        }
        .disclaimer { color: #94a3b8; font-size: 11px; display: block; margin-top: 5px; }
    </style>
</head>
<body>

    <table class="table-header">
        <tr>
            <td width="55%">
                <div class="logo-placeholder"></div>
                <div class="date-text">Le {{ \Carbon\Carbon::now()->translatedFormat('j F Y') }}</div>
                <h1 class="title">Reçu<br>d'encaissement</h1>
                <div class="ref">Référence > {{ $reference ?? 'REC-2024-001' }}</div>
            </td>
            <td width="45%" class="right-box">
                <p class="section-title">Destinataire</p>
                <p class="section-content">Monsieur/Madame {{ $destinataire ?? '[Nom du copropriétaire]' }}</p>

                <p class="section-title">Adresse</p>
                <p class="section-content">{{ $adresse ?? '[Adresse de la copropriété]' }}</p>

                <p class="section-title">Lots</p>
                <p class="section-content">{{ $lots ?? '[Liste des lots]' }}</p>
            </td>
        </tr>
    </table>

    <div class="content">
        <p>Cher(e) {{ $destinataire ?? '[Nom du propriétaire]' }},</p>

        <p>Nous vous confirmons la réception de votre paiement d'un montant de <strong>{{ number_format($montant ?? 0, 2, ',', ' ') }} MAD</strong>, effectué le <strong>{{ $date_paiement ?? '[Date]' }}</strong>, via <strong>{{ $mode_paiement ?? 'Virement' }}</strong>. Ce paiement concerne les charges liées à la résidence pour la période de <strong>{{ $periode ?? '[Période]' }}</strong>.</p>

        <p>Le paiement couvre les frais suivants :</p>
        <p>
            Type de frais réglés : <strong>{{ $type_frais ?? 'Charges courantes' }}</strong><br>
            Période concernée : <strong>{{ $periode ?? '[Période]' }}</strong>
        </p>

        <p>Nous vous remercions pour votre prompt règlement. Ce reçu fait foi et peut être utilisé comme preuve de paiement.</p>

        <p>Cordialement.</p>
    </div>

    <div class="footer">
        Contact : {{ $telephone ?? '[Numéro]' }} / {{ $email ?? '[Email]' }}<br>
        Numéro de compte bancaire : <strong>{{ $iban ?? '[IBAN]' }}</strong><br>
        <span class="disclaimer">Disclaimer légal : Ce document est officiel et doit être traité comme tel.</span>
    </div>

</body>
</html>