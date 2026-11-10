<div class="row g-3">
    <div class="col-12 col-md-6">
        <x-field name="name" label="Nom complet" :required="true" />
    </div>
    <div class="col-12 col-md-6">
        <x-field name="email" label="Adresse e-mail" type="email" :required="true" help="Sert d’identifiant de connexion." />
    </div>
    <div class="col-12 col-md-6">
        <x-field name="phone" label="Téléphone" type="tel" placeholder="Facultatif" />
    </div>
    @if (! isset($student))
        <div class="col-12 col-md-6">
            <x-field name="password" label="Mot de passe initial" type="password" :required="true" help="8 caractères minimum." />
        </div>
        <div class="col-12 col-md-6">
            <x-field name="password_confirmation" label="Confirmer le mot de passe" type="password" :required="true" />
        </div>
    @endif
    <div class="col-12">
        <x-textarea name="notes" label="Notes internes" :rows="3" placeholder="Informations utiles au suivi (facultatif)" help="Ces notes ne sont visibles que par les superviseurs." />
    </div>
</div>
