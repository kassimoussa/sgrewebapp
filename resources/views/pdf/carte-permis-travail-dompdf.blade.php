<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Carte de Permis de Travail</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }

        .card-container {
            width: 85.6mm;
            height: 53.98mm;
            margin: 20mm auto;
            border: 2px solid #1e3a8a;
            border-radius: 8px;
            background-color: #ffffff;
            position: relative;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .card-header {
            background-color: #1e3a8a;
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
            position: relative;
        }

        .employee-photo-card {
            position: absolute;
            top: 6px;
            right: 8px;
            width: 25mm;
            height: 30mm;
            object-fit: cover;
            border: 1px solid #1e3a8a;
            border-radius: 2px;
        }

        .card-content {
            margin-right: 27mm;
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
            margin-bottom: 4px;
        }

        .employee-details strong {
            color: #1f2937;
        }

        .card-footer {
            position: absolute;
            bottom: 6px;
            left: 8px;
            right: 8px;
            font-size: 8px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }

        .permit-number {
            font-weight: bold;
            color: #1e3a8a;
            float: left;
        }

        .validity {
            float: right;
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

        /* Version recto-verso */
        .card-back {
            margin-top: 40mm;
            padding: 8px;
            text-align: center;
            height: 53.98mm;
            box-sizing: border-box;
        }

        .back-title {
            font-size: 10px;
            color: #1e3a8a;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .back-content {
            font-size: 8px;
            color: #4b5563;
            text-align: justify;
            line-height: 1.4;
        }

        .back-conditions {
            margin-top: 4px;
        }

        .back-conditions p {
            margin: 2px 0;
        }

        .back-conditions ul {
            margin: 4px 0 4px 12px;
            padding: 0;
        }

        .back-conditions li {
            font-size: 7px;
            margin-bottom: 1px;
        }

        .back-footer {
            margin-top: 6px;
            font-size: 7px;
            color: #6b7280;
            text-align: center;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <!-- Recto de la carte -->
    <div class="card-container">
        <div class="watermark">RÉPUBLIQUE DE DJIBOUTI</div>
        <div class="flag-element"></div>
        
        <div class="card-header">
            PERMIS DE TRAVAIL RENOUVELABLE
        </div>
        
        <div class="card-body">
            @if($employee->photo_url && $employee->photo_url !== asset('images/default-employee.png'))
                <img src="{{ $employee->photo_url }}" alt="Photo {{ $employee->nom_complet }}" class="employee-photo-card">
            @endif
            <div class="card-content">
                <div class="employee-name">{{ $employee->nom_complet }}</div>
                <div class="employee-details">
                    <div><strong>Nationalité:</strong> {{ $employee->nationality->nom }}</div>
                    <div><strong>Né(e) le:</strong> {{ $employee->date_naissance->format('d/m/Y') }}</div>
                    <div><strong>État civil:</strong> {{ ucfirst($employee->etat_civil) }}</div>
                    <div><strong>Profession:</strong> {{ $employee->activeContrat?->type_emploi ?? 'N/A' }}</div>
                    <div><strong>Adresse:</strong> {{ $employee->quartier }}, {{ $employee->ville }}</div>
                </div>
            </div>
        </div>
        
        <div class="card-footer clearfix">
            <div class="permit-number">
                N° {{ $permit_number }}
            </div>
            <div class="validity">
                <div><strong>Émis le:</strong> {{ now()->format('d/m/Y') }}</div>
                <div><strong>Renouvelable</strong></div>
            </div>
        </div>
    </div>

    <!-- Verso de la carte -->
    <div class="card-container card-back">
        <div class="back-title">
            RÉPUBLIQUE DE DJIBOUTI
        </div>
        
        <div class="back-content">
            <p>Ce permis de travail autorise le porteur à exercer une activité professionnelle sur le territoire de la République de Djibouti.</p>
            
            <div class="back-conditions">
                <p><strong>CONDITIONS:</strong></p>
                <ul>
                    <li>Valide uniquement avec passeport</li>
                    <li>Renouvelable selon réglementation</li>
                    <li>À présenter lors de tout contrôle</li>
                </ul>
            </div>
        </div>
        
        <div class="back-footer">
            Ministère du Travail et de l'Emploi<br>
            République de Djibouti
        </div>
    </div>
</body>
</html>