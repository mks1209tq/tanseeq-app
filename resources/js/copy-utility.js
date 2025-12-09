/**
 * Universal Copy Utility
 * 
 * Usage:
 * 1. Add data-copyable="value" to any element to make it copyable
 * 2. Use window.copyToClipboard(text) to copy any text
 * 3. Click the universal copy button in header to copy selected text or nearest copyable element
 */

window.copyToClipboard = function(text, options, callback) {
    if (!text) {
        console.warn('No text provided to copy');
        return Promise.reject(new Error('No text provided'));
    }

    // Handle options parameter (can be callback if options not provided)
    if (typeof options === 'function') {
        callback = options;
        options = {};
    }
    options = options || {};

    // Copy to system clipboard
    const copyPromise = (navigator.clipboard && navigator.clipboard.writeText) 
        ? navigator.clipboard.writeText(text)
        : fallbackCopyPromise(text);

    return copyPromise.then(() => {
        // Save to clipboard database if autoSave is enabled (default: true)
        if (options.autoSave !== false) {
            saveToClipboard(text, options.title, options.type).catch(err => {
                console.warn('Failed to save to clipboard database:', err);
            });
        }

        if (callback) callback(true);
        return true;
    }).catch(err => {
        console.error('Copy failed:', err);
        if (callback) callback(false);
        return Promise.reject(err);
    });
};

function fallbackCopyPromise(text) {
    return new Promise((resolve, reject) => {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            const successful = document.execCommand('copy');
            document.body.removeChild(textArea);
            if (successful) {
                resolve();
            } else {
                reject(new Error('Copy command failed'));
            }
        } catch (err) {
            document.body.removeChild(textArea);
            reject(err);
        }
    });
}

function saveToClipboard(content, title, type) {
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (!csrfToken) {
        return Promise.reject(new Error('CSRF token not found'));
    }

    return window.axios.post('/clipboard-items/quick-save', {
        content: content,
        title: title || null,
        type: type || null,
    }, {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        }
    }).then(response => {
        return response.data;
    }).catch(error => {
        // Don't throw - just log, copying still succeeded
        if (error.response?.status === 404) {
            console.warn('Failed to save to clipboard: Tenant not found. Make sure you are logged in and tenant is configured.');
        } else {
            console.warn('Failed to save to clipboard:', error.response?.data || error.message);
        }
        throw error;
    });
}

// Legacy fallback function (kept for backward compatibility)
function fallbackCopy(text, callback) {
    fallbackCopyPromise(text).then(() => {
        if (callback) callback(true);
    }).catch(() => {
        if (callback) callback(false);
    });
}

// Toggle state for clipboard sidebar
let clipboardSidebarVisible = false;

// Track the last active input/textarea field
let lastActiveInput = null;

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Track last active input field
    initializeInputTracking();
    
    // Create clipboard sidebar if it doesn't exist
    createClipboardSidebar();
    
    // Handle universal copy button - toggle clipboard sidebar
    const universalCopyBtn = document.getElementById('universal-copy-btn');
    if (universalCopyBtn) {
        universalCopyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Toggle clipboard sidebar
            toggleClipboardSidebar();
        });
    }

    // Handle elements with data-copyable attribute
    document.querySelectorAll('[data-copyable]').forEach(element => {
        element.style.cursor = 'pointer';
        element.addEventListener('click', function(e) {
            // Only copy if clicking directly on the element (not on child elements with their own handlers)
            if (e.target === element || !e.target.closest('[data-copyable]')) {
                const textToCopy = element.getAttribute('data-copyable') || 
                                 element.textContent.trim() ||
                                 element.href ||
                                 element.value;
                
                if (textToCopy) {
                    e.preventDefault();
                    e.stopPropagation();
                    copyToClipboard(textToCopy, { autoSave: true }, function(success) {
                        showCopyFeedback(element, success);
                    });
                }
            }
        });
    });

    // Handle copy buttons with data-copy attribute
    document.querySelectorAll('[data-copy]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const textToCopy = button.getAttribute('data-copy') || 
                             button.textContent.trim();
            
            if (textToCopy) {
                copyToClipboard(textToCopy, { autoSave: true }, function(success) {
                    showCopyFeedback(button, success);
                });
            }
        });
    });
});

function findNearestCopyable() {
    // Try to find copyable element near the cursor or selection
    const selection = window.getSelection();
    if (selection.rangeCount > 0) {
        const range = selection.getRangeAt(0);
        const container = range.commonAncestorContainer;
        
        // Walk up the DOM tree to find copyable element
        let element = container.nodeType === Node.TEXT_NODE 
            ? container.parentElement 
            : container;
        
        while (element && element !== document.body) {
            if (element.hasAttribute('data-copyable')) {
                return element;
            }
            element = element.parentElement;
        }
    }
    
    return null;
}

function initializeInputTracking() {
    // Use event delegation to handle dynamically added inputs
    document.addEventListener('focus', function(e) {
        const target = e.target;
        // Track input, textarea, and contenteditable elements
        if (target.tagName === 'INPUT' || 
            target.tagName === 'TEXTAREA' || 
            target.isContentEditable) {
            // Only track if the element is visible and enabled
            if (target.offsetParent !== null && !target.disabled && !target.readOnly) {
                lastActiveInput = target;
            }
        }
    }, true); // Use capture phase to catch all focus events
    
    document.addEventListener('blur', function(e) {
        // Don't clear lastActiveInput on blur - keep it until a new one is focused
        // This allows pasting even after the input loses focus
    }, true);
}

function pasteToLastActiveInput(text) {
    if (!text || !lastActiveInput) {
        return false;
    }
    
    // Check if the element still exists in the DOM
    if (!document.body.contains(lastActiveInput)) {
        lastActiveInput = null;
        return false;
    }
    
    // Check if element is still visible and enabled
    if (lastActiveInput.offsetParent === null || 
        lastActiveInput.disabled || 
        lastActiveInput.readOnly) {
        return false;
    }
    
    try {
        // Focus the input first
        lastActiveInput.focus();
        
        // Handle different input types
        if (lastActiveInput.isContentEditable) {
            // Handle contenteditable divs
            const selection = window.getSelection();
            if (selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                range.deleteContents();
                const textNode = document.createTextNode(text);
                range.insertNode(textNode);
                range.setStartAfter(textNode);
                range.collapse(true);
                selection.removeAllRanges();
                selection.addRange(range);
            } else {
                // No selection, append at end
                lastActiveInput.textContent += text;
            }
        } else if (lastActiveInput.tagName === 'INPUT' || lastActiveInput.tagName === 'TEXTAREA') {
            // Handle regular inputs and textareas
            const start = lastActiveInput.selectionStart || 0;
            const end = lastActiveInput.selectionEnd || 0;
            const value = lastActiveInput.value || '';
            
            // Insert text at cursor position or replace selection
            const newValue = value.substring(0, start) + text + value.substring(end);
            lastActiveInput.value = newValue;
            
            // Set cursor position after inserted text
            const newPosition = start + text.length;
            lastActiveInput.setSelectionRange(newPosition, newPosition);
            
            // Trigger input event for frameworks that rely on it
            const inputEvent = new Event('input', { bubbles: true });
            lastActiveInput.dispatchEvent(inputEvent);
        }
        
        return true;
    } catch (error) {
        console.warn('Failed to paste to input:', error);
        return false;
    }
}

function createClipboardSidebar() {
    // Check if sidebar already exists
    if (document.getElementById('clipboard-sidebar')) {
        return;
    }

    // Create sidebar
    const sidebar = document.createElement('div');
    sidebar.id = 'clipboard-sidebar';
    sidebar.className = 'clipboard-sidebar';
    sidebar.innerHTML = `
        <div class="clipboard-sidebar-header">
            <h2 class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Clipboard</h2>
            <button id="clipboard-sidebar-close" class="p-1 hover:bg-[#e3e3e0] dark:hover:bg-[#3E3E3A] rounded transition-colors" title="Close clipboard">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="clipboard-sidebar-content" class="clipboard-sidebar-content">
            <div class="text-center py-8 text-[#706f6c] dark:text-[#A1A09A]">
                <div class="animate-spin inline-block w-6 h-6 border-2 border-current border-t-transparent rounded-full"></div>
                <p class="mt-2">Loading clipboard items...</p>
            </div>
        </div>
    `;
    
    document.body.appendChild(sidebar);

    // Close button handler
    document.getElementById('clipboard-sidebar-close').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleClipboardSidebar();
    });
}

function toggleClipboardSidebar() {
    clipboardSidebarVisible = !clipboardSidebarVisible;
    const sidebar = document.getElementById('clipboard-sidebar');
    const universalCopyBtn = document.getElementById('universal-copy-btn');

    if (!sidebar) {
        createClipboardSidebar();
        // Wait for sidebar to be created, then toggle
        setTimeout(() => toggleClipboardSidebar(), 100);
        return;
    }

    if (clipboardSidebarVisible) {
        sidebar.classList.add('visible');
        document.body.classList.add('clipboard-sidebar-open');
        
        // Load clipboard items
        loadClipboardItems();
        
        if (universalCopyBtn) {
            universalCopyBtn.setAttribute('title', 'Hide clipboard');
        }
    } else {
        sidebar.classList.remove('visible');
        document.body.classList.remove('clipboard-sidebar-open');
        
        if (universalCopyBtn) {
            universalCopyBtn.setAttribute('title', 'Show clipboard');
        }
    }
}

function loadClipboardItems() {
    const content = document.getElementById('clipboard-sidebar-content');
    if (!content) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (!csrfToken) {
        content.innerHTML = '<div class="text-center py-8 text-red-600 dark:text-red-400">CSRF token not found</div>';
        return;
    }

    // Show loading state
    content.innerHTML = `
        <div class="text-center py-8 text-[#706f6c] dark:text-[#A1A09A]">
            <div class="animate-spin inline-block w-6 h-6 border-2 border-current border-t-transparent rounded-full"></div>
            <p class="mt-2">Loading clipboard items...</p>
        </div>
    `;

    window.axios.get('/clipboard-items/api/recent', {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        }
    }).then(response => {
        const items = response.data;
        
        if (items.length === 0) {
            content.innerHTML = `
                <div class="text-center py-8 text-[#706f6c] dark:text-[#A1A09A]">
                    <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    <p>No clipboard items yet</p>
                    <p class="text-sm mt-2">Copy something to add it here</p>
                </div>
            `;
            return;
        }

        content.innerHTML = `
            <div class="clipboard-items-list space-y-2">
                ${items.map(item => `
                    <div class="clipboard-item p-3 bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg hover:bg-[#f5f5f3] dark:hover:bg-[#1a1a18] transition-colors cursor-pointer" 
                         data-clipboard-content="${escapeHtml(item.content)}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0" onclick="copyFromClipboardItem(this.closest('.clipboard-item'))">
                                ${item.title ? `<h3 class="font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1 truncate">${escapeHtml(item.title)}</h3>` : ''}
                                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] break-words line-clamp-2">${escapeHtml(item.content)}</p>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-[#3E3E3A] text-[#706f6c] dark:text-[#A1A09A] rounded">${escapeHtml(item.type)}</span>
                                    <span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">${escapeHtml(item.created_at)}</span>
                                </div>
                            </div>
                            <button class="flex-shrink-0 p-1 hover:bg-[#e3e3e0] dark:hover:bg-[#3E3E3A] rounded transition-colors" 
                                    onclick="event.stopPropagation(); copyFromClipboardItem(this.closest('.clipboard-item'))">
                                <svg class="w-4 h-4 text-[#706f6c] dark:text-[#A1A09A]" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }).catch(error => {
        console.error('Failed to load clipboard items:', error);
        content.innerHTML = `
            <div class="text-center py-8 text-red-600 dark:text-red-400">
                <p>Failed to load clipboard items</p>
                <p class="text-sm mt-2">${error.response?.data?.message || error.message}</p>
            </div>
        `;
    });
}

window.copyFromClipboardItem = function(element) {
    const content = element.getAttribute('data-clipboard-content');
    if (content) {
        copyToClipboard(content, { autoSave: false }, function(success) {
            if (success) {
                // Paste to last active input field
                pasteToLastActiveInput(content);
                
                // Visual feedback
                element.classList.add('copy-success');
                setTimeout(() => {
                    element.classList.remove('copy-success');
                }, 1000);
            }
        });
    }
};

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showCopyFeedback(element, success, message) {
    if (!element) return;

    const originalHTML = element.innerHTML;
    const originalTitle = element.getAttribute('title') || '';
    
    if (success) {
        element.innerHTML = `
            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        `;
        element.setAttribute('title', 'Copied!');
        
        // Add success class if it exists
        element.classList.add('copy-success');
        
        setTimeout(function() {
            element.innerHTML = originalHTML;
            element.setAttribute('title', originalTitle);
            element.classList.remove('copy-success');
        }, 2000);
    } else {
        element.setAttribute('title', message || 'Failed to copy');
        element.classList.add('copy-error');
        
        setTimeout(function() {
            element.setAttribute('title', originalTitle);
            element.classList.remove('copy-error');
        }, 2000);
    }
}

