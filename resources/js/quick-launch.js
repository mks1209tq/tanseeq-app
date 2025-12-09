/**
 * Quick Launch Functionality
 * Provides Ctrl+Q keyboard shortcut to search and navigate to routes
 */

class QuickLaunch {
    constructor() {
        this.isOpen = false;
        this.selectedIndex = 0;
        this.items = [];
        this.searchTimeout = null;
        this.modal = null;
        this.searchInput = null;
        this.resultsList = null;
        
        // Model registry - will be loaded dynamically from backend
        this.models = {};
        
        // CRUD operations
        this.operations = ['create', 'edit', 'show', 'delete', 'list'];
        
        // Load models from backend
        this.loadModels().then(() => {
            this.init();
        });
    }

    async loadModels() {
        try {
            const response = await fetch('/api/quick-launch/models', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();
                this.models = data.models || {};
            } else {
                console.warn('Failed to load models, using empty registry');
                this.models = {};
            }
        } catch (error) {
            console.error('Error loading models:', error);
            this.models = {};
        }
    }

    init() {
        // Create modal HTML structure
        this.createModal();
        
        // Bind keyboard shortcut
        document.addEventListener('keydown', (e) => this.handleKeyDown(e));
        
        // Close on backdrop click
        if (this.modal) {
            const backdrop = this.modal.querySelector('.quick-launch-backdrop');
            if (backdrop) {
                backdrop.addEventListener('click', () => this.close());
            }
        }
    }

    createModal() {
        // Check if modal already exists
        if (document.getElementById('quick-launch-modal')) {
            this.modal = document.getElementById('quick-launch-modal');
            this.searchInput = this.modal.querySelector('#quick-launch-search');
            this.resultsList = this.modal.querySelector('#quick-launch-results');
            return;
        }

        const modal = document.createElement('div');
        modal.id = 'quick-launch-modal';
        modal.className = 'quick-launch-modal';
        modal.innerHTML = `
            <div class="quick-launch-backdrop"></div>
            <div class="quick-launch-container">
                <div class="quick-launch-header">
                    <input
                        type="text"
                        id="quick-launch-search"
                        class="quick-launch-search"
                        placeholder="Search navigation or type /create, /edit, /show, /delete, /list..."
                        autocomplete="off"
                        aria-label="Search navigation"
                    />
                </div>
                <div id="quick-launch-results" class="quick-launch-results" role="listbox" aria-label="Navigation results"></div>
                <div class="quick-launch-footer">
                    <div class="quick-launch-hints">
                        <span class="quick-launch-hint"><kbd>Ctrl</kbd><kbd>Q</kbd> Open</span>
                        <span class="quick-launch-hint"><kbd>↑</kbd><kbd>↓</kbd> Navigate</span>
                        <span class="quick-launch-hint"><kbd>Enter</kbd> Select</span>
                        <span class="quick-launch-hint"><kbd>Esc</kbd> Close</span>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        this.modal = modal;
        this.searchInput = modal.querySelector('#quick-launch-search');
        this.resultsList = modal.querySelector('#quick-launch-results');

        // Handle search input
        this.searchInput.addEventListener('input', (e) => {
            this.handleSearch(e.target.value);
        });

        // Prevent modal from closing when clicking inside
        const container = modal.querySelector('.quick-launch-container');
        if (container) {
            container.addEventListener('click', (e) => e.stopPropagation());
        }
    }

    handleKeyDown(e) {
        // Check for Ctrl+Q (or Cmd+Q on Mac)
        if ((e.ctrlKey || e.metaKey) && e.key === 'q') {
            e.preventDefault();
            this.toggle();
            return;
        }

        // Handle keys when modal is open
        if (!this.isOpen) {
            return;
        }

        switch (e.key) {
            case 'Escape':
                e.preventDefault();
                this.close();
                break;
            case 'ArrowDown':
                e.preventDefault();
                this.selectNext();
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.selectPrevious();
                break;
            case 'Enter':
                e.preventDefault();
                this.navigateToSelected();
                break;
        }
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        if (!this.modal || !this.searchInput) {
            return;
        }

        this.isOpen = true;
        this.modal.classList.add('open');
        this.searchInput.focus();
        this.selectedIndex = 0;

        // Load initial results
        this.handleSearch('');
    }

    close() {
        if (!this.modal) {
            return;
        }

        this.isOpen = false;
        this.modal.classList.remove('open');
        this.searchInput.value = '';
        this.items = [];
        this.selectedIndex = 0;
        this.renderResults();
    }

    handleSearch(query) {
        // Clear existing timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }

        // Wait for models to load if not ready
        if (Object.keys(this.models).length === 0) {
            this.resultsList.innerHTML = `
                <div class="quick-launch-empty">
                    <p class="text-[#706f6c] dark:text-[#A1A09A]">Loading models...</p>
                </div>
            `;
            // Retry after a short delay
            setTimeout(() => this.handleSearch(query), 200);
            return;
        }

        // Check for command hints first
        const hint = this.getCommandHint(query);
        if (hint) {
            this.showCommandHint(hint);
            return;
        }

        // Check if it's a complete command
        const command = this.parseCommand(query);
        if (command) {
            this.handleCommand(command);
            return;
        }

        // Debounce search
        this.searchTimeout = setTimeout(() => {
            this.performSearch(query);
        }, 150);
    }

    getCommandHint(query) {
        const trimmed = query.trim().toLowerCase();
        
        // Check for any operation + model pattern (e.g., "/create", "/edit todo", "/create todo")
        for (const operation of this.operations) {
            if (trimmed.startsWith(`/${operation}`)) {
                // Check if model is specified
                const modelMatch = trimmed.match(new RegExp(`^/${operation}\\s+(\\w+)`, 'i'));
                if (modelMatch) {
                    const modelKey = modelMatch[1];
                    if (this.models[modelKey]) {
                        return {
                            type: 'command_hint',
                            operation: operation,
                            model: modelKey,
                            query: trimmed,
                        };
                    }
                } else if (operation === 'create' || operation === 'list') {
                    // For create/list, show available models
                    return {
                        type: 'command_hint',
                        operation: operation,
                        model: null,
                        query: trimmed,
                    };
                }
            }
        }

        return null;
    }

    showCommandHint(hint) {
        // Check if it's a complete command first
        const command = this.parseCommand(hint.query);
        if (command && command.complete) {
            this.handleCommand(command);
            return;
        }

        // Show hint based on operation and model
        if (hint.operation === 'create' && hint.model) {
            this.items = [{
                type: 'hint',
                hintType: 'create',
                model: hint.model,
                operation: hint.operation,
            }];
        } else if (hint.operation === 'edit' && hint.model) {
            this.items = [{
                type: 'hint',
                hintType: 'edit',
                model: hint.model,
                operation: hint.operation,
            }];
        } else if (hint.operation === 'create' || hint.operation === 'list') {
            // Show available models
            this.items = Object.keys(this.models).map(modelKey => ({
                type: 'model_option',
                model: modelKey,
                modelName: this.models[modelKey].name,
                operation: hint.operation,
            }));
        } else {
            // Generic hint
            this.items = [{
                type: 'hint',
                hintType: hint.operation,
                model: hint.model,
                operation: hint.operation,
            }];
        }
        
        this.selectedIndex = 0;
        this.renderResults();
    }

    parseCommand(query) {
        const trimmed = query.trim();
        
        // Parse generic CRUD commands
        // Format: /<operation> <model> [params...]
        const commandMatch = trimmed.match(/^\/(\w+)\s+(\w+)(?:\s+(.+))?$/i);
        if (!commandMatch) {
            return null;
        }

        const operation = commandMatch[1].toLowerCase();
        const modelKey = commandMatch[2].toLowerCase();
        const params = commandMatch[3] || '';

        if (!this.operations.includes(operation) || !this.models[modelKey]) {
            return null;
        }

        const model = this.models[modelKey];

        // Parse based on operation
        if (operation === 'create') {
            return this.parseCreateCommand(modelKey, model, params);
        } else if (operation === 'edit' || operation === 'show' || operation === 'delete') {
            return this.parseFindCommand(operation, modelKey, model, params);
        } else if (operation === 'list') {
            return {
                operation: 'list',
                model: modelKey,
                complete: true,
            };
        }

        return null;
    }

    parseCreateCommand(modelKey, model, params) {
        const fields = model.createFields || {};
        const fieldKeys = Object.keys(fields);
        const parsedFields = {};
        let complete = false;

        if (modelKey === 'todo') {
            // Special handling for todo with date
            const todoMatch = params.match(/^(.+?)(?:\s+(\d{6}))?$/);
            if (todoMatch) {
                parsedFields.title = todoMatch[1].trim();
                if (todoMatch[2]) {
                    parsedFields.date = todoMatch[2];
                }
                complete = !!parsedFields.title;
            }
        } else {
            // Generic parsing - first field is required
            const firstField = fieldKeys[0];
            if (firstField && params.trim()) {
                parsedFields[firstField] = params.trim();
                complete = true;
            }
        }

        return {
            operation: 'create',
            model: modelKey,
            fields: parsedFields,
            complete: complete,
        };
    }

    parseFindCommand(operation, modelKey, model, params) {
        // Check if params is an ID (numeric) or search term
        const isNumeric = /^\d+$/.test(params.trim());
        
        if (isNumeric) {
            return {
                operation: operation,
                model: modelKey,
                id: parseInt(params.trim()),
                complete: true,
            };
        } else if (params.trim()) {
            return {
                operation: operation,
                model: modelKey,
                searchTerm: params.trim(),
                complete: false, // Need to search first
            };
        } else {
            return {
                operation: operation,
                model: modelKey,
                complete: false,
            };
        }
    }

    parseDate(dateStr) {
        // Parse MMDDYY format (e.g., "091225" = September 12, 2025)
        if (!dateStr || dateStr.length !== 6) {
            return null;
        }

        const month = parseInt(dateStr.substring(0, 2), 10);
        const day = parseInt(dateStr.substring(2, 4), 10);
        const year = parseInt(dateStr.substring(4, 6), 10);
        
        // Convert YY to YYYY (assuming 20XX for years 00-99)
        const fullYear = 2000 + year;
        
        // Validate date
        if (month < 1 || month > 12 || day < 1 || day > 31) {
            return null;
        }

        // Format as YYYY-MM-DD
        const date = new Date(fullYear, month - 1, day);
        if (date.getFullYear() !== fullYear || date.getMonth() !== month - 1 || date.getDate() !== day) {
            return null; // Invalid date
        }

        return `${fullYear}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    }

    async handleCommand(command) {
        if (command.operation === 'create') {
            this.handleCreateCommand(command);
        } else if (command.operation === 'edit' || command.operation === 'show' || command.operation === 'delete') {
            await this.handleFindCommand(command);
        } else if (command.operation === 'list') {
            this.handleListCommand(command);
        }
    }

    handleCreateCommand(command) {
        const model = this.models[command.model];
        const fields = command.fields || {};
        
        let label = `Create ${model.name}`;
        let subtitle = '';
        
        if (command.model === 'todo') {
            const dateStr = fields.date ? this.parseDate(fields.date) : null;
            const formattedDate = dateStr ? new Date(dateStr).toLocaleDateString() : 'No due date';
            label = `Create todo: "${fields.title || ''}"`;
            subtitle = `Due: ${formattedDate}`;
        } else {
            const firstField = Object.keys(fields)[0];
            if (firstField && fields[firstField]) {
                label = `Create ${model.name}: "${fields[firstField]}"`;
            }
        }
        
        this.items = [{
            type: 'command',
            command: command,
            label: label,
            subtitle: subtitle,
            action: 'create',
        }];
        this.selectedIndex = 0;
        this.renderResults();
    }

    async handleFindCommand(command) {
        const model = this.models[command.model];
        
        if (command.complete && command.id) {
            // Direct ID - show action
            this.items = [{
                type: 'command',
                command: command,
                label: `${command.operation.charAt(0).toUpperCase() + command.operation.slice(1)} ${model.name} #${command.id}`,
                subtitle: `ID: ${command.id}`,
                action: command.operation,
            }];
            this.selectedIndex = 0;
            this.renderResults();
        } else if (command.searchTerm) {
            // Search for items
            await this.searchModelItems(command.model, command.searchTerm, command.operation);
        } else {
            // Show hint for search
            this.items = [{
                type: 'hint',
                hintType: command.operation,
                model: command.model,
                operation: command.operation,
            }];
            this.selectedIndex = 0;
            this.renderResults();
        }
    }

    async searchModelItems(modelKey, searchTerm, operation) {
        try {
            const model = this.models[modelKey];
            const routePrefix = model.routePrefix;
            
            // Search endpoint - we'll need to create this or use existing search
            const url = new URL(`/api/${routePrefix}/search`, window.location.origin);
            url.searchParams.set('q', searchTerm);

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();
                const items = data.items || [];
                
                this.items = items.map(item => ({
                    type: 'command',
                    command: {
                        operation: operation,
                        model: modelKey,
                        id: item.id,
                        complete: true,
                    },
                    label: `${operation.charAt(0).toUpperCase() + operation.slice(1)} ${model.name}: ${item.title || item.name || `#${item.id}`}`,
                    subtitle: `ID: ${item.id}`,
                    action: operation,
                }));
            } else {
                // Fallback: show hint if search fails
                this.items = [{
                    type: 'hint',
                    hintType: operation,
                    model: modelKey,
                    operation: operation,
                }];
            }
            
            this.selectedIndex = 0;
            this.renderResults();
        } catch (error) {
            console.error('Search error:', error);
            this.items = [{
                type: 'hint',
                hintType: operation,
                model: modelKey,
                operation: operation,
            }];
            this.selectedIndex = 0;
            this.renderResults();
        }
    }

    handleListCommand(command) {
        const model = this.models[command.model];
        this.items = [{
            type: 'command',
            command: command,
            label: `List ${model.name}`,
            subtitle: 'View all items',
            action: 'list',
        }];
        this.selectedIndex = 0;
        this.renderResults();
    }

    async performSearch(query) {
        try {
            const url = new URL('/api/navigation/search', window.location.origin);
            url.searchParams.set('q', query);

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Search failed');
            }

            const data = await response.json();
            this.items = data.items || [];
            this.selectedIndex = 0;
            this.renderResults();
        } catch (error) {
            console.error('Quick launch search error:', error);
            this.items = [];
            this.renderResults();
        }
    }

    renderResults() {
        if (!this.resultsList) {
            return;
        }

        if (this.items.length === 0) {
            this.resultsList.innerHTML = `
                <div class="quick-launch-empty">
                    <p class="text-[#706f6c] dark:text-[#A1A09A]">No results found</p>
                </div>
            `;
            return;
        }

        // Check if we should show a hint instead
        if (this.items.length === 1 && this.items[0].type === 'hint') {
            this.renderCommandHint(this.items[0]);
            return;
        }

        const html = this.items.map((item, index) => {
            const isSelected = index === this.selectedIndex;
            
            // Handle model options
            if (item.type === 'model_option') {
                return `
                    <div 
                        class="quick-launch-item ${isSelected ? 'selected' : ''}" 
                        data-index="${index}"
                        role="option"
                        aria-selected="${isSelected}"
                    >
                        <div class="quick-launch-item-content">
                            <span class="quick-launch-item-label">${this.escapeHtml(item.modelName)}</span>
                        </div>
                        <div class="quick-launch-item-route">${this.escapeHtml(item.operation)}</div>
                    </div>
                `;
            }
            
            // Handle command items
            if (item.type === 'command') {
                return `
                    <div 
                        class="quick-launch-item ${isSelected ? 'selected' : ''}" 
                        data-index="${index}"
                        data-action="${item.action}"
                        role="option"
                        aria-selected="${isSelected}"
                    >
                        <div class="quick-launch-item-content">
                            <span class="quick-launch-item-label">${this.escapeHtml(item.label)}</span>
                        </div>
                        <div class="quick-launch-item-route">${this.escapeHtml(item.subtitle || '')}</div>
                    </div>
                `;
            }
            
            // Handle navigation items
            const iconHtml = item.icon 
                ? `<i class="icon-${item.icon} mr-2"></i>` 
                : '';
            const groupHtml = item.group 
                ? `<span class="quick-launch-group">${this.escapeHtml(item.group)}</span>` 
                : '';

            return `
                <div 
                    class="quick-launch-item ${isSelected ? 'selected' : ''}" 
                    data-index="${index}"
                    data-route="${item.route ? this.escapeHtml(item.route) : ''}"
                    role="option"
                    aria-selected="${isSelected}"
                >
                    <div class="quick-launch-item-content">
                        ${iconHtml}
                        <span class="quick-launch-item-label">${this.escapeHtml(item.label)}</span>
                        ${groupHtml}
                    </div>
                    <div class="quick-launch-item-route">${item.route ? this.escapeHtml(item.route) : ''}</div>
                </div>
            `;
        }).join('');

        this.resultsList.innerHTML = html;

        // Add click handlers
        this.resultsList.querySelectorAll('.quick-launch-item').forEach((el) => {
            el.addEventListener('click', () => {
                const index = parseInt(el.dataset.index);
                this.selectedIndex = index;
                this.navigateToSelected();
            });
        });

        // Scroll selected item into view
        const selectedEl = this.resultsList.querySelector('.quick-launch-item.selected');
        if (selectedEl) {
            selectedEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }

    selectNext() {
        if (this.items.length === 0) {
            return;
        }
        this.selectedIndex = (this.selectedIndex + 1) % this.items.length;
        this.renderResults();
    }

    selectPrevious() {
        if (this.items.length === 0) {
            return;
        }
        this.selectedIndex = this.selectedIndex === 0 
            ? this.items.length - 1 
            : this.selectedIndex - 1;
        this.renderResults();
    }

    async navigateToSelected() {
        if (this.items.length === 0 || !this.items[this.selectedIndex]) {
            return;
        }

        const item = this.items[this.selectedIndex];
        
        // Handle model options (when selecting a model for create/list)
        if (item.type === 'model_option') {
            const query = `/${item.operation} ${item.model}`;
            this.searchInput.value = query;
            this.handleSearch(query);
            return;
        }
        
        // Handle commands
        if (item.type === 'command') {
            if (item.action === 'create') {
                await this.executeCreate(item.command);
            } else if (item.action === 'edit') {
                await this.executeEdit(item.command);
            } else if (item.action === 'show') {
                await this.executeShow(item.command);
            } else if (item.action === 'delete') {
                await this.executeDelete(item.command);
            } else if (item.action === 'list') {
                await this.executeList(item.command);
            }
            return;
        }

        // Handle navigation items
        if (item.route) {
            // Get route URL - try to use Laravel route helper if available
            let url;
            if (window.route && typeof window.route === 'function') {
                try {
                    url = window.route(item.route);
                } catch (e) {
                    url = item.path || `/${item.route}`;
                }
            } else {
                url = item.path || `/${item.route}`;
            }

            window.location.href = url;
        }
    }

    async executeCreate(command) {
        try {
            const model = this.models[command.model];
            const routePrefix = model.routePrefix;
            const fields = command.fields || {};
            
            // Validate required fields
            const requiredFields = Object.entries(model.createFields || {})
                .filter(([_, field]) => field.required)
                .map(([key, _]) => key);
            
            const missingFields = requiredFields.filter(field => !fields[field] || !fields[field].trim());
            if (missingFields.length > 0) {
                throw new Error(`Missing required fields: ${missingFields.join(', ')}`);
            }
            
            // Create form data
            const formData = new FormData();
            
            if (command.model === 'todo') {
                formData.append('title', fields.title || '');
                formData.append('priority', 'medium');
                if (fields.date) {
                    const dateStr = this.parseDate(fields.date);
                    if (dateStr) {
                        formData.append('due_date', dateStr);
                    }
                }
            } else if (command.model === 'company') {
                formData.append('name', fields.name || '');
            } else {
                // Generic field handling
                Object.keys(fields).forEach(key => {
                    if (fields[key]) {
                        formData.append(key, fields[key]);
                    }
                });
            }
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            
            const response = await fetch(`/${routePrefix}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: formData,
            });

            if (!response.ok) {
                let errorMessage = `Failed to create ${model.name}`;
                try {
                    const errorData = await response.json();
                    // Handle Laravel validation errors
                    if (errorData.errors) {
                        const errorMessages = Object.values(errorData.errors).flat();
                        errorMessage = errorMessages.join(', ');
                    } else if (errorData.message) {
                        errorMessage = errorData.message;
                    }
                } catch (e) {
                    // If response is not JSON, try to get text
                    const text = await response.text().catch(() => '');
                    if (text) {
                        errorMessage = text.substring(0, 200); // Limit length
                    }
                }
                throw new Error(errorMessage);
            }

            // Show success message and redirect
            this.showMessage(`${model.name} created successfully!`, 'success');
            
            // Close modal and redirect after a short delay
            setTimeout(() => {
                this.close();
                window.location.href = `/${routePrefix}`;
            }, 500);
        } catch (error) {
            console.error('Create error:', error);
            this.showMessage(error.message || `Failed to create ${this.models[command.model].name}`, 'error');
        }
    }

    async executeEdit(command) {
        const model = this.models[command.model];
        const routePrefix = model.routePrefix;
        window.location.href = `/${routePrefix}/${command.id}/edit`;
    }

    async executeShow(command) {
        const model = this.models[command.model];
        const routePrefix = model.routePrefix;
        window.location.href = `/${routePrefix}/${command.id}`;
    }

    async executeDelete(command) {
        if (!confirm(`Are you sure you want to delete this ${this.models[command.model].name}?`)) {
            return;
        }

        try {
            const model = this.models[command.model];
            const routePrefix = model.routePrefix;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            
            const response = await fetch(`/${routePrefix}/${command.id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`Failed to delete ${model.name}`);
            }

            this.showMessage(`${model.name} deleted successfully!`, 'success');
            setTimeout(() => {
                this.close();
                window.location.href = `/${routePrefix}`;
            }, 500);
        } catch (error) {
            console.error('Delete error:', error);
            this.showMessage(error.message || `Failed to delete ${this.models[command.model].name}`, 'error');
        }
    }

    async executeList(command) {
        const model = this.models[command.model];
        const routePrefix = model.routePrefix;
        window.location.href = `/${routePrefix}`;
    }

    showMessage(message, type = 'info') {
        if (!this.resultsList) {
            return;
        }

        const bgColor = type === 'success' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20';
        const textColor = type === 'success' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200';
        const borderColor = type === 'success' ? 'border-green-200 dark:border-green-800' : 'border-red-200 dark:border-red-800';

        this.resultsList.innerHTML = `
            <div class="quick-launch-empty">
                <div class="p-4 ${bgColor} border ${borderColor} rounded-lg ${textColor}">
                    <p class="font-medium">${this.escapeHtml(message)}</p>
                </div>
            </div>
        `;
    }

    renderCommandHint(hintItem) {
        const model = hintItem.model ? this.models[hintItem.model] : null;
        const operation = hintItem.operation || hintItem.hintType;
        
        if (operation === 'create' && model) {
            const fields = model.createFields || {};
            const fieldEntries = Object.entries(fields);
            
            let format = `/create ${hintItem.model}`;
            let example = `/create ${hintItem.model}`;
            
            if (hintItem.model === 'todo') {
                format = `/create todo <span class="quick-launch-hint-required">&lt;title&gt;</span> [MMDDYY]`;
                example = `/create todo buy flowers 091225`;
            } else {
                fieldEntries.forEach(([key, field], index) => {
                    if (field.required) {
                        format += ` <span class="quick-launch-hint-required">&lt;${key}&gt;</span>`;
                        example += ` ${key} value`;
                    } else {
                        format += ` [${key}]`;
                    }
                });
            }
            
            this.resultsList.innerHTML = `
                <div class="quick-launch-hint-container">
                    <div class="quick-launch-hint-header">
                        <span class="quick-launch-hint-title">Create ${model.name} Command</span>
                    </div>
                    <div class="quick-launch-hint-content">
                        <div class="quick-launch-hint-section">
                            <div class="quick-launch-hint-label">Format:</div>
                            <div class="quick-launch-hint-code">${format}</div>
                        </div>
                        <div class="quick-launch-hint-section">
                            <div class="quick-launch-hint-label">Fields:</div>
                            <div class="quick-launch-hint-fields">
                                ${fieldEntries.map(([key, field]) => `
                                    <div class="quick-launch-hint-field">
                                        <span class="quick-launch-hint-field-name">${key}</span>
                                        <span class="quick-launch-hint-field-badge ${field.required ? 'required' : 'optional'}">${field.required ? 'Required' : 'Optional'}</span>
                                        <span class="quick-launch-hint-field-desc">- ${field.description || field.type || ''}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        <div class="quick-launch-hint-section">
                            <div class="quick-launch-hint-label">Separator:</div>
                            <div class="quick-launch-hint-code">Space (<kbd> </kbd>)</div>
                        </div>
                        <div class="quick-launch-hint-section">
                            <div class="quick-launch-hint-label">Example:</div>
                            <div class="quick-launch-hint-code">${example}</div>
                        </div>
                    </div>
                </div>
            `;
        } else if (operation === 'edit' && model) {
            this.resultsList.innerHTML = `
                <div class="quick-launch-hint-container">
                    <div class="quick-launch-hint-header">
                        <span class="quick-launch-hint-title">Edit ${model.name} Command</span>
                    </div>
                    <div class="quick-launch-hint-content">
                        <div class="quick-launch-hint-section">
                            <div class="quick-launch-hint-label">Format:</div>
                            <div class="quick-launch-hint-code">/edit ${hintItem.model} <span class="quick-launch-hint-required">&lt;id or search term&gt;</span></div>
                        </div>
                        <div class="quick-launch-hint-section">
                            <div class="quick-launch-hint-label">Examples:</div>
                            <div class="quick-launch-hint-code">/edit ${hintItem.model} 123</div>
                            <div class="quick-launch-hint-code">/edit ${hintItem.model} buy flowers</div>
                        </div>
                    </div>
                </div>
            `;
        } else if (operation === 'create' || operation === 'list') {
            // Show available models
            const availableModels = Object.entries(this.models).map(([key, model]) => 
                `${key} (${model.name})`
            ).join(', ');
            
            this.resultsList.innerHTML = `
                <div class="quick-launch-hint-container">
                    <div class="quick-launch-hint-header">
                        <span class="quick-launch-hint-title">${operation.charAt(0).toUpperCase() + operation.slice(1)} Command</span>
                    </div>
                    <div class="quick-launch-hint-content">
                        <div class="quick-launch-hint-section">
                            <div class="quick-launch-hint-label">Available Models:</div>
                            <div class="quick-launch-hint-code">${availableModels}</div>
                        </div>
                        <div class="quick-launch-hint-section">
                            <div class="quick-launch-hint-label">Format:</div>
                            <div class="quick-launch-hint-code">/${operation} <span class="quick-launch-hint-required">&lt;model&gt;</span></div>
                        </div>
                    </div>
                </div>
            `;
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.quickLaunch = new QuickLaunch();
    });
} else {
    window.quickLaunch = new QuickLaunch();
}

