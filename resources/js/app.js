import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import flatpickr from 'flatpickr';
import { French } from 'flatpickr/dist/l10n/fr.js';
import ApexCharts from 'apexcharts';

// Alpine plugins
Alpine.plugin(collapse);
Alpine.plugin(focus);

// Make Alpine global
window.Alpine = Alpine;
window.ApexCharts = ApexCharts;
Alpine.start();

// Flatpickr global init
document.addEventListener('DOMContentLoaded', () => {
    initDatepickers();
});

document.addEventListener('livewire:navigated', () => {
    initDatepickers();
});

document.addEventListener('livewire:update', () => {
    setTimeout(initDatepickers, 50);
});

function initDatepickers() {
    document.querySelectorAll('[data-datepicker]').forEach(el => {
        if (el._flatpickr) return;
        flatpickr(el, {
            locale: French,
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            allowInput: true,
        });
    });
}

// Flash message auto-dismiss
document.addEventListener('livewire:init', () => {
    Livewire.on('notify', ({ type, message }) => {
        window.dispatchEvent(new CustomEvent('notify', { detail: { type, message } }));
    });
});
