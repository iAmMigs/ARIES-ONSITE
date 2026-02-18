import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String,
        refreshInterval: { type: Number, default: 5000 }
    }

    connect() {
        this.startPolling();
    }

    disconnect() {
        this.stopPolling();
    }

    startPolling() {
        this.timer = setInterval(() => {
            this.refresh();
        }, this.refreshIntervalValue);
    }

    stopPolling() {
        if (this.timer) {
            clearInterval(this.timer);
        }
    }

    async refresh() {
        if (!this.urlValue) return;

        try {
            const response = await fetch(this.urlValue, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const html = await response.text();
                this.element.innerHTML = html;
            }
        } catch (error) {
            console.error('Polling error:', error);
        }
    }
}