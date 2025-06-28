import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

// Set CSRF token from meta tag
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
}

// Add interceptor to handle CSRF token refresh
window.axios.interceptors.request.use(config => {
    const token = document.head.querySelector('meta[name="csrf-token"]');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token.getAttribute('content');
    }
    return config;
});

// Handle 419 errors (CSRF token mismatch)
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 419) {
            // Refresh the page to get a new CSRF token
            console.error('CSRF token mismatch, refreshing page...');
            window.location.reload();
        }
        return Promise.reject(error);
    }
);