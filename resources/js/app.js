import Alpine from 'alpinejs';
import { createIcons, ArrowRight, ArrowUpRight, ArrowLeft, Bell, CalendarDays, CalendarCheck, CalendarCheck2, Check, CircleCheck, CirclePlay, Eye, Gift, Heart, House, LayoutDashboard, LogIn, LogOut, MessagesSquare, PackageCheck, PiggyBank, Plus, ReceiptText, Search, SearchX, ShieldCheck, ShoppingBag, ShoppingBasket, ShoppingCart, Sprout, Target, UserRound, UserRoundPlus, Wallet, Calculator, Info, ExternalLink, Upload, FileText, Sheet } from 'lucide';
window.Alpine = Alpine;
Alpine.data('catalogSort', (value) => ({
    open: false,
    selected: ['popular', 'low', 'high'].includes(value) ? value : 'popular',
    labels: { popular: 'Pilihan kami', low: 'Harga terendah', high: 'Harga tertinggi' },
    get label() { return this.labels[this.selected]; },
    toggle() { this.open ? this.close() : this.openMenu(); },
    openMenu() {
        this.open = true;
        this.$nextTick(() => this.focusOption(['popular', 'low', 'high'].indexOf(this.selected)));
    },
    close() {
        if (!this.open) return;
        this.open = false;
        this.$refs.trigger.focus();
    },
    focusOption(index) {
        const options = this.$refs.list.querySelectorAll('[role="option"]');
        options[Math.max(0, Math.min(options.length - 1, index))]?.focus();
    },
    move(direction) {
        const options = [...this.$refs.list.querySelectorAll('[role="option"]')];
        const index = options.indexOf(document.activeElement);
        this.focusOption((index + direction + options.length) % options.length);
    },
    choose(value) {
        if (!Object.hasOwn(this.labels, value)) return;
        this.selected = value;
        this.open = false;
        this.$refs.trigger.focus();
        this.$nextTick(() => this.$el.closest('form').requestSubmit());
    },
}));
Alpine.data('heroCloud', () => ({
    x: null, y: null, dragging: false, pointerId: null,
    grabX: 0, grabY: 0, observer: null,
    get position() {
        return this.x === null ? {} : { left: `${this.x}px`, top: `${this.y}px`, right: 'auto', bottom: 'auto' };
    },
    init() {
        this.$nextTick(() => {
            const parent = this.$el.offsetParent;
            const rect = this.$el.getBoundingClientRect();
            const origin = parent.getBoundingClientRect();
            this.x = rect.left - origin.left;
            this.y = rect.top - origin.top;
            this.keepInside();
            this.observer = new ResizeObserver(() => this.keepInside());
            this.observer.observe(parent);
            this.observer.observe(this.$el);
            this.observer.observe(this.$el.closest('.hero').querySelector('.hero-image'));
        });
    },
    destroy() { this.observer?.disconnect(); },
    bounds() {
        const hero = this.$el.closest('.hero');
        const image = hero.querySelector('.hero-image').getBoundingClientRect();
        const parent = this.$el.offsetParent.getBoundingClientRect();
        const copy = hero.querySelector('.hero-copy').getBoundingClientRect();
        const mobile = window.matchMedia('(max-width: 768px)').matches;
        const width = this.$el.offsetWidth;
        const height = this.$el.offsetHeight;
        const padding = 10;
        let left = Math.max(image.left + padding, parent.left + padding);
        const right = Math.min(image.right, parent.right) - padding;
        if (!mobile && right - copy.right - padding >= width) left = Math.max(left, copy.right + padding);
        const top = Math.max(parent.top + padding, image.top + (mobile ? image.height * .16 : padding), mobile ? copy.bottom + padding : image.top);
        const bottom = Math.min(image.bottom, parent.bottom) - padding;
        return {
            minX: left - parent.left,
            maxX: Math.max(left - parent.left, right - parent.left - width),
            minY: top - parent.top,
            maxY: Math.max(top - parent.top, bottom - parent.top - height),
        };
    },
    keepInside() {
        if (this.x === null) return;
        const bounds = this.bounds();
        this.x = Math.min(bounds.maxX, Math.max(bounds.minX, this.x));
        this.y = Math.min(bounds.maxY, Math.max(bounds.minY, this.y));
    },
    start(event) {
        if (!event.isPrimary || event.button !== 0 || this.pointerId !== null) return;
        const rect = this.$el.getBoundingClientRect();
        this.grabX = event.clientX - rect.left;
        this.grabY = event.clientY - rect.top;
        this.pointerId = event.pointerId;
        this.dragging = true;
        this.$el.setPointerCapture(event.pointerId);
        event.preventDefault();
    },
    move(event) {
        if (!this.dragging || event.pointerId !== this.pointerId) return;
        const parent = this.$el.offsetParent.getBoundingClientRect();
        this.x = event.clientX - parent.left - this.grabX;
        this.y = event.clientY - parent.top - this.grabY;
        this.keepInside();
        event.preventDefault();
    },
    end(event) {
        if (event.pointerId !== this.pointerId) return;
        this.pointerId = null;
        this.dragging = false;
        if (this.$el.hasPointerCapture(event.pointerId)) this.$el.releasePointerCapture(event.pointerId);
        this.keepInside();
    },
    nudge(dx, dy) {
        if (this.x === null) return;
        this.x += dx;
        this.y += dy;
        this.keepInside();
    },
}));
Alpine.data('adminChat', (config) => ({
    open: false, conversationId: null, messages: [], body: '', name: null, hasMore: false,
    loading: false, sending: false, olderLoading: false, error: '', timer: null, syncVersion: 0, refreshNeeded: false,
    init() {
        this.timer = setInterval(() => {
            if (this.open && !document.hidden && !this.loading && !this.sending) this.load(false);
        }, 1000);
    },
    destroy() { clearInterval(this.timer); },
    async toggle() {
        if (this.open) return this.close();
        this.open = true;
        await this.load(this.messages.length === 0);
        this.$nextTick(() => this.$refs.input.focus());
    },
    close() { this.open = false; this.$refs.launcher.focus(); },
    async request(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin', cache: 'no-store', ...options,
            headers: { Accept: 'application/json', 'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, ...options.headers },
        });
        if (!response.ok) {
            if (response.status === 419) throw new Error('Sesi berakhir. Muat ulang halaman untuk melanjutkan chat.');
            if (response.status === 429) throw new Error('Pesan terlalu sering dikirim. Tunggu sebentar lalu coba lagi.');
            if (response.status === 403) throw new Error('Akun tidak aktif. Hubungi pengelola untuk bantuan.');
            throw new Error('Pesan belum berhasil diproses. Silakan coba lagi.');
        }
        return response.json();
    },
    merge(incoming) {
        const known = new Map(this.messages.map(message => [message.id, message]));
        incoming.forEach(message => known.set(message.id, message));
        this.messages = [...known.values()].sort((a, b) => a.id - b.id);
    },
    scrollBottom() { this.$nextTick(() => { this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight; }); },
    async load(initial = false) {
        if (this.loading) return;
        initial = initial || this.refreshNeeded;
        const version = this.syncVersion;
        this.loading = true;
        const box = this.$refs.messages;
        const nearBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 80;
        try {
            const lastId = this.messages.at(-1)?.id || 0;
            const data = await this.request(`${config.listUrl}?conversation_id=${this.conversationId || 0}${initial ? '' : `&after=${lastId}`}`);
            if (version !== this.syncVersion) return;
            this.refreshNeeded = false;
            if (data.reset) { this.messages = []; this.hasMore = false; }
            this.conversationId = data.conversation_id;
            this.name = data.name;
            if (initial || data.reset) this.hasMore = data.has_more;
            this.merge(data.messages);
            this.error = '';
            if (initial || nearBottom) this.scrollBottom();
        } catch (error) { this.error = error.message; }
        finally { this.loading = false; }
    },
    async loadOlder() {
        if (this.olderLoading || !this.messages.length) return;
        const version = this.syncVersion;
        this.olderLoading = true;
        const box = this.$refs.messages;
        const height = box.scrollHeight;
        const top = box.scrollTop;
        try {
            const data = await this.request(`${config.listUrl}?before=${this.messages[0].id}&conversation_id=${this.conversationId || 0}`);
            if (version !== this.syncVersion) return;
            if (data.reset) this.messages = [];
            this.conversationId = data.conversation_id;
            this.hasMore = data.has_more;
            this.merge(data.messages);
            this.$nextTick(() => { box.scrollTop = top + box.scrollHeight - height; });
        } catch (error) { this.error = error.message; }
        finally { this.olderLoading = false; }
    },
    async send() {
        if (this.sending || !this.body.trim()) return;
        this.sending = true;
        this.syncVersion++;
        try {
            const data = await this.request(config.sendUrl, { method: 'POST', body: JSON.stringify({ body: this.body.trim() }) });
            this.merge([data.message]);
            this.body = '';
            this.error = '';
            this.scrollBottom();
            this.refreshNeeded = true;
        } catch (error) { this.error = error.message; }
        finally { this.sending = false; }
        if (this.refreshNeeded) void this.load(true);
    },
    formatTime(value) { return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(value)); },
}));
Alpine.start();
const appIcons = { ArrowRight, ArrowUpRight, ArrowLeft, Bell, CalendarDays, CalendarCheck, CalendarCheck2, Check, CircleCheck, CirclePlay, Eye, Gift, Heart, House, LayoutDashboard, LogIn, LogOut, MessagesSquare, PackageCheck, PiggyBank, Plus, ReceiptText, Search, SearchX, ShieldCheck, ShoppingBag, ShoppingBasket, ShoppingCart, Sprout, Target, UserRound, UserRoundPlus, Wallet, Calculator, Info, ExternalLink, Upload, FileText, Sheet };
createIcons({ icons: appIcons, attrs: { class: 'icon' } });
if ('serviceWorker' in navigator) {
window.addEventListener('load',()=>navigator.serviceWorker.register('/sw.js').catch(()=>{}));
}
let installPrompt;
window.addEventListener('beforeinstallprompt',event=>{event.preventDefault();installPrompt=event;document.querySelectorAll('[data-install]').forEach(el=>el.hidden=false);});
document.querySelectorAll('[data-install]').forEach(el=>el.addEventListener('click',async()=>{if(installPrompt){installPrompt.prompt();await installPrompt.userChoice;installPrompt=null;el.hidden=true;}}));
