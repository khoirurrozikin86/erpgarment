<div class="card mb-3">
    <div class="card-body py-3">
        <div class="row align-items-end g-2">
            <div class="col-sm-5 col-md-3">
                <label class="form-label mb-1" for="from_date">Dari Tanggal</label>
                <input type="date" id="from_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}">
            </div>
            <div class="col-sm-5 col-md-3">
                <label class="form-label mb-1" for="to_date">Sampai Tanggal</label>
                <input type="date" id="to_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}">
            </div>
            <div class="col-sm-2 col-md-auto">
                <button type="button" id="applyDateFilter" class="btn btn-sm btn-primary"><i data-feather="filter" class="icon-sm me-1"></i>Filter</button>
                <button type="button" id="todayDateFilter" class="btn btn-sm btn-outline-secondary" title="Tampilkan data hari ini"><i data-feather="rotate-ccw" class="icon-sm"></i> Hari Ini</button>
            </div>
        </div>
    </div>
</div>
