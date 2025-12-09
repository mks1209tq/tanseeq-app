/**
 * Command Parser for CRUD operations
 */

export class CommandParser {
    /**
     * Parse a command string into structured command object
     */
    static parse(input) {
        const trimmed = input.trim();
        
        // Remove leading slash if present
        const command = trimmed.startsWith('/') ? trimmed.slice(1) : trimmed;
        
        if (!command) {
            return null;
        }

        const parts = command.split(/\s+/);
        const operation = parts[0]?.toLowerCase();
        const model = parts[1]?.toLowerCase();
        
        // Extract remaining arguments
        const args = parts.slice(2);

        // Handle operations
        switch (operation) {
            case 'create':
                return this.parseCreate(model, args);
            case 'edit':
            case 'update':
                return this.parseEdit(model, args);
            case 'show':
            case 'view':
                return this.parseShow(model, args);
            case 'delete':
            case 'remove':
                return this.parseDelete(model, args);
            case 'list':
                return this.parseList(model, args);
            case 'search':
                return this.parseSearch(args);
            default:
                // If no operation, treat as search
                return this.parseSearch([command]);
        }
    }

    /**
     * Parse create command: create <model> <field1> <field2> ...
     */
    static parseCreate(model, args) {
        if (!model) {
            return { type: 'error', message: 'Model name required for create command' };
        }

        // Parse fields from arguments
        // Format: create todo "Buy flowers" 091225
        const fields = {};
        let currentField = null;
        let inQuotes = false;
        let quoteChar = null;
        let currentValue = '';

        for (const arg of args) {
            // Check if argument starts a quoted string
            if ((arg.startsWith('"') || arg.startsWith("'")) && !inQuotes) {
                inQuotes = true;
                quoteChar = arg[0];
                currentValue = arg.slice(1);
                if (arg.endsWith(quoteChar) && arg.length > 1) {
                    // Single word quoted string
                    inQuotes = false;
                    fields[currentField || 'title'] = currentValue.slice(0, -1);
                    currentValue = '';
                }
            } else if (inQuotes) {
                // Continue quoted string
                currentValue += ' ' + arg;
                if (arg.endsWith(quoteChar)) {
                    inQuotes = false;
                    fields[currentField || 'title'] = currentValue.slice(0, -1);
                    currentValue = '';
                }
            } else {
                // Regular argument
                // Try to detect field names (contains =) or positional values
                if (arg.includes('=')) {
                    const [key, value] = arg.split('=', 2);
                    fields[key] = value;
                } else {
                    // Positional - assign to common field names
                    if (!currentField) {
                        currentField = 'title';
                        fields[currentField] = arg;
                    } else if (!fields.date && this.looksLikeDate(arg)) {
                        fields.date = arg;
                    } else {
                        // Append to current field or create new
                        fields[currentField] = (fields[currentField] || '') + ' ' + arg;
                    }
                }
            }
        }

        // Handle any remaining quoted value
        if (inQuotes && currentValue) {
            fields[currentField || 'title'] = currentValue;
        }

        return {
            type: 'create',
            model,
            fields,
        };
    }

    /**
     * Parse edit command: edit <model> <id|search>
     */
    static parseEdit(model, args) {
        if (!model) {
            return { type: 'error', message: 'Model name required for edit command' };
        }

        const identifier = args[0];
        if (!identifier) {
            return { type: 'error', message: 'ID or search term required for edit command' };
        }

        return {
            type: 'edit',
            model,
            identifier,
        };
    }

    /**
     * Parse show command: show <model> <id|search>
     */
    static parseShow(model, args) {
        if (!model) {
            return { type: 'error', message: 'Model name required for show command' };
        }

        const identifier = args[0];
        if (!identifier) {
            return { type: 'error', message: 'ID or search term required for show command' };
        }

        return {
            type: 'show',
            model,
            identifier,
        };
    }

    /**
     * Parse delete command: delete <model> <id|search>
     */
    static parseDelete(model, args) {
        if (!model) {
            return { type: 'error', message: 'Model name required for delete command' };
        }

        const identifier = args[0];
        if (!identifier) {
            return { type: 'error', message: 'ID or search term required for delete command' };
        }

        return {
            type: 'delete',
            model,
            identifier,
        };
    }

    /**
     * Parse list command: list <model> [options]
     */
    static parseList(model, args) {
        if (!model) {
            return { type: 'error', message: 'Model name required for list command' };
        }

        const options = {};
        for (const arg of args) {
            if (arg.startsWith('--')) {
                const [key, value] = arg.slice(2).split('=', 2);
                options[key] = value || true;
            }
        }

        return {
            type: 'list',
            model,
            options,
        };
    }

    /**
     * Parse search command: search <query>
     */
    static parseSearch(args) {
        const query = args.join(' ');
        if (!query) {
            return { type: 'error', message: 'Search query required' };
        }

        return {
            type: 'search',
            query,
        };
    }

    /**
     * Check if string looks like a date
     */
    static looksLikeDate(str) {
        // Check for MMDDYY format or other common date patterns
        return /^\d{6}$/.test(str) || // MMDDYY
               /^\d{4}-\d{2}-\d{2}$/.test(str) || // YYYY-MM-DD
               /^\d{2}\/\d{2}\/\d{4}$/.test(str); // MM/DD/YYYY
    }
}

