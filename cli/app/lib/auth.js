/**
 * Authentication module for TUI
 */

import inquirer from 'inquirer';
import chalk from 'chalk';
import { ApiClient } from './api.js';
import { Storage } from './storage.js';

export class Auth {
    constructor(apiClient, storage) {
        this.api = apiClient;
        this.storage = storage;
    }

    /**
     * Check if user is authenticated
     */
    async isAuthenticated() {
        const token = this.storage.loadToken();
        if (token) {
            this.api.setToken(token);
            // Optionally verify token is still valid
            return true;
        }
        return false;
    }

    /**
     * Show login screen and authenticate
     */
    async login() {
        console.clear();
        console.log(chalk.cyan.bold('\n=== Application Login ===\n'));

        const maxAttempts = 3;
        let attempts = 0;

        while (attempts < maxAttempts) {
            attempts++;
            
            if (attempts > 1) {
                console.log(chalk.yellow(`\nAttempt ${attempts} of ${maxAttempts}\n`));
            }

            const answers = await inquirer.prompt([
                {
                    type: 'input',
                    name: 'email',
                    message: 'Email:',
                    validate: (input) => {
                        if (!input) {
                            return 'Email is required';
                        }
                        if (!input.includes('@')) {
                            return 'Please enter a valid email address';
                        }
                        return true;
                    },
                },
                {
                    type: 'password',
                    name: 'password',
                    message: 'Password:',
                    mask: '*',
                    validate: (input) => {
                        if (!input) {
                            return 'Password is required';
                        }
                        return true;
                    },
                },
            ]);

            try {
                console.log(chalk.yellow('\nLogging in...'));
                const token = await this.api.login(answers.email, answers.password);
                
                // Save token
                this.storage.saveToken(token);
                this.api.setToken(token);
                
                console.log(chalk.green('\n✓ Login successful!\n'));
                return true;
            } catch (error) {
                const errorMessage = this.formatError(error);
                console.log(chalk.red(`\n✗ Login failed: ${errorMessage}\n`));
                
                // After 3 attempts, exit without asking
                if (attempts >= maxAttempts) {
                    console.log(chalk.red(`\nMaximum login attempts (${maxAttempts}) reached. Exiting...\n`));
                    return false;
                }
                
                // Ask if user wants to retry (default yes)
                const { retry } = await inquirer.prompt([
                    {
                        type: 'confirm',
                        name: 'retry',
                        message: 'Would you like to try again?',
                        default: true,
                    },
                ]);
                
                if (!retry) {
                    console.log(chalk.yellow('\nLogin cancelled by user.\n'));
                    return false;
                }
            }
        }

        return false;
    }

    /**
     * Format error message to be more explicit
     */
    formatError(error) {
        // Check for specific error types
        if (error.response) {
            const status = error.response.status;
            const data = error.response.data;

            // Debug: Log full error details in development
            if (process.env.DEBUG) {
                console.log(chalk.gray('\n[DEBUG] Full error details:'));
                console.log(chalk.gray(`Status: ${status}`));
                console.log(chalk.gray(`Data: ${JSON.stringify(data, null, 2)}`));
            }

            if (status === 401) {
                const message = data?.message || 'Invalid credentials';
                return `Authentication failed (401): ${message}. Please check your email and password.`;
            }

            if (status === 403) {
                if (data?.message?.includes('email') || data?.message?.includes('verify')) {
                    return 'Email verification required. Please verify your email address before logging in.';
                }
                return `Access forbidden (403): ${data?.message || 'You do not have permission to access this application.'}`;
            }

            if (status === 404) {
                return `API endpoint not found (404). Please check the API URL configuration. Current URL: ${this.api.baseURL}/api/login`;
            }

            if (status === 422) {
                const errors = data?.errors;
                if (errors) {
                    const errorMessages = Object.values(errors).flat();
                    return `Validation errors (422): ${errorMessages.join(', ')}`;
                }
                return `Validation error (422): ${data?.message || 'Invalid input data.'}`;
            }

            if (status >= 500) {
                return `Server error (${status}): ${data?.message || 'Please try again later or contact support.'}`;
            }

            // Return server message if available
            if (data?.message) {
                return `${data.message} (Status: ${status})`;
            }

            return `Server returned status ${status}. Response: ${JSON.stringify(data)}`;
        }

        // Network errors
        if (error.code === 'ECONNREFUSED') {
            const apiUrl = this.api.baseURL || 'http://localhost:8000';
            return `Connection refused. Please check that the server is running.\n` +
                   `  - If using Laravel Herd, ensure the site is running at: ${apiUrl}\n` +
                   `  - If using artisan serve, run: php artisan serve\n` +
                   `  - Or set API_URL environment variable: API_URL=http://your-url node cli/app/index.js`;
        }

        if (error.code === 'ENOTFOUND') {
            return 'Host not found. Please check the API URL configuration.';
        }

        if (error.code === 'ETIMEDOUT') {
            return 'Connection timeout. Please check your network connection.';
        }

        // Generic error
        return error.message || 'An unknown error occurred. Please try again.';
    }

    /**
     * Logout user
     */
    async logout() {
        this.storage.removeToken();
        this.api.setToken(null);
        console.log(chalk.green('Logged out successfully'));
    }

    /**
     * Ensure user is authenticated, prompt if not
     */
    async ensureAuthenticated() {
        if (await this.isAuthenticated()) {
            return true;
        }

        return await this.login();
    }
}

