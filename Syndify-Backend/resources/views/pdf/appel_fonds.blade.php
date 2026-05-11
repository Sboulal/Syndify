<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Appel de fonds</title>
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

        /* 🟢 Marges exactes dyal l-page */
        @page {
            margin: 60px 60px 120px 60px;
        }

        body {
            font-family: 'Poppins', Helvetica, Arial, sans-serif;
            font-size: 13px; 
            color: #1A1B41; 
            line-height: 1.4; /* 🟢 N9esna l-line-height mn 1.7 l-1.4 bash y-tzeyer stoura */
            font-weight: 500; 
        }

        /* --- LOGO --- */
        .logo-container {
            margin-bottom: 40px; /* 🟢 N9esna mn l-espace ta7t l-logo */
        }

        /* --- HEADER TABLE --- */
        .header-table {
            width: 100%;
            margin-bottom: 30px; /* 🟢 N9esna mn 60px l-30px (Espace bin Header w l-Message) */
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
            margin-bottom: 0px; /* 🟢 7iydna l-espace bin l-Date w l-Titre */
        }
        .main-title {
            color: #1A1B41;
            font-size: 42px; 
            font-weight: 800;
            margin: 0 0 2px 0; /* 🟢 N9esna l-espace ta7t l-Titre bzaaf */
            letter-spacing: -1px;
            line-height: 1;
        }
        .reference-text {
            font-size: 12px;
            color: #4A4D6B;
            font-weight: 600;
        }
        .reference-text span {
            color: #1A1B41;
        }

        /* Droite */
        .right-column {
            padding-left: 40px; 
            width: 50%;
        }
        .info-block {
            margin-bottom: 8px; /* 🟢 N9esna mn l-espace bin les blocs (Destinataire, Adresse, Lots) */
        }
        .info-title {
            font-size: 13px;
            font-weight: 700;
            color: #1A1B41;
            margin-bottom: 0px; /* 🟢 Espace mzeyer bin l-titre w l-m3louma */
            display: block;
        }
        .info-value {
            font-size: 13px;
            font-weight: 400;
            color: #1A1B41;
            line-height: 1.3;
            display: block;
        }

        /* --- CONTENU PRINCIPAL --- */
        .content-section {
            margin-top: 10px;
        }
        .content-section p {
            margin: 0 0 12px 0; /* 🟢 N9esna mn l-espace bin les paragraphes mn 25px l-12px */
            text-align: left;
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

    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <div class="date-text">Le {{ \Carbon\Carbon::parse($date_emission ?? now())->translatedFormat('j F Y') }}</div>
                <h1 class="main-title">Appel de fonds</h1>
                <div class="reference-text">Référence > <span>{{ $reference ?? 'SAF-T2-2026' }}</span></div>
            </td>

            <td class="right-column">
                <div class="info-block">
                    <span class="info-title">Destinataire</span>
                    <span class="info-value">Monsieur/Madame {{ $destinataire ?? '[nom du copropriétaire]' }}</span>
                </div>

                <div class="info-block">
                    <span class="info-title">Adresse</span>
                    <span class="info-value">{{ $adresse ?? '[Adresse de la copropriété]' }}</span>
                </div>

                <div class="info-block" style="margin-bottom: 0;">
                    <span class="info-title">Lots</span>
                    <span class="info-value">{{ $lots ?? '[liste des lots du copropriétaire]' }}</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="content-section">
        <p>Cher(e) {{ $destinataire ?? '[Nom du propriétaire]' }},</p>

        <p>Nous vous informons par la présente qu'un appel de fonds est nécessaire pour couvrir les charges courantes de la copropriété. Ces charges comprennent des provisions pour les frais de gestion, d'entretien et les dépenses courantes, réparties selon les clés de répartition en vigueur.</p>

        <p style="margin-bottom: 2px;">Montant total dû : <strong>{{ number_format($montant ?? 0, 2, ',', ' ') }} MAD</strong></p>
        <p>Date limite de paiement : <strong>{{ $date_limite ?? '[Date]' }}</strong></p>

        <p>Pour faciliter le règlement, vous pouvez effectuer le paiement par Virement bancaire</p>

        <p>Nous vous remercions de votre collaboration et de votre promptitude à effectuer ce règlement.<br>En cas de questions, n'hésitez pas à contacter le syndic.</p>

        <p>Cordialement.</p>
    </div>

    <div class="footer">
        Contact : {{ $telephone ?? '[Numéro de téléphone]' }} / {{ $email ?? '[Email]' }}<br>
        Numéro de compte bancaire : {{ $iban ?? '[IBAN]' }}<br>
        Disclaimer légal : Ce document est un appel de fonds officiel et doit être traité comme tel.
    </div>

</body>
</html>