import { initializeFormHandlers } from './formHandlers.js';
import { initializeUIUpdates } from './uiUpdates.js';

document.addEventListener('DOMContentLoaded', function () {
  const reservationForm = document.querySelector('#reservation_form');

  if (reservationForm) {
    initializeFormHandlers(reservationForm);
    initializeUIUpdates(reservationForm);
  }
});