#!/usr/bin/env node

/**
 * Main entry point for Application TUI
 */

import { config } from './config.js';
import { ApiClient } from './lib/api.js';
import { Storage } from './lib/storage.js';
import { Auth } from './lib/auth.js';
import { CLIHandler } from './lib/cli.js';
import { Dashboard } from './lib/dashboard.js';
import blessed from 'blessed';

// Get command line arguments
const args = process.argv.slice(2);

// Check if running in command mode (has arguments) or interactive mode
const isCommandMode = args.length > 0 && !args.includes('-i') && !args.includes('--interactive');

async function main() {
    // Initialize storage and API client
    const storage = new Storage(config.auth.tokenStoragePath);
    const apiClient = new ApiClient(config.apiUrl);
    
    // Load token if exists
    const token = storage.loadToken();
    if (token) {
        apiClient.setToken(token);
    }

    // Initialize auth
    const auth = new Auth(apiClient, storage);

    if (isCommandMode) {
        // Command mode - execute CLI commands
        const cliHandler = new CLIHandler(apiClient);
        await cliHandler.handle(args);
    } else {
        // Interactive mode - show TUI
        // Ensure authenticated
        if (!(await auth.ensureAuthenticated())) {
            console.error('Authentication required');
            process.exit(1);
        }

        // Create blessed screen
        const screen = blessed.screen({
            smartCSR: true,
            title: 'Application TUI',
        });

        // Initialize dashboard
        const dashboard = new Dashboard(screen, apiClient);
        await dashboard.init();

        // Handle exit
        screen.key(['escape', 'q', 'C-c'], () => {
            return screen.destroy();
        });

        // Render
        screen.render();
    }
}

// Run main function
main().catch((error) => {
    console.error('Fatal error:', error);
    process.exit(1);
});

