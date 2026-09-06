<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; line-height: 1.6; margin: 40px; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 30px; }
        .section { margin-bottom: 20px; }
        .section h2 { font-size: 13px; border-bottom: 1px solid #333; padding-bottom: 4px; margin-bottom: 8px; }
        .row { display: flex; margin-bottom: 4px; }
        .label { width: 180px; font-weight: bold; }
        .value { flex: 1; }
        .conditions { margin-top: 20px; padding: 10px; border: 1px solid #ccc; }
        .signatures { margin-top: 50px; display: flex; justify-content: space-between; }
        .signature-block { width: 45%; text-align: center; }
        .signature-block .line { border-top: 1px solid #333; margin-top: 60px; padding-top: 5px; }
    </style>
</head>
<body>

    <h1>CONTRAT DE LOCATION</h1>

    <div class="section">
        <h2>Parties</h2>
        <div class="row">
            <span class="label">Propriétaire :</span>
            <span class="value">{{ $contrat->location->logement->proprietaire->name }}</span>
        </div>
        <div class="row">
            <span class="label">Locataire :</span>
            <span class="value">{{ $contrat->location->locataire->name }}</span>
        </div>
    </div>

    <div class="section">
        <h2>Logement</h2>
        <div class="row">
            <span class="label">Titre :</span>
            <span class="value">{{ $contrat->location->logement->titre }}</span>
        </div>
        <div class="row">
            <span class="label">Adresse :</span>
            <span class="value">{{ $contrat->location->logement->adresse }}</span>
        </div>
    </div>

    <div class="section">
        <h2>Conditions financières</h2>
        <div class="row">
            <span class="label">Loyer mensuel :</span>
            <span class="value">{{ number_format($contrat->montant_loyer, 2, ',', ' ') }} Ar</span>
        </div>
        <div class="row">
            <span class="label">Caution :</span>
            <span class="value">{{ $contrat->montant_caution ? number_format($contrat->montant_caution, 2, ',', ' ') . ' Ar' : '—' }}</span>
        </div>
    </div>

    <div class="section">
        <h2>Durée</h2>
        <div class="row">
            <span class="label">Date de signature :</span>
            <span class="value">{{ $contrat->date_signature ? $contrat->date_signature->format('d/m/Y') : '—' }}</span>
        </div>
        <div class="row">
            <span class="label">Date de début :</span>
            <span class="value">{{ $contrat->date_debut ? $contrat->date_debut->format('d/m/Y') : '—' }}</span>
        </div>
        @if($contrat->date_fin)
        <div class="row">
            <span class="label">Date de fin :</span>
            <span class="value">{{ $contrat->date_fin->format('d/m/Y') }}</span>
        </div>
        @endif
    </div>

    @if($contrat->conditions)
    <div class="section">
        <h2>Conditions particulières</h2>
        <div class="conditions">{{ $contrat->conditions }}</div>
    </div>
    @endif

    <div class="signatures">
        <div class="signature-block">
            <div class="line">Propriétaire</div>
        </div>
        <div class="signature-block">
            <div class="line">Locataire</div>
        </div>
    </div>

</body>
</html>
