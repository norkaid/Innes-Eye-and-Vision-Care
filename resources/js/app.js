import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Appointment booking form: loads genuinely available times for the chosen
 * branch and date from the server (calculated from opening hours, booking rules
 * and existing confirmed appointments).
 */
Alpine.data('bookingForm', (config) => ({
    branch: config.branch || '',
    service: config.service || '',
    date: config.date || '',
    time: config.time || '',
    branchServices: config.branchServices || {},
    slots: [],
    loading: false,
    message: '',
    init() {
        if (this.branch && this.date) this.loadSlots(true);
    },
    serviceAvailable(id) {
        if (!this.branch) return true;
        return (this.branchServices[this.branch] || []).includes(Number(id));
    },
    onBranchChange() {
        if (this.service && !this.serviceAvailable(this.service)) this.service = '';
        this.loadSlots();
    },
    async loadSlots(keep = false) {
        if (!keep) this.time = '';
        this.slots = [];
        this.message = '';
        if (!this.branch || !this.date) return;
        this.loading = true;
        try {
            const url = new URL(config.endpoint, window.location.origin);
            url.searchParams.set('branch', this.branch);
            url.searchParams.set('date', this.date);
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            this.slots = data.slots || [];
            this.message = data.message || '';
            if (keep && this.time && !this.slots.includes(this.time)) this.time = '';
        } catch (e) {
            this.message = 'We could not load available times. Please try again or contact the branch.';
        } finally {
            this.loading = false;
        }
    },
}));

Alpine.start();
