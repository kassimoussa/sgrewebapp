<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Attestation d'Identité à Titre Administratif</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
        }

        .header h1 {
            color: #1e3a8a;
            font-size: 18px;
            margin: 0 0 5px 0;
            font-weight: bold;
        }

        .header h2 {
            color: #3b82f6;
            font-size: 16px;
            margin: 0 0 10px 0;
            font-weight: bold;
        }

        .header p {
            color: #6b7280;
            font-size: 10px;
            margin: 0;
        }

        .attestation-number {
            text-align: right;
            margin-bottom: 15px;
            font-weight: bold;
            color: #1e3a8a;
        }

        .content {
            margin-bottom: 20px;
        }

        .employee-info {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 12px;
            margin-bottom: 15px;
            position: relative;
        }

        .employee-photo {
            float: right;
            width: 80px;
            height: 100px;
            object-fit: cover;
            border: 2px solid #1e3a8a;
            border-radius: 4px;
            margin-left: 15px;
            margin-bottom: 10px;
        }

        .employee-info-content {
            margin-right: 100px;
        }

        .info-row {
            margin-bottom: 8px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: bold;
            color: #374151;
            width: 140px;
            display: inline-block;
        }

        .info-value {
            color: #1f2937;
        }

        .warning-section {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 12px;
        }

        .warning-title {
            font-weight: bold;
            color: #92400e;
            margin-bottom: 5px;
        }

        .warning-text {
            color: #92400e;
            font-size: 10px;
            text-align: justify;
        }

        .validity-section {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 12px;
        }

        .validity-title {
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 5px;
        }

        .validity-text {
            color: #dc2626;
            font-size: 10px;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
        }

        .signature-section {
            margin-top: 20px;
        }

        .signature-left {
            width: 48%;
            display: inline-block;
            vertical-align: top;
        }

        .signature-right {
            width: 48%;
            display: inline-block;
            vertical-align: top;
            text-align: right;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            height: 40px;
            margin-bottom: 5px;
        }

        .signature-label {
            font-size: 10px;
            color: #6b7280;
        }

        .page-footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>RÉPUBLIQUE DE DJIBOUTI</h1>
        <h2>MINISTÈRE DU TRAVAIL ET DE L'EMPLOI</h2>
        <p>Direction de l'Emploi et de la Main d'Œuvre</p>
    </div>

    <div class="attestation-number">
        N° {{ $attestation_number }}
    </div>

    <div class="content">
        <div style="text-align: center; margin-bottom: 25px;">
            <h3 style="color: #1e3a8a; font-size: 16px; margin: 0; text-decoration: underline;">
                ATTESTATION D'IDENTITÉ À TITRE ADMINISTRATIF
            </h3>
        </div>


        <div class="employee-info">
            @if($employee->photo_url && $employee->photo_url !== asset('images/default-employee.png'))
                <img src="{{ $employee->photo_url }}" alt="Photo {{ $employee->nom_complet }}" class="employee-photo">
            @endif
            <div class="employee-info-content">
                <div class="info-row">
                    <span class="info-label">Nom complet :</span>
                    <span class="info-value">{{ $employee->nom_complet }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Genre :</span>
                    <span class="info-value">{{ $employee->genre }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date de naissance :</span>
                    <span class="info-value">{{ $employee->date_naissance->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Nationalité :</span>
                    <span class="info-value">{{ $employee->nationality->nom }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date d'arrivée :</span>
                    <span class="info-value">{{ $employee->date_arrivee->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">État civil :</span>
                    <span class="info-value">{{ ucfirst($employee->etat_civil) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Adresse :</span>
                    <span class="info-value">{{ $employee->quartier }}, {{ $employee->ville }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Profession :</span>
                    <span class="info-value">{{ $employee->activeContrat?->type_emploi ?? 'Non spécifiée' }}</span>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>

        <div class="warning-section">
            <div class="warning-title">OBLIGATION IMPÉRATIVE :</div>
            <div class="warning-text">
                Le titulaire de cette attestation s'engage formellement à se procurer un passeport 
                de son pays d'origine dans un délai maximum de DOUZE (12) MOIS à compter de la date 
                d'émission de cette attestation. Le non-respect de cette obligation entraînera 
                l'annulation automatique de cette attestation et l'engagement de procédures 
                administratives appropriées.
            </div>
        </div>

        <div class="validity-section">
            <div class="validity-title">VALIDITÉ :</div>
            <div class="validity-text">
                Cette attestation est valable pour une durée de UN (1) AN à compter de sa date d'émission, 
                soit jusqu'au {{ $validity_period->format('d/m/Y') }}. Elle ne peut être renouvelée 
                qu'une seule fois, uniquement sur présentation de justificatifs prouvant les démarches 
                entreprises pour l'obtention du passeport.
            </div>
        </div>

        <div style="margin-bottom: 15px; font-size: 10px; text-align: justify;">
            Cette attestation ne constitue pas un titre de séjour et ne peut en aucun cas 
            remplacer un passeport pour les voyages internationaux. Elle est délivrée 
            exclusivement à des fins administratives locales.
        </div>
    </div>

    <div class="signature-section">
        <div class="signature-left">
            <div style="text-align: center;">
                <div style="margin-bottom: 40px;">Le titulaire</div>
                <div class="signature-line"></div>
                <div class="signature-label">Signature</div>
            </div>
        </div>
        
        <div class="signature-right">
            <div style="text-align: center;">
                <div style="margin-bottom: 10px;">Djibouti, le {{ $generation_date->format('d/m/Y') }}</div>
                <div style="margin-bottom: 30px;">Le Directeur de l'Emploi</div>
                <div class="signature-line"></div>
                <div class="signature-label">Signature et Cachet</div>
            </div>
        </div>
    </div>

    <div class="page-footer">
        République de Djibouti - Ministère du Travail et de l'Emploi<br>
        Direction de l'Emploi et de la Main d'Œuvre<br>
        Attestation d'Identité à Titre Administratif - {{ $attestation_number }}
    </div>
</body>
</html>