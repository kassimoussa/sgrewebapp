<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attestation d'Identité</title>
    <style>
        @page {
            margin: 2cm;
            @top-center {
                content: "RÉPUBLIQUE DE DJIBOUTI";
                font-weight: bold;
                font-size: 12px;
            }
        }
        
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #1e40af;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #1e40af;
            font-size: 24px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .header h2 {
            color: #64748b;
            font-size: 18px;
            margin: 10px 0;
            font-weight: normal;
        }
        
        .attestation-number {
            background: #f1f5f9;
            padding: 10px;
            border-left: 4px solid #1e40af;
            margin: 20px 0;
            font-weight: bold;
        }
        
        .content {
            margin: 30px 0;
            text-align: justify;
        }
        
        .employee-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .employee-info h3 {
            color: #1e40af;
            margin-top: 0;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 5px 0;
            border-bottom: 1px dotted #cbd5e1;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .label {
            font-weight: bold;
            color: #475569;
            width: 40%;
        }
        
        .value {
            width: 55%;
            text-align: right;
        }
        
        .employer-info {
            background: #fefce8;
            border: 1px solid #fde047;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .validity {
            background: #fee2e2;
            border: 2px solid #fca5a5;
            border-radius: 8px;
            padding: 15px;
            margin: 30px 0;
            text-align: center;
        }
        
        .validity h4 {
            color: #dc2626;
            margin: 0 0 10px 0;
        }
        
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
            border-top: 1px solid #94a3b8;
            padding-top: 10px;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72px;
            color: rgba(30, 64, 175, 0.1);
            z-index: -1;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="watermark">ATTESTATION</div>
    
    <div class="header">
        <h1>République de Djibouti</h1>
        <h2>Attestation d'Identité à Titre Administratif</h2>
    </div>
    
    <div class="attestation-number">
        <strong>N° d'Attestation :</strong> {{ $attestation_number }}
    </div>
    
    <div class="content">
        <p style="font-size: 16px; text-align: center; margin: 30px 0;">
            <strong>Le soussigné certifie que les informations ci-dessous sont exactes :</strong>
        </p>
        
        <div class="employee-info">
            <h3>Informations de l'Employé(e)</h3>
            
            <div class="info-row">
                <span class="label">Nom complet :</span>
                <span class="value">{{ $employee->nom_complet }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Genre :</span>
                <span class="value">{{ $employee->genre }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Date de naissance :</span>
                <span class="value">{{ $employee->date_naissance->format('d/m/Y') }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Âge :</span>
                <span class="value">{{ $employee->age }} ans</span>
            </div>
            
            <div class="info-row">
                <span class="label">Nationalité :</span>
                <span class="value">{{ $employee->nationality->nom ?? 'Non renseignée' }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Date d'arrivée :</span>
                <span class="value">{{ $employee->date_arrivee->format('d/m/Y') }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">État civil :</span>
                <span class="value">{{ ucfirst($employee->etat_civil) }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Adresse :</span>
                <span class="value">{{ $employee->quartier }}, {{ $employee->ville }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Profession :</span>
                <span class="value">{{ $employee->activeContrat?->type_emploi ?? 'Non spécifiée' }}</span>
            </div>
        </div>
        
        <div class="validity">
            <h4>⚠️ IMPORTANT - OBLIGATION LÉGALE</h4>
            <p style="margin: 10px 0;">
                Cette attestation est valable <strong>UNE ANNÉE UNIQUEMENT</strong> à compter de sa délivrance.
            </p>
            <p style="margin: 10px 0;">
                <strong>L'employé(e) doit OBLIGATOIREMENT se procurer un passeport avant le :</strong><br>
                <span style="font-size: 18px; color: #dc2626; font-weight: bold;">
                    {{ $validity_period->format('d/m/Y') }}
                </span>
            </p>
            <p style="margin: 10px 0; font-size: 14px;">
                Faute de quoi, cette attestation deviendra caduque et l'employé(e) sera en situation irrégulière.
            </p>
        </div>
        
        <p style="text-align: center; margin: 30px 0; font-style: italic;">
            Cette attestation ne peut en aucun cas remplacer un document d'identité officiel.<br>
            Elle est délivrée uniquement à des fins administratives temporaires.
        </p>
    </div>
    
    <div class="signature-section">
        <div class="signature-box">
            <strong>Délivré le :</strong><br>
            {{ $generation_date->format('d/m/Y à H:i') }}
        </div>
        <div class="signature-box">
            <strong>Signature et cachet</strong><br>
            <div style="height: 60px;"></div>
        </div>
    </div>
    
    <div class="footer">
        <p>Document généré automatiquement - {{ $generation_date->format('d/m/Y H:i:s') }}</p>
        <p>Ce document est authentique et peut être vérifié via le numéro d'attestation ci-dessus.</p>
    </div>
</body>
</html>