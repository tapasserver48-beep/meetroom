// MeetRoom - Main JavaScript Entry Point
import './bootstrap';

// Import Alpine.js for reactive components
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// Global utilities
window.MeetRoom = {
    // Format duration in seconds to HH:MM:SS
    formatDuration(seconds) {
        if (!seconds || seconds < 0) return '00:00:00';
        const hrs = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return [hrs, mins, secs].map(v => v.toString().padStart(2, '0')).join(':');
    },
    
    // Format date for display
    formatDate(date) {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    // Copy to clipboard
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            console.error('Failed to copy:', err);
            return false;
        }
    },
    
    // Show toast notification
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        const colors = {
            success: 'bg-green-600',
            error: 'bg-red-600',
            warning: 'bg-yellow-600',
            info: 'bg-indigo-600'
        };
        
        toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg text-white shadow-lg z-50 animate-fade-in ${colors[type] || colors.info}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('animate-fade-out');
            setTimeout(() => toast.remove(), 200);
        }, 3000);
    },
    
    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // Generate random ID
    generateId() {
        return Math.random().toString(36).substring(2, 15);
    }
};

// Initialize tooltips
document.addEventListener('DOMContentLoaded', () => {
    // Add any global initialization here
    console.log('MeetRoom initialized');
});

// Export for use in other modules
export default window.MeetRoom;