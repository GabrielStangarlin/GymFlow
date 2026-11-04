@php
    $windows = old('windows', isset($plan)
        ? $plan->timeWindows->map(fn ($w) => ['starts_at' => substr($w->starts_at, 0, 5), 'ends_at' => substr($w->ends_at, 0, 5)])->all()
        : [['starts_at' => '', 'ends_at' => '']]);
@endphp
<form method="POST" action="{{ $action }}" class="space-y-4 max-w-md">
    @csrf
    @isset($plan)
        @method('PUT')
    @endisset
    <div>
        <label for="name" class="block text-sm font-medium">Nome</label>
        <input id="name" name="name" value="{{ old('name', $plan->name ?? '') }}" required class="mt-1 w-full rounded border border-gray-300 p-2">
        @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="monthly_price" class="block text-sm font-medium">Preço base mensal (€)</label>
        <input id="monthly_price" name="monthly_price" type="number" step="0.01" min="0" value="{{ old('monthly_price', $plan->monthly_price ?? '') }}" required class="mt-1 w-full rounded border border-gray-300 p-2">
        @error('monthly_price')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>
    <label class="flex items-center gap-2">
        <input type="hidden" name="active" value="0">
        <input type="checkbox" name="active" value="1" @checked(old('active', $plan->active ?? true))>
        Ativo
    </label>
    <fieldset>
        <legend class="text-sm font-medium">Janelas de horário</legend>
        @error('windows')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        <div id="windows">
            @foreach ($windows as $i => $window)
                <div class="window-row mt-2">
                    <div class="flex items-center gap-2">
                        <input type="time" name="windows[{{ $i }}][starts_at]" value="{{ $window['starts_at'] }}" required class="rounded border border-gray-300 p-2">
                        <span>–</span>
                        <input type="time" name="windows[{{ $i }}][ends_at]" value="{{ $window['ends_at'] }}" required class="rounded border border-gray-300 p-2">
                        <button type="button" class="remove-window rounded border px-2 py-1">Remover</button>
                    </div>
                    @error("windows.$i.starts_at")<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    @error("windows.$i.ends_at")<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </div>
        <button type="button" id="add-window" class="mt-2 rounded border px-3 py-1">Adicionar janela</button>
    </fieldset>
    <button type="submit" class="rounded bg-gray-900 text-white px-4 py-2 disabled:opacity-50">Guardar</button>
</form>
<script>
    (() => {
        const box = document.getElementById('windows');
        let next = box.children.length;
        document.getElementById('add-window').addEventListener('click', () => {
            const row = box.firstElementChild.cloneNode(true);
            row.querySelectorAll('p').forEach((p) => p.remove());
            row.querySelectorAll('input').forEach((input) => {
                input.name = input.name.replace(/\[\d+\]/, `[${next}]`);
                input.value = '';
            });
            next++;
            box.appendChild(row);
        });
        box.addEventListener('click', (event) => {
            if (event.target.classList.contains('remove-window') && box.children.length > 1) {
                event.target.closest('.window-row').remove();
            }
        });
    })();
</script>
