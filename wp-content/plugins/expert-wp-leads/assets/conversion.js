/* Local event only. No network request, cookie, analytics or personal data. */
window.dispatchEvent(new CustomEvent('expertwp:lead-recorded', {detail: {event: 'lead_recorded'}}));
