<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte de Permis de Travail</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        .card-container {
            width: 85.6mm; /* Format carte de crédit standard */
            height: 53.98mm;
            margin: 20mm auto;
            border: 2px solid #1e3a8a;
            border-radius: 8px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 4px 8px;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .card-body {
            padding: 6px 8px;
            height: calc(100% - 22px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .employee-info {
            flex-grow: 1;
        }

        .employee-name {
            font-size: 11px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .employee-details {
            font-size: 9px;
            color: #4b5563;
            line-height: 1.3;
        }

        .employee-details strong {
            color: #1f2937;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 8px;
            color: #6b7280;
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px solid #e5e7eb;
        }

        .permit-number {
            font-weight: bold;
            color: #1e3a8a;
        }

        .validity {
            text-align: right;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 20px;
            color: rgba(30, 58, 138, 0.05);
            font-weight: bold;
            z-index: 1;
            white-space: nowrap;
        }

        .flag-element {
            position: absolute;
            top: 0;
            right: 0;
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-top: 15px solid #059669;
            z-index: 2;
        }

        .flag-element::after {
            content: '';
            position: absolute;
            top: -15px;
            left: -8px;
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-top: 8px solid #10b981;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .card-container {
                margin: 0;
                box-shadow: none;
                border: 1px solid #1e3a8a;
            }
        }
    </style>
</head>
<body>
    <div class="card-container">
        <div class="watermark">RÉPUBLIQUE DE DJIBOUTI</div>
        <div class="flag-element"></div>
        
        <div class="card-header">
            PERMIS DE TRAVAIL RENOUVELABLE
        </div>
        
        <div class="card-body">
            <div class="employee-info">
                <div class="employee-name">{{ $employee->nom_complet }}</div>
                <div class="employee-details">
                    <div><strong>Nationalité:</strong> {{ $employee->nationality->nom }}</div>
                    <div><strong>Né(e) le:</strong> {{ $employee->date_naissance->format('d/m/Y') }}</div>
                    <div><strong>État civil:</strong> {{ ucfirst($employee->etat_civil) }}</div>
                    <div><strong>Profession:</strong> {{ $employee->activeContrat?->type_emploi ?? 'N/A' }}</div>
                    <div><strong>Adresse:</strong> {{ $employee->quartier }}, {{ $employee->ville }}</div>
                </div>
            </div>
            
            <div class="card-footer">
                <div class="permit-number">
                    N° {{ $permit_number }}
                </div>
                <div class="validity">
                    <div><strong>Émis le:</strong> {{ now()->format('d/m/Y') }}</div>
                    <div><strong>Renouvelable</strong></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Version recto-verso optionnelle -->
    <div style="page-break-before: always;">
        <div class="card-container">
            <div style="padding: 8px; text-align: center; height: 100%; display: flex; flex-direction: column; justify-content: center;">
                <div style="font-size: 10px; color: #1e3a8a; font-weight: bold; margin-bottom: 6px;">
                    RÉPUBLIQUE DE DJIBOUTI
                </div>
                <div style="font-size: 8px; color: #4b5563; text-align: justify; line-height: 1.4;">
                    <p>Ce permis de travail autorise le porteur à exercer une activité professionnelle sur le territoire de la République de Djibouti.</p>
                    
                    <p style="margin-top: 4px;"><strong>CONDITIONS:</strong></p>
                    <ul style="margin-left: 12px; font-size: 7px;">
                        <li>Valide uniquement avec passeport</li>
                        <li>Renouvelable selon réglementation</li>
                        <li>À présenter lors de tout contrôle</li>
                    </ul>
                </div>
                
                <div style="margin-top: 6px; font-size: 7px; color: #6b7280; text-align: center;">
                    Ministère du Travail et de l'Emploi<br>
                    République de Djibouti
                </div>
            </div>
        </div>
    </div>
</body>
</html>