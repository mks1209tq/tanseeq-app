# Application TUI

Terminal User Interface (TUI) for the Laravel application.

## Installation

Install dependencies:

```bash
cd cli/app
npm install
```

## Usage

### Interactive Mode

Run the TUI in interactive mode:

```bash
npm start
# or
node index.js
```

This will:
1. Show a login screen if not authenticated
2. Display the main dashboard with modules
3. Allow navigation and interaction with the application

### Command Mode

Run commands directly without the UI:

```bash
npm start -- create todo "Buy flowers" 091225
npm start -- edit todo 123
npm start -- show company 5
npm start -- delete todo 123
npm start -- list todos
npm start -- search "dashboard"
```

## Configuration

Set the API URL via environment variable:

```bash
# For Laravel Herd (default)
export API_URL=http://auth.test
npm start

# For Laravel artisan serve
export API_URL=http://localhost:8000
npm start
```

Or edit `config.js` to change the default API URL.

**Note:** Make sure your Laravel server is running before starting the TUI:
- For Herd: The site should be automatically available at `http://auth.test` (or your project's Herd URL)
- For artisan serve: Run `php artisan serve` in a separate terminal

## Authentication

The TUI uses Sanctum API tokens for authentication. Tokens are stored in `~/.app-tui/token.json`.

## Features

- **Interactive Dashboard**: Full-screen terminal UI with menu navigation
- **Command Mode**: Execute CRUD operations via command line
- **Authentication**: Secure token-based authentication
- **Authorization**: Respects user permissions (only shows authorized modules)
- **Dynamic Models**: Automatically discovers available models from the backend

## Keyboard Shortcuts (Interactive Mode)

- `Q` or `Ctrl+C`: Quit
- `↑↓`: Navigate menu
- `Enter`: Select
- `/`: Open command palette
- `Esc`: Go back

## Commands

- `create <model> <args>` - Create a new resource
- `edit <model> <id>` - Edit a resource
- `show <model> <id>` - Show a resource
- `delete <model> <id>` - Delete a resource
- `list <model>` - List resources
- `search <query>` - Search navigation items

## Examples

```bash
# Create a todo
app create todo "Buy flowers" 091225

# Edit a todo
app edit todo 123

# Show a company
app show company 5

# List all todos
app list todos

# Search
app search "dashboard"
```

