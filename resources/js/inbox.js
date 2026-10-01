/*
 * Keeps unread-message badges current without a reload: the shared health-station inbox on staff
 * pages, the patient's own conversation on patient pages.
 */

const userId = Number(document.querySelector('meta[name="chat-user-id"]')?.getAttribute('content') || 0);
const role = document.querySelector('meta[name="chat-role"]')?.getAttribute('content');

let unread = Number(document.querySelector('[data-unread-count]')?.textContent.trim() || 0);

function setUnread(count) {
    unread = Math.max(0, count);

    document.querySelectorAll('[data-unread-count]').forEach((badge) => {
        badge.textContent = unread;
        badge.setAttribute('aria-label', `${unread} unread`);
        badge.classList.toggle('hidden', unread === 0);
    });
    document.querySelectorAll('[data-unread-dot]').forEach((dot) => dot.classList.toggle('hidden', unread === 0));
    document.querySelectorAll('[data-unread-link]').forEach((link) => {
        link.title = unread > 0 ? `${unread} unread message${unread === 1 ? '' : 's'}` : 'No unread messages';
    });
}

// Messages page (staff): a patient's badge in the conversation list; waiting patients move to the top.
function updatePatientRow(patientId, patientUnread) {
    const row = document.querySelector(`[data-patient-id="${patientId}"]`);
    if (!row) {
        return;
    }

    let badge = row.querySelector('[data-patient-unread]');
    if (patientUnread > 0 && row.dataset.active !== '1') {
        if (!badge) {
            badge = document.createElement('span');
            badge.dataset.patientUnread = '';
            badge.title = 'Unread messages';
            badge.className = 'shrink-0 min-w-5 h-5 px-1.5 rounded-full bg-error text-on-error text-[10px] font-bold flex items-center justify-center';
            row.querySelector('h4')?.after(badge);
        }
        badge.textContent = patientUnread;
        row.parentElement.prepend(row);
    } else {
        badge?.remove();
    }
}

function listen() {
    if (!window.Echo || !userId) {
        return;
    }

    if (role === 'staff') {
        window.Echo.private('staff-inbox').listen('StaffInboxUpdated', (e) => {
            setUnread(e.totalUnread);
            updatePatientRow(e.patientId, e.patientUnread);
        });
    } else if (role === 'patient') {
        window.Echo.private(`patient-chat.${userId}`)
            .listen('MessageSent', (e) => {
                if (e.message.sender_id !== userId) {
                    setUnread(unread + 1);
                }
            })
            .listen('MessageRead', (e) => {
                if (e.readByUserId === userId) {
                    setUnread(0);
                }
            });
    }
}

if (window.EchoReady) {
    listen();
} else {
    document.addEventListener('echo-ready', listen, { once: true });
}
