/**
 * Dashboard module for TUI
 */

import blessed from 'blessed';
import chalk from 'chalk';

export class Dashboard {
    constructor(screen, apiClient) {
        this.screen = screen;
        this.api = apiClient;
        this.models = {};
    }

    /**
     * Initialize and show dashboard
     */
    async init() {
        // Load models
        try {
            this.models = await this.api.getModels();
        } catch (error) {
            console.error('Failed to load models:', error.message);
        }

        this.createUI();
        this.setupKeyHandlers();
    }

    /**
     * Create dashboard UI
     */
    createUI() {
        // Create a box for the main container
        const container = blessed.box({
            top: 0,
            left: 0,
            width: '100%',
            height: '100%',
            content: '',
            tags: true,
            style: {
                fg: 'white',
                bg: 'black',
            },
        });

        // Title
        const title = blessed.box({
            top: 0,
            left: 0,
            width: '100%',
            height: 3,
            content: '{center}{bold}Application Dashboard{/bold}{/center}',
            tags: true,
            style: {
                fg: 'cyan',
                bg: 'blue',
            },
        });

        // Menu
        const menu = blessed.list({
            top: 3,
            left: 0,
            width: '30%',
            height: '100%-3',
            label: ' Modules ',
            keys: true,
            vi: true,
            items: this.buildMenuItems(),
            style: {
                selected: {
                    bg: 'blue',
                    fg: 'white',
                },
            },
        });

        // Content area
        const content = blessed.box({
            top: 3,
            left: '30%',
            width: '70%',
            height: '100%-3',
            label: ' Content ',
            content: 'Select a module from the menu',
            tags: true,
            scrollable: true,
            alwaysScroll: true,
            style: {
                fg: 'white',
                bg: 'black',
            },
        });

        // Help bar
        const help = blessed.box({
            bottom: 0,
            left: 0,
            width: '100%',
            height: 1,
            content: '{bold}Q{/bold}: Quit | {bold}↑↓{/bold}: Navigate | {bold}Enter{/bold}: Select | {bold}/{/bold}: Command',
            tags: true,
            style: {
                fg: 'gray',
                bg: 'black',
            },
        });

        container.append(title);
        container.append(menu);
        container.append(content);
        container.append(help);

        this.screen.append(container);
        this.menu = menu;
        this.content = content;
        this.container = container;

        // Focus on menu
        menu.focus();
    }

    /**
     * Build menu items from models
     */
    buildMenuItems() {
        const items = ['Dashboard', '---'];
        
        Object.keys(this.models).forEach(key => {
            const model = this.models[key];
            items.push(model.name || key);
        });

        items.push('---', 'Search', 'Logout');
        return items;
    }

    /**
     * Setup keyboard handlers
     */
    setupKeyHandlers() {
        this.menu.on('select', (item) => {
            this.handleMenuSelect(item.content);
        });

        this.screen.key(['q', 'C-c'], () => {
            return process.exit(0);
        });

        this.screen.key(['/'], () => {
            this.showCommandPalette();
        });
    }

    /**
     * Handle menu selection
     */
    handleMenuSelect(item) {
        if (item === 'Dashboard') {
            this.content.setContent('Welcome to the Application Dashboard');
        } else if (item === 'Logout') {
            // Handle logout
            process.exit(0);
        } else if (item === 'Search') {
            this.showSearch();
        } else {
            // Find model by name
            const modelKey = Object.keys(this.models).find(
                key => (this.models[key].name || key) === item
            );
            if (modelKey) {
                this.showModelList(modelKey);
            }
        }
        this.screen.render();
    }

    /**
     * Show model list
     */
    async showModelList(modelKey) {
        const model = this.models[modelKey];
        const routePrefix = model.routePrefix;

        try {
            this.content.setContent('Loading...');
            this.screen.render();

            const data = await this.api.list(routePrefix);
            const items = Array.isArray(data) ? data : (data.data || []);

            let content = `{bold}${model.name || modelKey}{/bold}\n\n`;
            if (items.length === 0) {
                content += 'No items found.\n';
            } else {
                items.forEach((item, index) => {
                    const id = item.id || index + 1;
                    const title = item.title || item.name || `Item #${id}`;
                    content += `${id}. ${title}\n`;
                });
            }

            this.content.setContent(content);
        } catch (error) {
            this.content.setContent(`Error: ${error.message}`);
        }
        this.screen.render();
    }

    /**
     * Show search
     */
    showSearch() {
        this.content.setContent('Search functionality - Type to search');
        this.screen.render();
    }

    /**
     * Show command palette
     */
    showCommandPalette() {
        // Simplified - would show command input
        this.content.setContent('Command Palette - Type a command (e.g., /create todo "Buy flowers")');
        this.screen.render();
    }

    /**
     * Render the dashboard
     */
    render() {
        this.screen.render();
    }
}

