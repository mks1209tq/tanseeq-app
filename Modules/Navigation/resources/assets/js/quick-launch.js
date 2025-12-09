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
        
        this.init();
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
                        placeholder="Search navigation..."
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

        // Debounce search
        this.searchTimeout = setTimeout(() => {
            this.performSearch(query);
        }, 150);
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

        const html = this.items.map((item, index) => {
            const isSelected = index === this.selectedIndex;
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
                    data-route="${this.escapeHtml(item.route)}"
                    role="option"
                    aria-selected="${isSelected}"
                >
                    <div class="quick-launch-item-content">
                        ${iconHtml}
                        <span class="quick-launch-item-label">${this.escapeHtml(item.label)}</span>
                        ${groupHtml}
                    </div>
                    <div class="quick-launch-item-route">${this.escapeHtml(item.route)}</div>
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

    navigateToSelected() {
        if (this.items.length === 0 || !this.items[this.selectedIndex]) {
            return;
        }

        const item = this.items[this.selectedIndex];
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

