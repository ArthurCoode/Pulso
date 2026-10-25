<div class="field">
    <label for="name">Nome</label>
    <input id="name" name="name" value="{{ old('name', $monitor->name) }}" maxlength="100" required>
    @error('name')<small class="error">{{ $message }}</small>@enderror
</div>

<div class="field">
    <label for="url">URL</label>
    <input id="url" name="url" type="url" value="{{ old('url', $monitor->url) }}" placeholder="https://exemplo.com" required>
    @error('url')<small class="error">{{ $message }}</small>@enderror
</div>

<div class="row">
    <div class="field">
        <label for="method">Método</label>
        <select id="method" name="method">
            @foreach (['GET', 'HEAD'] as $method)
                <option value="{{ $method }}" @selected(old('method', $monitor->method) === $method)>{{ $method }}</option>
            @endforeach
        </select>
        @error('method')<small class="error">{{ $message }}</small>@enderror
    </div>

    <div class="field">
        <label for="expected_status">Status esperado</label>
        <input id="expected_status" name="expected_status" type="number" min="100" max="599"
               value="{{ old('expected_status', $monitor->expected_status) }}" required>
        @error('expected_status')<small class="error">{{ $message }}</small>@enderror
    </div>
</div>

<div class="row">
    <div class="field">
        <label for="interval_minutes">Intervalo (minutos)</label>
        <input id="interval_minutes" name="interval_minutes" type="number" min="1" max="1440"
               value="{{ old('interval_minutes', $monitor->interval_minutes) }}" required>
        @error('interval_minutes')<small class="error">{{ $message }}</small>@enderror
    </div>

    <div class="field">
        <label for="timeout">Timeout (segundos)</label>
        <input id="timeout" name="timeout" type="number" min="1" max="25"
               value="{{ old('timeout', $monitor->timeout) }}" required>
        @error('timeout')<small class="error">{{ $message }}</small>@enderror
    </div>
</div>

<div class="field">
    <label for="keyword">Palavra-chave (opcional)</label>
    <input id="keyword" name="keyword" value="{{ old('keyword', $monitor->keyword) }}" maxlength="255">
    <div class="hint">Se preenchida, a página precisa conter este texto. Só funciona com o método GET.</div>
    @error('keyword')<small class="error">{{ $message }}</small>@enderror
</div>

<div class="field">
    <label for="is_public">
        <input id="is_public" name="is_public" type="checkbox" value="1" style="width:auto"
               @checked(old('is_public', $monitor->is_public))>
        Exibir na status page pública
    </label>
    <div class="hint">Aparecem só o nome e a disponibilidade. A URL e as mensagens de erro nunca são mostradas.</div>
    @error('is_public')<small class="error">{{ $message }}</small>@enderror
</div>