@extends('layouts.app')

@section('title', 'Patient Records')

@section('content')
@include('partials.flash')

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-md mb-lg">
    <div>
        <h3 class="font-headline-lg text-headline-lg text-primary">Patient Records</h3>
        <p class="text-on-surface-variant font-body-md">Centralized database for Maternal Health Hub community health tracking.</p>
    </div>
    <a href="{{ route('register') }}" class="bg-primary text-on-primary px-md py-sm rounded-lg font-label-md flex items-center gap-xs hover:opacity-90 active:scale-95 transition-all shadow-sm whitespace-nowrap">
        <span class="material-symbols-outlined">person_add</span>
        Register New Patient
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-md mb-xl">
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
        <div class="flex items-center justify-between mb-sm">
            <span class="material-symbols-outlined text-primary bg-primary-container/20 p-xs rounded-lg">groups</span>
            <span class="text-tertiary text-label-sm font-bold bg-tertiary-fixed/30 px-xs py-[2px] rounded">+{{ round(($maternalCases / max($totalPatients, 1)) * 100) }}% maternal</span>
        </div>
        <p class="text-on-surface-variant text-label-sm uppercase tracking-wider">Total Patients</p>
        <p class="text-headline-md font-bold mt-xs">{{ number_format($totalPatients) }}</p>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
        <div class="flex items-center justify-between mb-sm">
            <span class="material-symbols-outlined text-secondary bg-secondary-container/20 p-xs rounded-lg">pregnant_woman</span>
            <span class="text-on-surface-variant text-label-sm">Mothers</span>
        </div>
        <p class="text-on-surface-variant text-label-sm uppercase tracking-wider">Maternal Cases</p>
        <p class="text-headline-md font-bold mt-xs">{{ number_format($maternalCases) }}</p>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
        <div class="flex items-center justify-between mb-sm">
            <span class="material-symbols-outlined text-tertiary bg-tertiary-fixed/30 p-xs rounded-lg">child_care</span>
            <span class="text-on-surface-variant text-label-sm">Children</span>
        </div>
        <p class="text-on-surface-variant text-label-sm uppercase tracking-wider">Child Records</p>
        <p class="text-headline-md font-bold mt-xs">{{ number_format($childRecords) }}</p>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
        <div class="flex items-center justify-between mb-sm">
            <span class="material-symbols-outlined text-error bg-error-container/40 p-xs rounded-lg">notification_important</span>
            <span class="text-error font-bold text-label-sm">Priority</span>
        </div>
        <p class="text-on-surface-variant text-label-sm uppercase tracking-wider">Due for Visit</p>
        <p class="text-headline-md font-bold mt-xs">{{ $dueForVisit }}</p>
    </div>
</div>

<form action="{{ route('records') }}" method="GET" class="bg-surface-container-low p-sm rounded-xl mb-md flex flex-col sm:flex-row items-start sm:items-center gap-md">
    <div class="w-full sm:flex-1 relative">
        <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline">search</span>
        <input name="search" value="{{ $search }}" class="w-full bg-surface-container-lowest border border-outline-variant/50 rounded-lg pl-xl pr-md py-sm focus:border-primary focus:ring-1 focus:ring-primary transition-all" placeholder="Search by name or phone..." type="text">
    </div>
    <div class="flex items-center gap-sm w-full sm:w-auto">
        <select name="type" onchange="this.form.submit()" class="flex-1 sm:flex-initial bg-surface-container-lowest border border-outline-variant/50 rounded-lg px-md py-sm font-label-md text-on-surface-variant focus:border-primary cursor-pointer">
            <option value="All Types" {{ $type === 'All Types' ? 'selected' : '' }}>All Types</option>
            <option value="Maternal" {{ $type === 'Maternal' ? 'selected' : '' }}>Maternal</option>
            <option value="Child" {{ $type === 'Child' ? 'selected' : '' }}>Child</option>
        </select>
        <select name="status" onchange="this.form.submit()" class="bg-surface-container-lowest border border-outline-variant/50 rounded-lg px-md py-sm font-label-md text-on-surface-variant focus:border-primary cursor-pointer">
            <option value="All Status" {{ $status === 'All Status' ? 'selected' : '' }}>All Status</option>
            <option value="Active" {{ $status === 'Active' ? 'selected' : '' }}>Active</option>
            <option value="Due for Visit" {{ $status === 'Due for Visit' ? 'selected' : '' }}>Due for Visit</option>
            <option value="High Risk" {{ $status === 'High Risk' ? 'selected' : '' }}>High Risk</option>
            <option value="Completed" {{ $status === 'Completed' ? 'selected' : '' }}>Completed</option>
        </select>
        <button type="submit" class="bg-surface-container-highest text-primary p-sm rounded-lg hover:bg-primary-container hover:text-on-primary-container transition-all flex items-center">
            <span class="material-symbols-outlined">filter_list</span>
        </button>
    </div>
</form>

<div class="bg-surface-container-lowest rounded-xl soft-drop-shadow border border-outline-variant/10">
    <div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead class="bg-surface-container-high/50 border-b border-outline-variant/30">
            <tr>
                <th class="px-md py-md font-label-md text-on-surface-variant uppercase text-[11px] tracking-widest">Name &amp; ID</th>
                <th class="px-md py-md font-label-md text-on-surface-variant uppercase text-[11px] tracking-widest">Type</th>
                <th class="px-md py-md font-label-md text-on-surface-variant uppercase text-[11px] tracking-widest">Status</th>
                <th class="px-md py-md font-label-md text-on-surface-variant uppercase text-[11px] tracking-widest">Last Visit</th>
                <th class="px-md py-md font-label-md text-on-surface-variant uppercase text-[11px] tracking-widest">Next Due Date</th>
                <th class="px-md py-md font-label-md text-on-surface-variant uppercase text-[11px] tracking-widest text-right">Quick Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant/20">
            @forelse($patients as $patient)
            <tr class="hover:bg-surface-container-low transition-colors group">
                <td class="px-md py-md">
                    <div class="flex items-center gap-sm">
                        <div class="w-10 h-10 rounded-full bg-primary-container/20 flex items-center justify-center text-primary font-bold">
                            {{ strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-label-md text-on-surface">{{ $patient->first_name }} {{ $patient->last_name }}</p>
                            <p class="text-xs text-on-surface-variant">ID: #BC-{{ $patient->created_at->format('Y') }}-{{ sprintf('%03d', $patient->id) }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-md py-md">
                    @if($patient->registration_type === 'Maternal')
                    <span class="flex items-center gap-xs font-label-sm text-secondary">
                        <span class="material-symbols-outlined text-[18px]">pregnant_woman</span> Maternal
                    </span>
                    @else
                    <span class="flex items-center gap-xs font-label-sm text-primary">
                        <span class="material-symbols-outlined text-[18px]">child_care</span> Child
                    </span>
                    @endif
                </td>
                <td class="px-md py-md">
                    @php
                        $badgeClass = match($patient->status) {
                            'Active' => 'bg-tertiary-fixed/20 text-tertiary border-tertiary-fixed-dim/30',
                            'Due for Visit' => 'bg-error-container/20 text-error border-error-container/30',
                            'High Risk' => 'bg-surface-variant text-on-surface-variant border-outline-variant/30',
                            'Completed' => 'bg-primary-container/20 text-primary border-primary-container/30',
                            default => 'bg-surface-variant text-on-surface-variant'
                        };
                    @endphp
                    <span class="{{ $badgeClass }} px-sm py-xs rounded-full text-xs font-bold border">{{ $patient->status }}</span>
                </td>
                <td class="px-md py-md text-body-sm text-on-surface-variant">
                    {{ $patient->updated_at->format('M d, Y') }}
                </td>
                <td class="px-md py-md text-body-sm text-on-surface font-semibold">
                    @if($patient->registration_type === 'Maternal' && $patient->maternalRecord)
                        {{ $patient->maternalRecord->edd ? $patient->maternalRecord->edd->format('M d, Y') : 'N/A' }}
                    @elseif($patient->registration_type === 'Child' && $patient->childRecord && $patient->childRecord->immunizations->where('status', 'Scheduled')->first())
                        {{ $patient->childRecord->immunizations->where('status', 'Scheduled')->first()->scheduled_date->format('M d, Y') }}
                    @else
                        N/A
                    @endif
                </td>
                <td class="px-md py-md text-right">
                    <div class="flex items-center justify-end gap-xs relative">
                        @if($patient->registration_type === 'Maternal')
                        <a href="{{ route('maternal', ['id' => $patient->id]) }}" class="p-xs text-primary hover:bg-primary-container/30 rounded-lg transition-all" title="View Details">
                            <span class="material-symbols-outlined">visibility</span>
                        </a>
                        @else
                        <a href="{{ route('immunization', ['id' => $patient->id]) }}" class="p-xs text-primary hover:bg-primary-container/30 rounded-lg transition-all" title="View Details">
                            <span class="material-symbols-outlined">visibility</span>
                        </a>
                        @endif
                        <button class="p-xs text-on-surface-variant hover:bg-surface-variant rounded-lg transition-all dropdown-toggle" onclick="toggleDropdown(this)">
                            <span class="material-symbols-outlined">more_vert</span>
                        </button>
                        <div class="hidden absolute right-0 top-full mt-1 w-48 bg-surface-container-low rounded-xl soft-drop-shadow border border-outline-variant/20 py-2 z-50 dropdown-menu">
                            @if($patient->registration_type === 'Maternal')
                            <a href="{{ route('maternal', ['id' => $patient->id]) }}" class="flex items-center gap-sm px-4 py-2 text-on-surface hover:bg-surface-variant/50 transition-colors text-sm">
                                <span class="material-symbols-outlined text-[18px]">visibility</span> View Details
                            </a>
                            @else
                            <a href="{{ route('immunization', ['id' => $patient->id]) }}" class="flex items-center gap-sm px-4 py-2 text-on-surface hover:bg-surface-variant/50 transition-colors text-sm">
                                <span class="material-symbols-outlined text-[18px]">visibility</span> View Details
                            </a>
                            @endif
                            <a href="{{ route('patients.edit', $patient->id) }}" class="flex items-center gap-sm px-4 py-2 text-on-surface hover:bg-surface-variant/50 transition-colors text-sm">
                                <span class="material-symbols-outlined text-[18px]">edit</span> Edit Patient
                            </a>
                            <div class="border-t border-outline-variant/20 my-1"></div>
                        </div>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-md py-xl text-center text-on-surface-variant text-body-sm">
                    No patient records found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="px-md py-sm bg-surface-container-high/20 border-t border-outline-variant/30 flex flex-col sm:flex-row items-center justify-between gap-sm">
        <p class="text-xs text-on-surface-variant">Showing {{ $patients->count() }} patient record(s)</p>
    </div>
</div>

<div class="mt-lg grid grid-cols-1 lg:grid-cols-3 gap-lg">
    <div class="lg:col-span-2 bg-surface-container-lowest p-lg rounded-xl soft-drop-shadow border border-outline-variant/10 relative overflow-hidden">
        <div class="relative z-10">
            <h4 class="font-headline-sm text-primary mb-sm">Record Integrity Report</h4>
            <p class="text-body-sm text-on-surface-variant max-w-lg mb-md">All records are encrypted and compliant with local data privacy laws. Last system backup completed successfully 2 hours ago.</p>
            <div class="flex gap-md">
                <div class="flex items-center gap-xs">
                    <span class="material-symbols-outlined text-tertiary text-sm">check_circle</span>
                    <span class="text-xs font-bold">Verified Data</span>
                </div>
                <div class="flex items-center gap-xs">
                    <span class="material-symbols-outlined text-tertiary text-sm">shield</span>
                    <span class="text-xs font-bold">SSL Secure</span>
                </div>
            </div>
        </div>
        <div class="absolute right-0 top-0 w-32 h-full opacity-10 flex items-center justify-center">
            <span class="material-symbols-outlined text-[120px] text-primary" style="font-variation-settings: 'FILL' 1;">verified_user</span>
        </div>
    </div>
    <div class="bg-secondary-container/20 p-lg rounded-xl border border-secondary/20">
        <h4 class="font-label-md text-on-secondary-container mb-md flex items-center gap-xs">
            <span class="material-symbols-outlined text-secondary">tips_and_updates</span>
            Quick Action Guide
        </h4>
        <ul class="space-y-sm text-body-sm text-on-secondary-container opacity-90">
            <li class="flex items-start gap-xs">
                <span class="mt-1 w-1 h-1 rounded-full bg-secondary"></span>
                Use "High Risk" filters to prioritize visits.
            </li>
            <li class="flex items-start gap-xs">
                <span class="mt-1 w-1 h-1 rounded-full bg-secondary"></span>
                Click on patient IDs to view full medical history.
            </li>
            <li class="flex items-start gap-xs">
                <span class="mt-1 w-1 h-1 rounded-full bg-secondary"></span>
                Generate CSV reports from the "Reports" tab.
            </li>
        </ul>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('mouseenter', () => {
            row.style.transform = 'translateX(4px)';
            row.style.transition = 'transform 0.2s ease-out';
        });
        row.addEventListener('mouseleave', () => {
            row.style.transform = 'translateX(0px)';
        });
    });

    function toggleDropdown(btn) {
        const menu = btn.nextElementSibling;
        const isOpen = !menu.classList.contains('hidden');
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        if (!isOpen) menu.classList.remove('hidden');
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown-toggle') && !e.target.closest('.dropdown-menu')) {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        }
    });
</script>
@endpush
@endsection
