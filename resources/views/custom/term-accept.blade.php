@include('form.custom-checkbox', [
                       'name' => 'accept_term',
                       'value' => 'true',
                       'label' => '',
                       'checked' => false,
                   ])
Eu aceito os <a style="text-decoration: underline;" id="open-dialog">termos de uso</a>.
@error('accept_term')
    <div class="text-neg text-small">Confirme os termos de uso</div>
@enderror

<dialog id="dialog-term" class="bs-dialog box">
        <h2>Termos de uso</h2>
        <p>O texto vai aqui</p>
        <div class="actions">
            <button id="close-dialog" class="button outline">Fechar</button>
            <button class="button primary">Confirmar</button>
        </div>
    </dialog>

<script nonce="{{ $cspNonce }}" src="{{ url('/custom/dialogTerm.js') }}"></script>
<link rel="stylesheet" href="{{ url('/custom/dialogTerm.css') }}">
