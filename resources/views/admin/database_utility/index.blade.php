@extends('layouts.app')

@section('title', 'Manajemen Database')

@section('content')
<div class="container-fluid">
    <div class="text-center pt-2 pb-2 mb-3 border-bottom">
        <h1 class="h2 d-inline-block mb-0 me-2 align-middle">Manajemen Database</h1>
    </div>

    @include('layouts.flash-messages')

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Daftar Tabel Database</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createTableModal">
                        <i class="bi bi-plus-circle"></i> Buat Tabel Baru
                    </button>
                </div>
                <div class="card-body">
                    @if(!empty($tables))
                    <div class="accordion" id="tablesAccordion">
                        @foreach($tables as $table)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading{{ $loop->index }}">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse{{ $loop->index }}" aria-expanded="false"
                                    aria-controls="collapse{{ $loop->index }}">
                                    <code class="me-2">{{ $table }}</code>
                                    <span class="badge bg-secondary ms-2">{{ $tableDetails[$table]['rowCount'] }} baris</span>
                                </button>
                            </h2>
                            <div id="collapse{{ $loop->index }}" class="accordion-collapse collapse"
                                aria-labelledby="heading{{ $loop->index }}" data-bs-parent="#tablesAccordion">
                                <div class="accordion-body">
                                    <div class="mb-4">
                                        <h6>Struktur Tabel:</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Kolom</th>
                                                        <th>Tipe</th>
                                                        <th>Nullable</th>
                                                        <th>Key</th>
                                                        <th>Default</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($tableDetails[$table]['columnsDetails'] as $column)
                                                    <tr>
                                                        <td><code>{{ $column['name'] }}</code></td>
                                                        <td>{{ $column['type'] }}</td>
                                                        <td>{{ $column['nullable'] ? 'Ya' : 'Tidak' }}</td>
                                                        <td>{{ $column['key'] ?: '-' }}</td>
                                                        <td>{{ $column['default'] ?: 'NULL' }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <h6>Contoh Data (5 baris teratas):</h6>
                                        @if(!empty($tableDetails[$table]['sampleData']))
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead>
                                                    <tr>
                                                        @foreach($tableDetails[$table]['columns'] as $column)
                                                        <th>{{ $column }}</th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($tableDetails[$table]['sampleData'] as $row)
                                                    <tr>
                                                        @foreach($tableDetails[$table]['columns'] as $column)
                                                        <td>
                                                            @if(is_array($row[$column] ?? null) || is_object($row[$column] ?? null))
                                                            {{ json_encode($row[$column]) }}
                                                            @else
                                                            {{ $row[$column] ?? 'NULL' }}
                                                            @endif
                                                        </td>
                                                        @endforeach
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @else
                                        <p class="text-muted">Tidak ada data atau gagal mengambil contoh data.</p>
                                        @endif
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('admin.database.table.data', $table) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Lihat Semua Data
                                        </a>
                                        <form action="{{ route('admin.database.table.destroy', $table) }}" method="POST"
                                            onsubmit="return confirm('Hapus tabel {{ $table }}? Data akan hilang permanen!');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i> Hapus Tabel
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-muted">Tidak ada tabel yang ditemukan.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Kolom Buat Tabel Baru --}}
<div class="col-md-6 mb-4">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">Buat Tabel Baru</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.database.table.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="table_name" class="form-label">Nama Tabel <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('table_name') is-invalid @enderror" id="table_name" name="table_name" value="{{ old('table_name') }}" required pattern="[a-zA-Z0-9_]+" title="Hanya huruf, angka, dan underscore.">
                    @error('table_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <hr>
                <h6>Definisi Kolom <span class="text-danger">*</span></h6>
                <div id="columns-container">
                    {{-- Kolom awal --}}
                    <div class="row column-definition mb-2 gx-2">
                        <div class="col">
                            <input type="text" name="columns[0][name]" class="form-control form-control-sm" placeholder="Nama Kolom (cth: user_name)" required pattern="[a-zA-Z0-9_]+">
                        </div>
                        <div class="col">
                            <select name="columns[0][type]" class="form-select form-select-sm" required>
                                <option value="id">ID (Primary, Auto Increment)</option>
                                <option value="string">String (VARCHAR)</option>
                                <option value="integer">Integer</option>
                                <option value="text">Text</option>
                                <option value="date">Date</option>
                                <option value="boolean">Boolean</option>
                                <option value="timestamps">Timestamps (created_at, updated_at)</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-sm btn-danger remove-column-btn" disabled><i class="bi bi-x-circle"></i></button>
                        </div>
                    </div>
                </div>

                <button type="button" id="add-column-btn" class="btn btn-sm btn-outline-secondary mt-2 mb-3">
                    <i class="bi bi-plus-circle"></i> Tambah Kolom
                </button>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-square"></i> Buat Tabel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const columnsContainer = document.getElementById('columns-container');
        const addColumnBtn = document.getElementById('add-column-btn');
        @php
        $oldColumnsValue = old('columns');
        $initialColumnIndex = 1; // Default value
        // Ensure old('columns') is an array and not empty before counting
        if (is_array($oldColumnsValue) && count($oldColumnsValue) > 0) {
            $initialColumnIndex = count($oldColumnsValue);
        }
        @endphp
        let columnIndex = {
            {
                $initialColumnIndex
            }
        };

        function updateRemoveButtons() {
            const removeButtons = columnsContainer.querySelectorAll('.remove-column-btn');
            removeButtons.forEach((btn, index) => {
                btn.disabled = removeButtons.length <= 1; // Nonaktifkan jika hanya ada satu baris
            });
        }

        addColumnBtn.addEventListener('click', function() {
            const newColumnHtml = `
            <div class="row column-definition mb-2 gx-2">
                <div class="col">
                    <input type="text" name="columns[${columnIndex}][name]" class="form-control form-control-sm" placeholder="Nama Kolom" required pattern="[a-zA-Z0-9_]+">
                </div>
                <div class="col">
                    <select name="columns[${columnIndex}][type]" class="form-select form-select-sm" required>
                        <option value="string">String (VARCHAR)</option>
                        <option value="integer">Integer</option>
                        <option value="text">Text</option>
                        <option value="date">Date</option>
                        <option value="boolean">Boolean</option>
                        <option value="id">ID (Primary, Auto Increment)</option>
                        <option value="timestamps">Timestamps (created_at, updated_at)</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-danger remove-column-btn"><i class="bi bi-x-circle"></i></button>
                </div>
            </div>
        `;
            columnsContainer.insertAdjacentHTML('beforeend', newColumnHtml);
            columnIndex++;
            updateRemoveButtons();
        });

        columnsContainer.addEventListener('click', function(event) {
            if (event.target.closest('.remove-column-btn')) {
                event.target.closest('.column-definition').remove();
                updateRemoveButtons();
            }
        });

        // Panggil saat load untuk memastikan status tombol hapus benar
        updateRemoveButtons();
    });
</script>
@endpush