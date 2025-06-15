<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="name" class="form-label">Nama SO Item Rahil <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $so_event->name ?? '') }}" required>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                <option value="pending" {{ old('status', $so_event->status ?? '') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="active" {{ old('status', $so_event->status ?? '') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="completed" {{ old('status', $so_event->status ?? '') == 'completed' ? 'selected' : '' }}>Selesai</option>
                <option value="cancelled" {{ old('status', $so_event->status ?? '') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
            @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-group mb-3">
    <label for="description" class="form-label">Deskripsi</label>
    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $so_event->description ?? '') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="start_date" class="form-label">Tanggal Mulai</label>
            <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', isset($so_event->start_date) ? $so_event->start_date->format('Y-m-d') : '') }}">
            @error('start_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="end_date" class="form-label">Tanggal Selesai</label>
            <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', isset($so_event->end_date) ? $so_event->end_date->format('Y-m-d') : '') }}">
            @error('end_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save"></i> {{ isset($so_event) ? 'Update SO Item Rahil' : 'Simpan SO Item Rahil' }}
    </button>
    <a href="{{ route('admin.so-events.index') }}" class="btn btn-secondary">Batal</a>
</div>
