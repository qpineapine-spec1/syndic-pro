@extends('layouts.app')

@section('content')
<div class="card-glass" style="padding:2rem;">
    <h2>Importer PDF - Première Assemblée</h2>
    <form action="{{ route('import.preview') }}" method="post" enctype="multipart/form-data">
        @csrf
        <div>
            <label for="pdf">Fichier PDF</label>
            <input type="file" name="pdf" id="pdf" accept="application/pdf" required />
        </div>
        @if(!empty($alreadyImported))
            <div class="alert alert-warning" style="margin-top:1rem;">
                <strong>Attention :</strong> Un import a déjà été effectué pour votre immeuble le {{ ($importedAt?->format('d/m/Y à H:i') ?? $importedAt) }}. Un nouvel import peut créer des doublons.
            </div>
            <div style="margin-top:1rem;">
                <label><input type="checkbox" id="force_import" name="force" value="1"> Je comprends les risques et je veux forcer cet import</label>
            </div>
        @endif

        <div style="margin-top:1rem;">
            <button id="submitBtn" class="btn-primary">Télécharger et Prévisualiser</button>
        </div>
        <script>
            (function(){
                var already = !!{{ !empty($alreadyImported) ? '1' : '0' }};
                var submit = document.getElementById('submitBtn');
                if (already) {
                    submit.disabled = true;
                    var chk = document.getElementById('force_import');
                    if (chk) {
                        chk.addEventListener('change', function() { submit.disabled = !this.checked; });
                    }
                }
            })();
        </script>
    </form>
</div>
@endsection
