@extends('layouts.app')

@section('title', 'Add Polling Station')

@section('content')
    <div class="mb-3">
        <a href="{{ route('polling-stations.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>
    <h4 class="page-title mb-3">Add Polling Station</h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('polling-stations.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">UC</label>
                    <select name="uc_id" class="form-select @error('uc_id') is-invalid @enderror" required>
                        <option value="">Select UC</option>
                        @foreach ($ucs as $uc)
                            <option value="{{ $uc->id }}" {{ (old('uc_id', $ucId) == $uc->id) ? 'selected' : '' }}>
                                {{ $uc->tehsil->district->name ?? '' }} / {{ $uc->tehsil->name ?? '' }} / {{ $uc->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('uc_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Block Code (optional)</label>
                    <select name="block_code_id" id="blockCodeSelect" class="form-select @error('block_code_id') is-invalid @enderror">
                        <option value="">Select Block Code</option>
                        @foreach ($blockCodes as $bc)
                            <option value="{{ $bc->id }}" {{ (old('block_code_id') == $bc->id) ? 'selected' : '' }}>{{ $bc->code }}</option>
                        @endforeach
                    </select>
                    @error('block_code_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Station Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Address (optional)</label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const ucSelect = document.querySelector('select[name="uc_id"]');
                const blockSelect = document.getElementById('blockCodeSelect');
                if (!ucSelect || !blockSelect) return;

                function loadBlockCodes(ucId, selectedId) {
                    blockSelect.innerHTML = '<option value="">Select Block Code</option>';
                    if (!ucId) return;
                    fetch(`/ucs/${ucId}/block-codes`)
                        .then(r => r.json())
                        .then(list => {
                            list.forEach(bc => {
                                const opt = document.createElement('option');
                                opt.value = bc.id;
                                opt.textContent = bc.code;
                                if (selectedId && Number(bc.id) === Number(selectedId)) opt.selected = true;
                                blockSelect.appendChild(opt);
                            });
                        })
                        .catch(() => {});
                }

                ucSelect.addEventListener('change', () => loadBlockCodes(ucSelect.value, null));
                if (ucSelect.value) loadBlockCodes(ucSelect.value, '{{ old('block_code_id') }}');
            })();
        </script>
    @endpush
@endsection
