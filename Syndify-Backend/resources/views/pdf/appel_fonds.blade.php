<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Appel de fonds</title>
    <style>
        /* 🟢 ZEDNA L-POLICE POPPINS MN GOOGLE FONTS */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');

        /* 🟢 BDDELNA L-FONT W L-ALWAN L-SYNDIFY-DARK (#0B0F24) */
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
        .title { color: #0B0F24; font-size: 34px; font-weight: 800; margin: 0 0 5px 0; letter-spacing: -1px; }
        .ref { font-size: 12px; color: #64748b; font-weight: 600; }
        
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
                <!-- T9edri t-badliha b <img src="{{ public_path('images/logo.png') }}" width="50"> -->
                <div class="logo-placeholder"></div>
                <div class="date-text">Le {{ \Carbon\Carbon::now()->translatedFormat('j F Y') }}</div>
                <h1 class="title">Appel de fonds</h1>
                <div class="ref">Référence > {{ $reference ?? 'SAF-529-2024' }}</div>
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

        <p>Nous vous informons par la présente qu'un appel de fonds est nécessaire pour couvrir les charges courantes de la copropriété. Ces charges comprennent des provisions pour les frais de gestion, d'entretien et les dépenses courantes, réparties selon les clés de répartition en vigueur.</p>

        <p>
            Montant total dû : <strong>{{ number_format($montant ?? 0, 2, ',', ' ') }} MAD</strong><br>
            Date limite de paiement : <strong>{{ $date_limite ?? '[Date]' }}</strong>
        </p>

        <p>Pour faciliter le règlement, vous pouvez effectuer le paiement par Virement bancaire.</p>

        <p>Nous vous remercions de votre collaboration et de votre promptitude à effectuer ce règlement. En cas de questions, n'hésitez pas à contacter le syndic.</p>

        <p>Cordialement.</p>
    </div>

    <div class="footer">
        Contact : {{ $telephone ?? '[Numéro]' }} / {{ $email ?? '[Email]' }}<br>
        Numéro de compte bancaire : <strong>{{ $iban ?? '[IBAN]' }}</strong><br>
        <span class="disclaimer">Disclaimer légal : Ce document est un appel de fonds officiel et doit être traité comme tel.</span>
    </div>

</body>
</html>