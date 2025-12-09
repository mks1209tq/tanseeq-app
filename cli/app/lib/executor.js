/**
 * Command Executor for CRUD operations
 */

import chalk from 'chalk';
import { ApiClient } from './api.js';

export class CommandExecutor {
    constructor(apiClient) {
        this.api = apiClient;
    }

    /**
     * Execute a parsed command
     */
    async execute(command) {
        if (!command || command.type === 'error') {
            console.error(chalk.red(command?.message || 'Invalid command'));
            return false;
        }

        try {
            switch (command.type) {
                case 'create':
                    return await this.executeCreate(command);
                case 'edit':
                    return await this.executeEdit(command);
                case 'show':
                    return await this.executeShow(command);
                case 'delete':
                    return await this.executeDelete(command);
                case 'list':
                    return await this.executeList(command);
                case 'search':
                    return await this.executeSearch(command);
                default:
                    console.error(chalk.red(`Unknown command type: ${command.type}`));
                    return false;
            }
        } catch (error) {
            console.error(chalk.red(`Error: ${error.message}`));
            return false;
        }
    }

    /**
     * Execute create command
     */
    async executeCreate(command) {
        const { model, fields } = command;
        const routePrefix = this.getRoutePrefix(model);

        try {
            const result = await this.api.create(routePrefix, fields);
            console.log(chalk.green(`✓ Successfully created ${model}`));
            if (result.id) {
                console.log(chalk.gray(`  ID: ${result.id}`));
            }
            return true;
        } catch (error) {
            this.handleError(error, 'create');
            return false;
        }
    }

    /**
     * Execute edit command
     */
    async executeEdit(command) {
        const { model, identifier } = command;
        const routePrefix = this.getRoutePrefix(model);
        const id = await this.resolveIdentifier(routePrefix, identifier);

        if (!id) {
            console.error(chalk.red(`Could not find ${model} with identifier: ${identifier}`));
            return false;
        }

        try {
            const resource = await this.api.show(routePrefix, id);
            console.log(chalk.yellow(`Edit mode for ${model} #${id}`));
            console.log(chalk.gray('Note: Interactive editing not yet implemented in CLI mode'));
            console.log(chalk.gray('Use the TUI interactive mode for editing'));
            return true;
        } catch (error) {
            this.handleError(error, 'edit');
            return false;
        }
    }

    /**
     * Execute show command
     */
    async executeShow(command) {
        const { model, identifier } = command;
        const routePrefix = this.getRoutePrefix(model);
        const id = await this.resolveIdentifier(routePrefix, identifier);

        if (!id) {
            console.error(chalk.red(`Could not find ${model} with identifier: ${identifier}`));
            return false;
        }

        try {
            const resource = await this.api.show(routePrefix, id);
            this.displayResource(resource);
            return true;
        } catch (error) {
            this.handleError(error, 'show');
            return false;
        }
    }

    /**
     * Execute delete command
     */
    async executeDelete(command) {
        const { model, identifier } = command;
        const routePrefix = this.getRoutePrefix(model);
        const id = await this.resolveIdentifier(routePrefix, identifier);

        if (!id) {
            console.error(chalk.red(`Could not find ${model} with identifier: ${identifier}`));
            return false;
        }

        try {
            await this.api.delete(routePrefix, id);
            console.log(chalk.green(`✓ Successfully deleted ${model} #${id}`));
            return true;
        } catch (error) {
            this.handleError(error, 'delete');
            return false;
        }
    }

    /**
     * Execute list command
     */
    async executeList(command) {
        const { model, options } = command;
        const routePrefix = this.getRoutePrefix(model);

        try {
            const data = await this.api.list(routePrefix, options);
            this.displayList(data, model);
            return true;
        } catch (error) {
            this.handleError(error, 'list');
            return false;
        }
    }

    /**
     * Execute search command
     */
    async executeSearch(command) {
        const { query } = command;

        try {
            // Search navigation items
            const navItems = await this.api.searchNavigation(query);
            
            if (navItems.length === 0) {
                console.log(chalk.yellow('No results found'));
                return true;
            }

            console.log(chalk.cyan(`Found ${navItems.length} navigation item(s):`));
            navItems.forEach((item, index) => {
                console.log(`  ${index + 1}. ${chalk.bold(item.label || item.name)}`);
                if (item.route) {
                    console.log(chalk.gray(`     Route: ${item.route}`));
                }
            });

            return true;
        } catch (error) {
            this.handleError(error, 'search');
            return false;
        }
    }

    /**
     * Resolve identifier (ID or search term) to actual ID
     */
    async resolveIdentifier(routePrefix, identifier) {
        // If it's numeric, assume it's an ID
        if (/^\d+$/.test(identifier)) {
            return parseInt(identifier);
        }

        // Otherwise, search for it
        try {
            const results = await this.api.searchModel(routePrefix, identifier);
            if (results.length > 0) {
                return results[0].id;
            }
        } catch (error) {
            // Search might not be available, try direct ID
            if (/^\d+$/.test(identifier)) {
                return parseInt(identifier);
            }
        }

        return null;
    }

    /**
     * Get route prefix for model (pluralize)
     */
    getRoutePrefix(model) {
        // Simple pluralization
        if (model.endsWith('y')) {
            return model.slice(0, -1) + 'ies';
        }
        if (model.endsWith('s')) {
            return model;
        }
        return model + 's';
    }

    /**
     * Display a resource in formatted output
     */
    displayResource(resource) {
        if (typeof resource === 'object') {
            Object.keys(resource).forEach(key => {
                const value = resource[key];
                if (value !== null && value !== undefined) {
                    console.log(chalk.bold(key) + ': ' + value);
                }
            });
        } else {
            console.log(resource);
        }
    }

    /**
     * Display a list of resources
     */
    displayList(data, model) {
        const items = Array.isArray(data) ? data : (data.data || data.items || []);
        
        if (items.length === 0) {
            console.log(chalk.yellow(`No ${model} items found`));
            return;
        }

        console.log(chalk.cyan(`Found ${items.length} ${model} item(s):`));
        items.forEach((item, index) => {
            const id = item.id || index + 1;
            const title = item.title || item.name || `Item #${id}`;
            console.log(`  ${id}. ${chalk.bold(title)}`);
        });
    }

    /**
     * Handle errors with user-friendly messages
     */
    handleError(error, operation) {
        if (error.response?.data) {
            const data = error.response.data;
            
            // Handle validation errors
            if (data.errors) {
                console.error(chalk.red('Validation errors:'));
                Object.keys(data.errors).forEach(field => {
                    data.errors[field].forEach(msg => {
                        console.error(chalk.red(`  - ${field}: ${msg}`));
                    });
                });
                return;
            }
            
            // Handle error message
            if (data.message) {
                console.error(chalk.red(data.message));
                return;
            }
        }

        // Generic error
        console.error(chalk.red(`Failed to ${operation}: ${error.message}`));
    }
}

