const pad = value => String(value).padStart(2, '0');
const localDate = date => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

export function calendarDays(year, month) {
    const first = new Date(year, month, 1);
    const offset = (first.getDay() + 6) % 7;
    return Array.from({ length: 42 }, (_, index) => new Date(year, month, index - offset + 1));
}

for (const input of document.querySelectorAll('[data-handover-form] input[type="datetime-local"]')) {
    if (typeof HTMLDialogElement === 'undefined') continue;
    const mobile = window.matchMedia('(max-width: 768px)');
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'mobile-datetime-trigger';
    trigger.setAttribute('aria-haspopup', 'dialog');
    trigger.setAttribute('aria-label', 'Pilih tanggal dan jam serah terima');
    input.after(trigger);
    input.classList.add('mobile-datetime-native');
    const dialog = document.createElement('dialog');
    dialog.className = 'mobile-datetime-sheet';
    dialog.setAttribute('aria-label', 'Pilih tanggal dan jam');
    dialog.innerHTML = `
        <div class="datetime-sheet-handle" aria-hidden="true"></div>
        <header class="datetime-sheet-header">
            <span class="datetime-sheet-icon" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4M17 3v4M3 11h18M7 15h2M11 15h2M15 15h2M7 18h2M11 18h2"/></svg></span>
            <div><h2>Pilih Tanggal</h2><p>Jadwal serah terima produk</p></div>
            <button type="button" data-close aria-label="Tutup pemilih tanggal">&times;</button>
        </header>
        <div class="datetime-calendar-nav"><button type="button" data-month="-1" aria-label="Bulan sebelumnya">&lsaquo;</button><h3 data-month-title aria-live="polite"></h3><button type="button" data-month="1" aria-label="Bulan selanjutnya">&rsaquo;</button></div>
        <div class="datetime-weekdays" aria-hidden="true"><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span></div>
        <div class="datetime-calendar" role="group" aria-label="Pilih tanggal" data-days></div>
        <div class="datetime-time"><span>Jam serah terima</span><div><label>Jam<select data-hours aria-label="Jam"></select></label><b aria-hidden="true">:</b><label>Menit<select data-minutes aria-label="Menit"></select></label></div></div>
        <p class="datetime-sheet-error" data-error role="alert"></p>
        <footer class="datetime-sheet-footer"><button type="button" data-today>Hari ini</button><button type="button" data-confirm>Gunakan jadwal</button></footer>`;
    document.body.append(dialog);
    const hours = dialog.querySelector('[data-hours]');
    const minutes = dialog.querySelector('[data-minutes]');
    for (let value = 0; value < 24; value++) hours.add(new Option(pad(value), String(value)));
    for (let value = 0; value < 60; value++) minutes.add(new Option(pad(value), String(value)));
    let selected, month, previousOverflow;
    function updateTrigger() {
        const value = input.value ? new Date(input.value) : null;
        trigger.textContent = value && !Number.isNaN(value.getTime())
            ? new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(value)
            : 'Pilih tanggal dan jam';
    }
    function renderCalendar() {
        dialog.querySelector('[data-month-title]').textContent = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(month);
        const days = dialog.querySelector('[data-days]');
        days.replaceChildren();
        const minimum = input.min.slice(0, 10);
        for (const date of calendarDays(month.getFullYear(), month.getMonth())) {
            const button = document.createElement('button');
            button.type = 'button';
            const day = localDate(date);
            button.textContent = date.getDate();
            button.setAttribute('aria-label', new Intl.DateTimeFormat('id-ID', { dateStyle: 'full' }).format(date));
            button.setAttribute('aria-pressed', String(day === selected));
            if (date.getMonth() !== month.getMonth()) button.classList.add('is-other-month');
            if (day === localDate(new Date())) button.classList.add('is-today');
            button.disabled = day < minimum;
            button.addEventListener('click', () => { selected = day; dialog.querySelector('[data-error]').textContent = ''; renderCalendar(); });
            days.append(button);
        }
    }
    function open() {
        if (!mobile.matches || dialog.open) return;
        const now = new Date();
        const existing = input.value ? new Date(input.value) : null;
        const value = existing && !Number.isNaN(existing.getTime()) ? existing : new Date(now.getTime() + 3600000);
        selected = localDate(value);
        month = new Date(value.getFullYear(), value.getMonth(), 1);
        hours.value = String(value.getHours());
        minutes.value = String(value.getMinutes());
        dialog.querySelector('[data-error]').textContent = '';
        renderCalendar();
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        dialog.showModal();
    }
    trigger.addEventListener('click', open);
    input.addEventListener('change', updateTrigger);
    input.addEventListener('invalid', event => { if (mobile.matches) { event.preventDefault(); open(); } });
    dialog.querySelector('[data-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => { document.body.style.overflow = previousOverflow; if (mobile.matches) trigger.focus(); });
    for (const button of dialog.querySelectorAll('[data-month]')) button.addEventListener('click', () => {
        month = new Date(month.getFullYear(), month.getMonth() + Number(button.dataset.month), 1);
        renderCalendar();
    });
    dialog.querySelector('[data-today]').addEventListener('click', () => {
        const now = new Date();
        selected = localDate(now);
        month = new Date(now.getFullYear(), now.getMonth(), 1);
        renderCalendar();
    });
    dialog.querySelector('[data-confirm]').addEventListener('click', () => {
        const value = `${selected}T${pad(hours.value)}:${pad(minutes.value)}`;
        if (new Date(value) <= new Date() || (input.min && value < input.min)) {
            dialog.querySelector('[data-error]').textContent = 'Pilih tanggal dan jam setelah waktu sekarang.';
            return;
        }
        input.value = value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        dialog.close();
    });
    const desktopTabIndex = input.getAttribute('tabindex');
    const updateViewport = () => {
        if (mobile.matches) input.tabIndex = -1;
        else {
            if (desktopTabIndex === null) input.removeAttribute('tabindex');
            else input.setAttribute('tabindex', desktopTabIndex);
            if (dialog.open) dialog.close();
        }
    };
    mobile.addEventListener('change', updateViewport);
    updateViewport();
    updateTrigger();
}
