import './bootstrap';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import flatpickr from 'flatpickr';
import { French } from 'flatpickr/dist/l10n/fr.js';
import ApexCharts from 'apexcharts';

// Livewire fournit et démarre déjà sa propre instance Alpine (via
// @livewireScripts). Importer et démarrer une deuxième instance ici
// entrait en course avec celle de Livewire et pouvait l'empêcher
// d'enregistrer ses composants (Livewire.all() restait vide, champs
// jamais préremplis, wire:click sans effet) — on étend l'instance de
// Livewire via l'évènement alpine:init au lieu d'en créer une seconde.
document.addEventListener('alpine:init', () => {
    Alpine.plugin(collapse);
    Alpine.plugin(focus);
});

window.ApexCharts = ApexCharts;

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
