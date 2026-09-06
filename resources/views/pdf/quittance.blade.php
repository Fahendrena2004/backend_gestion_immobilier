<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; line-height: 1.6; margin: 40px; color: #333; }
        h1 { text-align: center; font-size: 20px; margin-bottom: 5px; }
        .subtitle { text-align: center; font-size: 13px; color: #666; margin-bottom: 30px; }
        .section { margin-bottom: 20px; }
        .section h2 { font-size: 13px; border-bottom: 1px solid #333; padding-bottom: 4px; margin-bottom: 8px; }
        .row { display: flex; margin-bottom: 4px; }
        .label { width: 180px; font-weight: bold; }
        .value { flex: 1; }
        .montant { font-size: 16px; font-weight: bold; text-align: center; margin: 20px 0; padding: 12px; border: 2px solid #2d6a4f; background: #f0faf4; }
        .mention { margin-top: 30px; font-size: 11px; color: #666; text-align: center; font-style: italic; }
    </style>
</head>
<body>

    <h1>QUITTANCE DE PAIEMENT</h1>
    <div class="subtitle">Référence : {{ $quittance->numero_quittance }}</div>

    <div class="section">
        <h2>Locataire</h2>
        <div class="row">
            <span class="label">Nom :</span>
            <span class="value">{{ $quittance->paiement->facture->location->locataire->name }}</span>
        </div>
        <div class="row">
            <span class="label">Email :</span>
            <span class="value">{{ $quittance->paiement->facture->location->locataire->email }}</span>
        </div>
    </div>

    <div class="section">
        <h2>Logement</h2>
        <div class="row">
            <span class="label">Titre :</span>
            <span class="value">{{ $quittance->paiement->facture->location->logement->titre }}</span>
        </div>
        <div class="row">
            <span class="label">Adresse :</span>
            <span class="value">{{ $quittance->paiement->facture->location->logement->adresse }}</span>
        </div>
    </div>

    <div class="section">
        <h2>Détails du paiement</h2>
        <div class="row">
            <span class="label">Facture :</span>
            <span class="value">{{ $quittance->paiement->facture->numero_facture }}</span>
        </div>
        <div class="row">
            <span class="label">Date de paiement :</span>
            <span class="value">{{ $quittance->paiement->date_paiement ? $quittance->paiement->date_paiement->format('d/m/Y à H:i') : '—' }}</span>
        </div>
        <div class="row">
            <span class="label">Mode de paiement :</span>
            <span class="value">{{ $quittance->paiement->modePaiement->libelle ?? '—' }}</span>
        </div>
        @if($quittance->paiement->reference)
        <div class="row">
            <span class="label">Référence :</span>
            <span class="value">{{ $quittance->paiement->reference }}</span>
        </div>
        @endif
    </div>

    <div class="montant">
        Montant reçu : {{ number_format($quittance->paiement->montant, 2, ',', ' ') }} Ar
    </div>

    <div class="section">
        <h2>Date d'émission</h2>
        <div class="row">
            <span class="label">Émis le :</span>
            <span class="value">{{ $quittance->date_emission->format('d/m/Y') }}</span>
        </div>
    </div>

    <div class="mention">
        Ce document atteste du paiement mentionné ci-dessus.
    </div>

</body>
</html>
