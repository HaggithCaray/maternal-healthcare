@extends('layouts.app')

@section('title', 'Messaging')

@push('styles')
<style>
    .chat-bubble-in { border-radius: 1px 16px 16px 16px; }
    .chat-bubble-out { border-radius: 16px 1px 16px 16px; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbdcec; border-radius: 10px; }

    /* Toast notification styles */
    .toast-container {
        position: fixed;
        top: 1rem;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        pointer-events: none;
    }
    .toast {
        pointer-events: auto;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1.25rem;
        border-radius: 1rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.1);
        font-size: 0.875rem;
        font-weight: 500;
        opacity: 0;
        transform: translateY(-1.5rem) scale(0.95);
        transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
        max-width: 420px;
        backdrop-filter: blur(12px);
    }
    .toast.show {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    .toast.hide {
        opacity: 0;
        transform: translateY(-1rem) scale(0.95);
    }
    .toast-error {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.15);
    }
    .toast-success {
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.15);
    }
    .toast-icon {
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .toast-close {
        margin-left: auto;
        background: rgba(255,255,255,0.15);
        border: none;
        color: inherit;
        cursor: pointer;
        border-radius: 50%;
        width: 1.5rem;
        height: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: background 0.2s;
    }
    .toast-close:hover {
        background: rgba(255,255,255,0.3);
    }
    .toast-progress {
        position: absolute;
        bottom: 0;
        left: 1rem;
        right: 1rem;
        height: 3px;
        background: rgba(255,255,255,0.25);
        border-radius: 2px;
        overflow: hidden;
    }
    .toast-progress-bar {
        height: 100%;
        background: rgba(255,255,255,0.7);
        border-radius: 2px;
        animation: toast-countdown 4s linear forwards;
    }
    @keyframes toast-countdown {
        from { width: 100%; }
        to { width: 0%; }
    }
</style>
@endpush

@section('content')
<!-- Toast notification container -->
<div class="toast-container" id="toast-container"></div>

<div class="h-[calc(100dvh-64px)] -mx-margin-mobile -my-margin-mobile md:-mx-margin-desktop md:-my-margin-desktop flex overflow-hidden">
    <section class="w-80 flex flex-col bg-surface-container-low/50 border-r border-outline-variant/30 hidden md:flex shrink-0">
        <div class="p-md">
            <div class="relative group">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">search</span>
                <input class="w-full pl-10 pr-4 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-transparent outline-hidden transition-all" placeholder="Search patients..." type="text">
            </div>
        </div>
        <div class="flex-1 overflow-y-auto custom-scrollbar">
            @forelse($patients as $p)
            @php
                $isActive = $activeChatUser && $activeChatUser->id === $p->id;
            @endphp
            <a href="{{ route('messaging', ['chat_user_id' => $p->id]) }}" data-patient-id="{{ $p->id }}" class="block px-md py-4 {{ $isActive ? 'bg-primary-container/20 border-l-4 border-primary' : 'hover:bg-surface-variant/30' }} cursor-pointer transition-colors">
                <div class="flex gap-3">
                    <div class="relative w-12 h-12 rounded-full bg-surface-container-highest flex items-center justify-center font-bold text-primary border-2 border-primary-container shrink-0">
                        {{ strtoupper(substr($p->name, 0, 2)) }}
                        <span class="online-indicator absolute bottom-0 right-0 w-3 h-3 rounded-full bg-emerald-500 border-2 border-surface hidden"></span>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <div class="flex justify-between items-center mb-0.5">
                            <h4 class="font-bold text-sm truncate">{{ $p->name }}</h4>
                        </div>
                        <p class="text-xs text-on-surface-variant truncate">{{ $p->email }}</p>
                    </div>
                </div>
            </a>
            @empty
            <div class="p-md text-center text-xs text-on-surface-variant">No patients registered.</div>
            @endforelse
        </div>
    </section>

    <section class="flex-1 flex flex-col bg-surface relative">
        @if($activeChatUser)
        <div class="h-16 flex items-center justify-between px-sm md:px-md border-b border-outline-variant/20">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary-container flex items-center justify-center font-bold text-primary shrink-0">
                    {{ strtoupper(substr($activeChatUser->name, 0, 2)) }}
                </div>
                <div>
                    <h3 class="font-bold text-sm">{{ $activeChatUser->name }}</h3>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1">
                            <span id="active-user-status-dot" class="w-2 h-2 rounded-full bg-outline-variant"></span>
                            <span id="active-user-status-text" class="text-[10px] uppercase tracking-wider font-bold text-on-surface-variant">OFFLINE</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 p-sm md:p-md overflow-y-auto custom-scrollbar flex flex-col gap-4 md:gap-6" id="chat-messages">
            @forelse($messages as $msg)
                @php
                    $isSender = $msg->sender_id === auth()->user()->id;
                @endphp
                @if($isSender)
                <div class="flex gap-3 max-w-[80%] ml-auto flex-row-reverse">
                    <div class="flex flex-col items-end">
                        <div class="bg-primary text-on-primary p-3 chat-bubble-out shadow-md font-medium">
                            @if($msg->message)
                            <p class="text-sm {{ $msg->attachment_path ? 'mb-2' : '' }}">{{ $msg->message }}</p>
                            @endif

                            @if($msg->attachment_path)
                                @if($msg->isImage())
                                <div class="mt-1 max-w-sm rounded-lg overflow-hidden border border-white/20">
                                    <a href="{{ $msg->attachment_url }}" target="_blank">
                                        <img src="{{ $msg->attachment_url }}" alt="{{ $msg->attachment_name }}" class="max-h-60 object-cover rounded-lg">
                                    </a>
                                </div>
                                @elseif($msg->isVideo())
                                <div class="mt-1 max-w-sm rounded-lg overflow-hidden border border-white/20">
                                    <video controls class="max-h-60 w-full rounded-lg">
                                        <source src="{{ $msg->attachment_url }}" type="{{ $msg->attachment_type }}">
                                    </video>
                                </div>
                                @else
                                <div class="mt-1 flex items-center gap-2 p-2 bg-black/10 rounded-lg border border-white/10 max-w-xs">
                                    <span class="material-symbols-outlined text-lg">draft</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs truncate font-medium">{{ $msg->attachment_name }}</p>
                                        <a href="{{ $msg->attachment_url }}" target="_blank" download class="text-[10px] underline opacity-90 hover:opacity-100 flex items-center gap-0.5 mt-0.5">
                                            <span class="material-symbols-outlined text-[12px]">download</span> Download
                                        </a>
                                    </div>
                                </div>
                                @endif
                            @endif
                        </div>
                        <div class="flex items-center gap-1 mt-1">
                            <span class="text-[10px] text-on-surface-variant block">{{ \Carbon\Carbon::parse($msg->created_at)->format('h:i A') }}</span>
                            @if($msg->is_read)
                            <span class="material-symbols-outlined read-status-icon text-sm text-primary" style="font-variation-settings: 'opsz' 14;">done_all</span>
                            @else
                            <span class="material-symbols-outlined read-status-icon text-sm text-on-surface-variant" style="font-variation-settings: 'opsz' 14;">done</span>
                            @endif
                        </div>
                    </div>
                </div>
                @else
                <div class="flex gap-3 max-w-[80%]">
                    <div>
                        <div class="bg-surface-container-high text-on-surface p-3 chat-bubble-in shadow-xs">
                            @if($msg->message)
                            <p class="text-sm {{ $msg->attachment_path ? 'mb-2' : '' }}">{{ $msg->message }}</p>
                            @endif

                            @if($msg->attachment_path)
                                @if($msg->isImage())
                                <div class="mt-1 max-w-sm rounded-lg overflow-hidden border border-outline-variant/30">
                                    <a href="{{ $msg->attachment_url }}" target="_blank">
                                        <img src="{{ $msg->attachment_url }}" alt="{{ $msg->attachment_name }}" class="max-h-60 object-cover rounded-lg">
                                    </a>
                                </div>
                                @elseif($msg->isVideo())
                                <div class="mt-1 max-w-sm rounded-lg overflow-hidden border border-outline-variant/30">
                                    <video controls class="max-h-60 w-full rounded-lg">
                                        <source src="{{ $msg->attachment_url }}" type="{{ $msg->attachment_type }}">
                                    </video>
                                </div>
                                @else
                                <div class="mt-1 flex items-center gap-2 p-2 bg-surface-container-highest rounded-lg border border-outline-variant/20 max-w-xs">
                                    <span class="material-symbols-outlined text-lg text-primary">draft</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs truncate font-medium text-on-surface">{{ $msg->attachment_name }}</p>
                                        <a href="{{ $msg->attachment_url }}" target="_blank" download class="text-[10px] text-primary font-bold hover:underline flex items-center gap-0.5 mt-0.5">
                                            <span class="material-symbols-outlined text-[12px]">download</span> Download
                                        </a>
                                    </div>
                                </div>
                                @endif
                            @endif
                        </div>
                        <span class="text-[10px] text-on-surface-variant mt-1 block">{{ \Carbon\Carbon::parse($msg->created_at)->format('h:i A') }}</span>
                    </div>
                </div>
                @endif
            @empty
            <div data-chat-empty class="my-auto text-center text-on-surface-variant text-body-sm">
                No chat history found. Start the conversation below!
            </div>
            @endforelse

            {{-- Typing indicator --}}
            <div class="flex gap-3 max-w-[80%] hidden" id="typing-indicator">
                <div>
                    <div class="bg-surface-container-high text-on-surface-variant px-4 py-3 chat-bubble-in shadow-xs">
                        <div class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-on-surface-variant/60 rounded-full animate-bounce" style="animation-delay: 0ms;"></span>
                            <span class="w-2 h-2 bg-on-surface-variant/60 rounded-full animate-bounce" style="animation-delay: 150ms;"></span>
                            <span class="w-2 h-2 bg-on-surface-variant/60 rounded-full animate-bounce" style="animation-delay: 300ms;"></span>
                        </div>
                    </div>
                    <span class="text-[10px] text-on-surface-variant mt-1 block italic">typing...</span>
                </div>
            </div>
        </div>

        <form id="chat-form" method="POST" action="{{ route('messaging', ['chat_user_id' => $activeChatUser->id]) }}" class="p-sm md:p-md bg-surface border-t border-outline-variant/20" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="receiver_id" value="{{ $activeChatUser->id }}">
            
            <!-- File preview bar -->
            <div id="file-preview-container" class="hidden mb-2 px-3 py-2 bg-surface-container border border-outline-variant/30 rounded-xl flex items-center justify-between gap-sm">
                <div class="flex items-center gap-2 overflow-hidden">
                    <span class="material-symbols-outlined text-primary">description</span>
                    <span id="file-preview-name" class="text-xs text-on-surface truncate font-medium"></span>
                </div>
                <button type="button" id="remove-file-btn" class="text-error hover:bg-error/10 p-1 rounded-lg transition-colors flex items-center">
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
            </div>

            <div class="flex items-end gap-3 bg-surface-container-lowest border border-outline-variant/50 rounded-2xl p-2 focus-within:ring-2 focus-within:ring-primary transition-all">
                <input type="file" name="file" id="file-input" class="hidden">
                <button type="button" id="clip-btn" class="w-10 h-10 flex items-center justify-center text-outline hover:text-primary hover:bg-surface-container rounded-xl transition-all shrink-0">
                    <span class="material-symbols-outlined">attach_file</span>
                </button>
                <textarea name="message" class="flex-1 bg-transparent border-none focus:ring-0 text-sm py-2 resize-none max-h-32 custom-scrollbar outline-hidden" placeholder="Type a secure message..." rows="1" id="msg-input"></textarea>
                <button type="submit" class="w-10 h-10 flex items-center justify-center bg-primary text-on-primary rounded-xl hover:bg-primary-container transition-colors shadow-md active:scale-95 shrink-0" id="send-btn">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">send</span>
                </button>
            </div>
            <p class="text-[10px] text-center text-outline-variant mt-2 font-medium uppercase tracking-tighter">Only this patient and health station staff can see this conversation.</p>
        </form>
        @else
        <div class="grow flex flex-col items-center justify-center text-on-surface-variant p-md">
            <span class="material-symbols-outlined text-6xl text-outline mb-md">chat_bubble</span>
            <p class="text-sm">Please select a patient from the sidebar to view history or start chatting.</p>
        </div>
        @endif
    </section>

    <section class="w-72 flex-col bg-surface-container-low/30 border-l border-outline-variant/30 hidden xl:flex shrink-0">
        @if($activeChatUser)
        @php 
            $activePatient = \App\Models\Patient::where('user_id', $activeChatUser->id)->first();
            $vitals = $activePatient ? ($activePatient->registration_type === 'Maternal' ? $activePatient->maternalRecord?->checkups?->sortByDesc('date')?->first() : null) : null;
        @endphp
        <div class="p-md flex flex-col items-center text-center border-b border-outline-variant/20">
            <div class="w-24 h-24 rounded-full bg-primary-container flex items-center justify-center font-bold text-3xl text-primary mb-4 shadow-lg shrink-0">
                {{ strtoupper(substr($activeChatUser->name, 0, 2)) }}
            </div>
            <h3 class="font-bold text-lg">{{ $activeChatUser->name }}</h3>
            <p class="text-xs text-on-surface-variant">Patient ID: #BC-{{ $activePatient?->created_at->format('Y') ?? '2026' }}-{{ sprintf('%03d', $activePatient?->id ?? 0) }}</p>
            <div class="mt-4 flex gap-2">
                <span class="px-3 py-1 bg-secondary-container/40 text-on-secondary-container text-[10px] font-bold rounded-full">{{ $activePatient?->registration_type ?? 'Patient' }}</span>
                <span class="px-3 py-1 bg-tertiary-container/10 text-tertiary text-[10px] font-bold rounded-full">{{ $activePatient?->status ?? 'Active' }}</span>
            </div>
        </div>
        <div class="flex-1 p-md overflow-y-auto custom-scrollbar space-y-6">
            <div>
                <h4 class="text-[10px] font-bold text-outline uppercase tracking-widest mb-3">Quick Vitals</h4>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-surface-container-lowest p-3 rounded-xl border border-outline-variant/30 shadow-xs">
                        <p class="text-[10px] text-on-surface-variant">Weight</p>
                        <p class="font-bold text-sm">{{ $vitals?->weight_kg ?? 'N/A' }} kg</p>
                    </div>
                    <div class="bg-surface-container-lowest p-3 rounded-xl border border-outline-variant/30 shadow-xs">
                        <p class="text-[10px] text-on-surface-variant">BP</p>
                        <p class="font-bold text-sm text-secondary">{{ $vitals?->bp ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
            <div>
                <h4 class="text-[10px] font-bold text-outline uppercase tracking-widest mb-3">Demographics</h4>
                <p class="text-xs text-on-surface-variant">Phone: {{ $activePatient?->phone ?? 'N/A' }}</p>
                <p class="text-xs text-on-surface-variant mt-1">Barangay: {{ $activePatient?->barangay ?? 'N/A' }}</p>
            </div>
        </div>
        @else
        <div class="grow flex items-center justify-center p-md text-xs text-on-surface-variant text-center">
            No active patient selected
        </div>
        @endif
    </section>
</div>

@push('scripts')
<script>
    function showToast(message, type = 'error') {
        const container = document.getElementById('toast-container');
        const icons = { error: 'error', success: 'check_circle', warning: 'warning' };
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="material-symbols-outlined toast-icon">${icons[type] || 'info'}</span>
            <span>${message}</span>
            <button class="toast-close" onclick="this.closest('.toast').classList.replace('show','hide'); setTimeout(() => this.closest('.toast')?.remove(), 350);">
                <span class="material-symbols-outlined" style="font-size: 0.875rem;">close</span>
            </button>
            <div class="toast-progress"><div class="toast-progress-bar"></div></div>
        `;
        container.appendChild(toast);
        requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('show')));
        setTimeout(() => {
            if (toast.parentNode) {
                toast.classList.replace('show', 'hide');
                setTimeout(() => toast.remove(), 350);
            }
        }, 4000);
    }

    const chatMessages = document.getElementById('chat-messages');

    // New messages go above the typing bubble, so "typing..." always stays at the bottom.
    function appendMessage(html) {
        chatMessages.querySelector('[data-chat-empty]')?.remove();
        const typing = document.getElementById('typing-indicator');
        if (typing && typing.parentElement === chatMessages) {
            typing.insertAdjacentHTML('beforebegin', html);
        } else {
            chatMessages.insertAdjacentHTML('beforeend', html);
        }
    }
    if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;

    const textarea = document.querySelector('textarea');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }

    const activeChatUserId = {{ $activeChatUser ? $activeChatUser->id : 'null' }};
    const currentUserId = {{ auth()->user()->id }};

    function escapeHTML(str) {
        return str.replace(/[&<>'"]/g, 
            tag => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[tag] || tag)
        );
    }

    function renderMessageHtml(msg, isSender) {
        const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        const isReadIcon = msg.is_read
            ? `<span class="material-symbols-outlined read-status-icon text-sm text-primary" style="font-variation-settings: 'opsz' 14;">done_all</span>`
            : `<span class="material-symbols-outlined read-status-icon text-sm text-on-surface-variant" style="font-variation-settings: 'opsz' 14;">done</span>`;

        let attachmentHtml = '';
        if (msg.attachment_url) {
            const isImg = msg.attachment_type && msg.attachment_type.startsWith('image/');
            const isVid = msg.attachment_type && msg.attachment_type.startsWith('video/');

            if (isImg) {
                attachmentHtml = `
                <div class="mt-1 max-w-sm rounded-lg overflow-hidden border ${isSender ? 'border-white/20' : 'border-outline-variant/30'}">
                    <a href="${msg.attachment_url}" target="_blank">
                        <img src="${msg.attachment_url}" alt="${escapeHTML(msg.attachment_name)}" class="max-h-60 object-cover rounded-lg">
                    </a>
                </div>`;
            } else if (isVid) {
                attachmentHtml = `
                <div class="mt-1 max-w-sm rounded-lg overflow-hidden border ${isSender ? 'border-white/20' : 'border-outline-variant/30'}">
                    <video controls class="max-h-60 w-full rounded-lg">
                        <source src="${msg.attachment_url}" type="${msg.attachment_type}">
                    </video>
                </div>`;
            } else {
                attachmentHtml = `
                <div class="mt-1 flex items-center gap-2 p-2 ${isSender ? 'bg-black/10 border-white/10' : 'bg-surface-container-highest border-outline-variant/20'} rounded-lg border max-w-xs">
                    <span class="material-symbols-outlined text-lg ${isSender ? '' : 'text-primary'}">draft</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs truncate font-medium ${isSender ? '' : 'text-on-surface'}">${escapeHTML(msg.attachment_name)}</p>
                        <a href="${msg.attachment_url}" target="_blank" download class="text-[10px] ${isSender ? 'underline opacity-90 hover:opacity-100' : 'text-primary font-bold hover:underline'} flex items-center gap-0.5 mt-0.5">
                            <span class="material-symbols-outlined text-[12px]">download</span> Download
                        </a>
                    </div>
                </div>`;
            }
        }

        const msgText = msg.message 
            ? `<p class="text-sm ${msg.attachment_url ? 'mb-2' : ''}">${escapeHTML(msg.message)}</p>` 
            : '';

        if (isSender) {
            return `
            <div class="flex gap-3 max-w-[80%] ml-auto flex-row-reverse">
                <div class="flex flex-col items-end">
                    <div class="bg-primary text-on-primary p-3 chat-bubble-out shadow-md font-medium">
                        ${msgText}
                        ${attachmentHtml}
                    </div>
                    <div class="flex items-center gap-1 mt-1">
                        <span class="text-[10px] text-on-surface-variant block">${time}</span>
                        ${isReadIcon}
                    </div>
                </div>
            </div>`;
        } else {
            return `
            <div class="flex gap-3 max-w-[80%]">
                <div>
                    <div class="bg-surface-container-high text-on-surface p-3 chat-bubble-in shadow-xs">
                        ${msgText}
                        ${attachmentHtml}
                    </div>
                    <span class="text-[10px] text-on-surface-variant mt-1 block">${time}</span>
                </div>
            </div>`;
        }
    }

    const chatForm = document.getElementById('chat-form');
    const fileInput = document.getElementById('file-input');
    const clipBtn = document.getElementById('clip-btn');
    const filePreviewContainer = document.getElementById('file-preview-container');
    const filePreviewName = document.getElementById('file-preview-name');
    const removeFileBtn = document.getElementById('remove-file-btn');

    if (clipBtn && fileInput) {
        clipBtn.addEventListener('click', () => fileInput.click());
    }

    const MAX_FILE_SIZE = 20 * 1024 * 1024; // 20MB

    if (fileInput && filePreviewContainer && filePreviewName) {
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                if (file.size > MAX_FILE_SIZE) {
                    showToast('File is too large. Maximum allowed size is 20MB.', 'error');
                    fileInput.value = '';
                    filePreviewContainer.classList.add('hidden');
                    return;
                }
                filePreviewName.textContent = file.name;
                filePreviewContainer.classList.remove('hidden');
            } else {
                filePreviewContainer.classList.add('hidden');
            }
        });
    }

    if (removeFileBtn && fileInput && filePreviewContainer) {
        removeFileBtn.addEventListener('click', () => {
            fileInput.value = '';
            filePreviewContainer.classList.add('hidden');
        });
    }

    if (chatForm && textarea) {
        const sendBtn = document.getElementById('send-btn');
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const message = textarea.value.trim();
            const hasFile = fileInput && fileInput.files.length > 0;
            if (!message && !hasFile) return;

            if (hasFile && fileInput.files[0].size > MAX_FILE_SIZE) {
                showToast('File is too large. Maximum allowed size is 20MB.', 'error');
                return;
            }

            const formData = new FormData(chatForm);

            // Disable input and button
            textarea.disabled = true;
            if (sendBtn) sendBtn.disabled = true;
            if (clipBtn) clipBtn.disabled = true;

            fetch(chatForm.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            })
            .then(async response => {
                if (!response.ok) {
                    // Show the server's reason (rate limit, file too large, ...) when it gives one.
                    const body = await response.json().catch(() => ({}));
                    const error = new Error(`Send failed with status ${response.status}`);
                    error.userMessage = body.message;
                    throw error;
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Clear textarea and reset height
                    textarea.value = '';
                    textarea.style.height = 'auto';

                    // Clear files
                    if (fileInput) fileInput.value = '';
                    if (filePreviewContainer) filePreviewContainer.classList.add('hidden');

                    // Create chat bubble
                    const newMsg = renderMessageHtml(data.message, true);

                    if (chatMessages) {
                        appendMessage(newMsg);
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                showToast(error.userMessage || 'Failed to send message. Please try again.', 'error');
            })
            .finally(() => {
                textarea.disabled = false;
                if (sendBtn) sendBtn.disabled = false;
                if (clipBtn) clipBtn.disabled = false;
                textarea.focus();
            });
        });
    }

    function subscribeToChat() {
        if (!activeChatUserId || !window.Echo) return;

        const conversationId = Math.min(currentUserId, activeChatUserId) + '-' + Math.max(currentUserId, activeChatUserId);
        const typingIndicator = document.getElementById('typing-indicator');
        let typingTimeout = null;
        
        console.log('[WS] Subscribing to conversation.' + conversationId);

        const channel = window.Echo.private('conversation.' + conversationId);

        channel.listen('MessageSent', (e) => {
                console.log('[WS] MessageSent event received:', e);
                // Hide typing indicator when a message arrives
                if (typingIndicator) typingIndicator.classList.add('hidden');

                if (e.message.sender_id !== currentUserId) {
                    const newMsg = renderMessageHtml(e.message, false);
                    
                    if(chatMessages) {
                        appendMessage(newMsg);
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                }
            })
            .listenForWhisper('typing', (e) => {
                console.log('[WS] Typing whisper received:', e);
                if (typingIndicator) {
                    typingIndicator.classList.remove('hidden');
                    if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;
                }
                clearTimeout(typingTimeout);
                typingTimeout = setTimeout(() => {
                    if (typingIndicator) typingIndicator.classList.add('hidden');
                }, 2000);
            })
            .listen('MessageRead', (e) => {
                console.log('[WS] MessageRead event received:', e);
                // Update all single checks to double checks
                if (e.readByUserId !== currentUserId) {
                    document.querySelectorAll('.read-status-icon').forEach(icon => {
                        if (icon.textContent.trim() === 'done') {
                            icon.textContent = 'done_all';
                            icon.classList.remove('text-on-surface-variant');
                            icon.classList.add('text-primary');
                        }
                    });
                }
            });

        // Send typing whisper when user types
        if (textarea) {
            let lastWhisper = 0;
            textarea.addEventListener('input', function() {
                const now = Date.now();
                if (now - lastWhisper > 1000) {
                    channel.whisper('typing', { user: currentUserId });
                    lastWhisper = now;
                }
            });
        }
    }

    const activeUsers = new Set();

    function updateOnlineIndicators() {
        // Update sidebar patients
        document.querySelectorAll('[data-patient-id]').forEach(el => {
            const patientId = parseInt(el.getAttribute('data-patient-id'));
            const indicator = el.querySelector('.online-indicator');
            if (indicator) {
                if (activeUsers.has(patientId)) {
                    indicator.classList.remove('hidden');
                } else {
                    indicator.classList.add('hidden');
                }
            }
        });

        // Update active chat header
        if (activeChatUserId) {
            const headerDot = document.getElementById('active-user-status-dot');
            const headerText = document.getElementById('active-user-status-text');
            if (headerDot && headerText) {
                if (activeUsers.has(parseInt(activeChatUserId))) {
                    headerDot.className = 'w-2 h-2 rounded-full bg-emerald-500 animate-pulse';
                    headerText.textContent = 'ACTIVE NOW';
                    headerText.className = 'text-[10px] uppercase tracking-wider font-bold text-emerald-600';
                } else {
                    headerDot.className = 'w-2 h-2 rounded-full bg-outline-variant';
                    headerText.textContent = 'OFFLINE';
                    headerText.className = 'text-[10px] uppercase tracking-wider font-bold text-on-surface-variant';
                }
            }
        }
    }

    function subscribeToPresence() {
        if (!window.Echo) return;

        console.log('[WS] Joining presence channel: online');

        window.Echo.join('online')
            .here((users) => {
                console.log('[WS] Users online:', users);
                users.forEach(u => activeUsers.add(u.id));
                updateOnlineIndicators();
            })
            .joining((user) => {
                console.log('[WS] User joined:', user);
                activeUsers.add(user.id);
                updateOnlineIndicators();
            })
            .leaving((user) => {
                console.log('[WS] User left:', user);
                activeUsers.delete(user.id);
                updateOnlineIndicators();
            })
            .error((error) => {
                console.error('[WS] Presence channel error:', error);
            });
    }

    // Subscribe when Echo is ready
    if (window.EchoReady) {
        subscribeToChat();
        subscribeToPresence();
    } else {
        document.addEventListener('echo-ready', () => {
            subscribeToChat();
            subscribeToPresence();
        });
    }
</script>
@endpush
@endsection
