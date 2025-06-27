import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

// Set CSRF token from meta tag
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
}

// For Sanctum CSRF protection, ensure we get the CSRF cookie first
window.axios.get('/sanctum/csrf-cookie').catch(() => {
    // If this fails, we'll fall back to the meta tag token
});