@extends('layouts.app')

@section('title', 'Admin')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-md mb-lg">
    <div>
        <h3 class="font-headline-lg text-headline-lg text-primary">Admin Settings</h3>
        <p class="text-on-surface-variant font-body-md">Manage system settings, user accounts, and configuration.</p>
    </div>
    <div class="flex gap-sm w-full sm:w-auto">
        <button class="flex-1 sm:flex-initial px-md py-sm border border-outline-variant rounded-lg font-label-md flex items-center justify-center gap-xs hover:bg-surface-container-high transition-all">
            <span class="material-symbols-outlined">settings_backup_restore</span>
            System Logs
        </button>
        <button class="flex-1 sm:flex-initial bg-primary text-on-primary px-md py-sm rounded-lg font-label-md flex items-center justify-center gap-xs hover:opacity-90 active:scale-95 transition-all shadow-sm">
            <span class="material-symbols-outlined">add</span>
            Add User
        </button>
    </div>
</div>

<div class="bg-surface-container-lowest rounded-xl soft-drop-shadow border border-outline-variant/10 mb-lg">
    <div class="flex items-center justify-between p-md border-b border-outline-variant/10 flex-col sm:flex-row gap-sm">
        <div class="flex items-center gap-sm">
            <span class="material-symbols-outlined text-primary">manage_accounts</span>
            <h4 class="font-headline-sm text-headline-sm">User Management</h4>
        </div>
        <div class="flex items-center gap-sm w-full sm:w-auto">
            <div class="relative flex-1 sm:flex-initial">
                <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                <input type="text" placeholder="Search users..." class="bg-surface-container border-none rounded-lg pl-xl pr-md py-xs text-label-sm outline-none focus:ring-1 focus:ring-primary w-full sm:w-52">
            </div>
            <select class="bg-surface-container border-none rounded-lg px-md py-xs text-label-sm outline-none focus:ring-1 focus:ring-primary">
                <option>All Roles</option>
                <option>Admin</option>
                <option>Doctor</option>
                <option>Nurse</option>
                <option>Midwife</option>
            </select>
        </div>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr class="text-left text-label-sm text-on-surface-variant border-b border-outline-variant/10 bg-surface-container-high/10">
                <th class="p-md font-medium">
                    <input type="checkbox" class="rounded border-outline-variant">
                </th>
                <th class="p-md font-medium">User</th>
                <th class="p-md font-medium">Role</th>
                <th class="p-md font-medium">Barangay</th>
                <th class="p-md font-medium">Last Active</th>
                <th class="p-md font-medium">Status</th>
                <th class="p-md font-medium"></th>
            </tr>
        </thead>
        <tbody>
            @php
                $users = [
                    ['Dr. Maria Santos', 'maria.santos@bch.gov.ph', 'Doctor', 'Pulang Bato', '2 min ago', 'active', 'primary'],
                    ['Nurse Elena Reyes', 'elena.reyes@bch.gov.ph', 'Nurse', 'San Jose', '15 min ago', 'active', 'secondary'],
                    ['Admin Juan Carlos', 'admin@bch.gov.ph', 'Admin', 'Central', '1 hour ago', 'active', 'primary'],
                    ['Midwife Rosa Mendez', 'rosa.mendez@bch.gov.ph', 'Midwife', 'Sta. Cruz', '3 hours ago', 'active', 'tertiary'],
                    ['Dr. Antonio Cruz', 'antonio.cruz@bch.gov.ph', 'Doctor', 'Malate', 'Yesterday', 'inactive', 'primary'],
                    ['Nurse Liza So', 'liza.so@bch.gov.ph', 'Nurse', 'Pulang Bato', 'Yesterday', 'inactive', 'secondary'],
                    ['Midwife Clara Gomez', 'clara.gomez@bch.gov.ph', 'Midwife', 'San Jose', 'Oct 28, 2023', 'suspended', 'tertiary'],
                ];
            @endphp
            @foreach($users as $user)
            <tr class="border-b border-outline-variant/5 hover:bg-surface-container-high/20 transition-colors">
                <td class="p-md"><input type="checkbox" class="rounded border-outline-variant"></td>
                <td class="p-md">
                    <div class="flex items-center gap-sm">
                        <div class="w-9 h-9 rounded-full bg-{{ $user[5] === 'active' ? 'tertiary' : ($user[5] === 'inactive' ? 'outline' : 'error') }}-container/20 flex items-center justify-center">
                            <span class="material-symbols-outlined text-{{ $user[5] === 'active' ? 'tertiary' : ($user[5] === 'inactive' ? 'outline' : 'error') }} text-[18px]" style="font-variation-settings: 'FILL' 1;">person</span>
                        </div>
                        <div>
                            <p class="text-label-md font-label-md">{{ $user[0] }}</p>
                            <p class="text-label-sm text-on-surface-variant">{{ $user[1] }}</p>
                        </div>
                    </div>
                </td>
                <td class="p-md">
                    <span class="px-sm py-xs bg-{{ $user[6] }}-container/20 text-{{ $user[6] }} rounded-full text-label-sm font-label-sm">{{ $user[2] }}</span>
                </td>
                <td class="p-md text-label-md text-on-surface-variant">{{ $user[3] }}</td>
                <td class="p-md text-label-md text-on-surface-variant">{{ $user[4] }}</td>
                <td class="p-md">
                    @if($user[5] === 'active')
                    <span class="flex items-center gap-xs text-tertiary text-label-sm font-label-sm">
                        <span class="w-1.5 h-1.5 bg-tertiary rounded-full animate-pulse"></span>
                        Active
                    </span>
                    @elseif($user[5] === 'inactive')
                    <span class="flex items-center gap-xs text-on-surface-variant text-label-sm font-label-sm">
                        <span class="w-1.5 h-1.5 bg-outline rounded-full"></span>
                        Inactive
                    </span>
                    @else
                    <span class="flex items-center gap-xs text-error text-label-sm font-label-sm">
                        <span class="w-1.5 h-1.5 bg-error rounded-full"></span>
                        Suspended
                    </span>
                    @endif
                </td>
                <td class="p-md">
                    <button class="text-primary text-label-sm font-label-sm hover:underline">Edit</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    <div class="flex items-center justify-between p-md border-t border-outline-variant/10">
        <span class="text-label-sm text-on-surface-variant">Showing 7 of 24 users</span>
        <div class="flex gap-sm">
            <button class="px-sm py-xs border border-outline-variant rounded-lg text-label-sm hover:bg-surface-container-high transition-colors">Previous</button>
            <button class="px-sm py-xs bg-primary text-on-primary rounded-lg text-label-sm">1</button>
            <button class="px-sm py-xs border border-outline-variant rounded-lg text-label-sm hover:bg-surface-container-high transition-colors">2</button>
            <button class="px-sm py-xs border border-outline-variant rounded-lg text-label-sm hover:bg-surface-container-high transition-colors">3</button>
            <button class="px-sm py-xs border border-outline-variant rounded-lg text-label-sm hover:bg-surface-container-high transition-colors">Next</button>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-gutter mb-lg">
    <div class="col-span-1 xl:col-span-8 space-y-gutter">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-gutter">
            <div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10 hover:border-primary/30 transition-all cursor-pointer group">
                <div class="flex items-center gap-sm mb-sm">
                    <span class="material-symbols-outlined text-secondary">settings</span>
                    <h4 class="font-label-md text-on-surface">System Settings</h4>
                </div>
                <p class="text-xs text-on-surface-variant">Configure system preferences, locale, and regional settings.</p>
                <div class="mt-md pt-sm border-t border-outline-variant/10 flex justify-between items-center">
                    <span class="text-label-sm text-on-surface-variant">8 options available</span>
                    <span class="material-symbols-outlined text-primary opacity-0 group-hover:opacity-100 transition-opacity">arrow_forward</span>
                </div>
            </div>
            <div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10 hover:border-primary/30 transition-all cursor-pointer group">
                <div class="flex items-center gap-sm mb-sm">
                    <span class="material-symbols-outlined text-tertiary">backup</span>
                    <h4 class="font-label-md text-on-surface">Backup &amp; Restore</h4>
                </div>
                <p class="text-xs text-on-surface-variant">Database backup management, schedule, and restore points.</p>
                <div class="mt-md pt-sm border-t border-outline-variant/10 flex justify-between items-center">
                    <span class="text-label-sm text-on-surface-variant">Last: Today 03:00 AM</span>
                    <span class="material-symbols-outlined text-primary opacity-0 group-hover:opacity-100 transition-opacity">arrow_forward</span>
                </div>
            </div>
            <div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10 hover:border-primary/30 transition-all cursor-pointer group">
                <div class="flex items-center gap-sm mb-sm">
                    <span class="material-symbols-outlined text-error">shield</span>
                    <h4 class="font-label-md text-on-surface">Security &amp; Audit</h4>
                </div>
                <p class="text-xs text-on-surface-variant">Access logs, 2FA settings, and security policies.</p>
                <div class="mt-md pt-sm border-t border-outline-variant/10 flex justify-between items-center">
                    <span class="text-label-sm text-on-surface-variant">12 new log entries today</span>
                    <span class="material-symbols-outlined text-primary opacity-0 group-hover:opacity-100 transition-opacity">arrow_forward</span>
                </div>
            </div>
            <div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10 hover:border-primary/30 transition-all cursor-pointer group">
                <div class="flex items-center gap-sm mb-sm">
                    <span class="material-symbols-outlined text-secondary">database</span>
                    <h4 class="font-label-md text-on-surface">Data Management</h4>
                </div>
                <p class="text-xs text-on-surface-variant">Data import/export, archiving, and cleanup operations.</p>
                <div class="mt-md pt-sm border-t border-outline-variant/10 flex justify-between items-center">
                    <span class="text-label-sm text-on-surface-variant">2.4 GB / 10 GB used</span>
                    <span class="material-symbols-outlined text-primary opacity-0 group-hover:opacity-100 transition-opacity">arrow_forward</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-span-1 xl:col-span-4 space-y-gutter">
        <div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10">
            <div class="flex items-center gap-sm mb-sm">
                <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">notifications_active</span>
                <h4 class="font-label-md text-on-surface">Notification Settings</h4>
            </div>
            <div class="space-y-sm">
                <label class="flex items-center justify-between p-sm bg-surface-container rounded-lg cursor-pointer">
                    <span class="text-label-sm">Email Alerts</span>
                    <div class="w-9 h-5 bg-primary rounded-full relative cursor-pointer">
                        <div class="w-4 h-4 bg-on-primary rounded-full absolute top-0.5 right-0.5"></div>
                    </div>
                </label>
                <label class="flex items-center justify-between p-sm bg-surface-container rounded-lg cursor-pointer">
                    <span class="text-label-sm">SMS Reminders</span>
                    <div class="w-9 h-5 bg-primary rounded-full relative cursor-pointer">
                        <div class="w-4 h-4 bg-on-primary rounded-full absolute top-0.5 right-0.5"></div>
                    </div>
                </label>
                <label class="flex items-center justify-between p-sm bg-surface-container rounded-lg cursor-pointer">
                    <span class="text-label-sm">System Updates</span>
                    <div class="w-9 h-5 bg-surface-container-high rounded-full relative cursor-pointer">
                        <div class="w-4 h-4 bg-on-surface-variant rounded-full absolute top-0.5 left-0.5"></div>
                    </div>
                </label>
                <label class="flex items-center justify-between p-sm bg-surface-container rounded-lg cursor-pointer">
                    <span class="text-label-sm">Backup Reports</span>
                    <div class="w-9 h-5 bg-surface-container-high rounded-full relative cursor-pointer">
                        <div class="w-4 h-4 bg-on-surface-variant rounded-full absolute top-0.5 left-0.5"></div>
                    </div>
                </label>
            </div>
            <button class="w-full mt-md py-sm text-primary font-label-md text-label-md hover:underline">Configure All</button>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10">
            <div class="flex items-center gap-sm mb-sm">
                <span class="material-symbols-outlined text-secondary">monitoring</span>
                <h4 class="font-label-md text-on-surface">System Health</h4>
            </div>
            <div class="space-y-sm">
                <div>
                    <div class="flex justify-between text-label-sm mb-xs">
                        <span>Server Uptime</span>
                        <span class="text-tertiary font-bold">99.9%</span>
                    </div>
                    <div class="w-full bg-surface-container rounded-full h-1.5">
                        <div class="bg-tertiary h-full rounded-full" style="width: 99.9%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-label-sm mb-xs">
                        <span>Database Load</span>
                        <span class="text-primary font-bold">42%</span>
                    </div>
                    <div class="w-full bg-surface-container rounded-full h-1.5">
                        <div class="bg-primary h-full rounded-full" style="width: 42%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-label-sm mb-xs">
                        <span>API Latency</span>
                        <span class="text-secondary font-bold">124ms</span>
                    </div>
                    <div class="w-full bg-surface-container rounded-full h-1.5">
                        <div class="bg-secondary h-full rounded-full" style="width: 35%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bg-surface-container-lowest rounded-xl soft-drop-shadow border border-outline-variant/10 overflow-hidden">
    <div class="flex items-center justify-between p-md border-b border-outline-variant/10">
        <div class="flex items-center gap-sm">
            <span class="material-symbols-outlined text-error">receipt_long</span>
            <h4 class="font-headline-sm text-headline-sm">Recent Activity Log</h4>
        </div>
        <button class="text-primary text-label-sm font-label-sm hover:underline flex items-center gap-xs">
            <span class="material-symbols-outlined text-[16px]">download</span>
            Export Log
        </button>
    </div>
    <div class="divide-y divide-outline-variant/5">
        @php
            $activities = [
                ['Dr. Maria Santos', 'logged in', '2 min ago', 'primary'],
                ['Nurse Elena Reyes', 'updated patient record #BC-2023-0842', '15 min ago', 'secondary'],
                ['System', 'daily backup completed', '1 hour ago', 'tertiary'],
                ['Admin Juan Carlos', 'added new user: Midwife Rosa Mendez', '3 hours ago', 'primary'],
                ['Dr. Antonio Cruz', 'generated immunization report Q3 2023', '5 hours ago', 'error'],
                ['System', 'automatic SMS sent to 24 patients', '8 hours ago', 'secondary'],
                ['Nurse Liza So', 'updated vaccination record #VC-2023-1124', '1 day ago', 'tertiary'],
            ];
        @endphp
        @foreach($activities as $activity)
        <div class="flex items-start gap-sm p-md hover:bg-surface-container-high/20 transition-colors">
            <div class="w-2 h-2 rounded-full bg-{{ $activity[3] }} mt-xs"></div>
            <div class="flex-1">
                <p class="text-label-md">
                    <span class="font-label-md">{{ $activity[0] }}</span>
                    <span class="text-on-surface-variant"> {{ $activity[1] }}</span>
                </p>
                <p class="text-label-sm text-on-surface-variant">{{ $activity[2] }}</p>
            </div>
            <span class="material-symbols-outlined text-on-surface-variant text-[16px] cursor-pointer hover:text-primary transition-colors">more_vert</span>
        </div>
        @endforeach
    </div>
    <div class="p-md border-t border-outline-variant/10 text-center">
        <button class="text-primary text-label-sm font-label-sm hover:underline">View All Activity</button>
    </div>
</div>
@endsection
