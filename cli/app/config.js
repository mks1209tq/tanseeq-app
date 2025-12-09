/**
 * Configuration for TUI Application
 */

export const config = {
    // API endpoint - defaults to Herd URL, can be overridden via environment
    // For Laravel Herd: http://auth.test (or your project's Herd URL)
    // For artisan serve: http://localhost:8000
    apiUrl: process.env.API_URL || 'http://auth.test',
    
    // Authentication
    auth: {
        tokenStoragePath: process.env.HOME 
            ? `${process.env.HOME}/.app-tui/token.json`
            : './.app-tui/token.json',
    },
    
    // UI preferences
    ui: {
        theme: 'default',
        colors: true,
    },
};

