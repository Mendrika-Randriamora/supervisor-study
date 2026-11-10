@if (session('success'))
    <div class="alert alert-success d-flex gap-2 align-items-start" role="alert">
        <i class="bi bi-check-circle-fill mt-1"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

@if (session('status') === 'profile-updated')
    <div class="alert alert-success d-flex gap-2 align-items-start">
        <i class="bi bi-check-circle-fill mt-1"></i>
        <div>Profil mis à jour.</div>
    </div>
@endif

@if (session('generated_password'))
    <div class="alert alert-warning" role="alert">
        <div class="fw-semibold mb-1"><i class="bi bi-key me-1"></i>Mot de passe temporaire généré</div>
        <div>Communiquez ce mot de passe à l’étudiant, il ne sera plus affiché :</div>
        <code class="fs-6 d-inline-block mt-1">{{ session('generated_password') }}</code>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger d-flex gap-2 align-items-start">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>{{ session('error') }}</div>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Veuillez corriger les erreurs suivantes :</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
