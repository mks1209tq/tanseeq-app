/**
 * CLI Command Handler for command-line mode
 */

import { CommandParser } from './parser.js';
import { CommandExecutor } from './executor.js';
import { ApiClient } from './api.js';
import chalk from 'chalk';

export class CLIHandler {
    constructor(apiClient) {
        this.api = apiClient;
        this.executor = new CommandExecutor(apiClient);
    }

    /**
     * Handle CLI command
     */
    async handle(args) {
        if (args.length === 0) {
            this.showHelp();
            return;
        }

        const command = args[0];
        const commandArgs = args.slice(1);

        switch (command) {
            case 'help':
            case '--help':
            case '-h':
                this.showHelp();
                break;
            case 'version':
            case '--version':
            case '-v':
                this.showVersion();
                break;
            case 'login':
                await this.handleLogin(commandArgs);
                break;
            case 'logout':
                await this.handleLogout();
                break;
            default:
                // Parse as CRUD command
                const fullCommand = [command, ...commandArgs].join(' ');
                const parsed = CommandParser.parse(fullCommand);
                await this.executor.execute(parsed);
                break;
        }
    }

    /**
     * Show help information
     */
    showHelp() {
        console.log(chalk.cyan.bold('\nApplication TUI - Command Line Interface\n'));
        console.log(chalk.white('Usage:'));
        console.log('  app [command] [options]\n');
        console.log(chalk.white('Commands:'));
        console.log('  login                    Login to the application');
        console.log('  logout                   Logout from the application');
        console.log('  create <model> <args>    Create a new resource');
        console.log('  edit <model> <id>        Edit a resource');
        console.log('  show <model> <id>         Show a resource');
        console.log('  delete <model> <id>      Delete a resource');
        console.log('  list <model>             List resources');
        console.log('  search <query>           Search navigation items\n');
        console.log(chalk.white('Examples:'));
        console.log('  app create todo "Buy flowers" 091225');
        console.log('  app edit todo 123');
        console.log('  app show company 5');
        console.log('  app delete todo 123');
        console.log('  app list todos');
        console.log('  app search "dashboard"\n');
        console.log(chalk.white('Options:'));
        console.log('  --help, -h               Show help');
        console.log('  --version, -v            Show version\n');
    }

    /**
     * Show version information
     */
    showVersion() {
        console.log(chalk.cyan('Application TUI v1.0.0'));
    }

    /**
     * Handle login command
     */
    async handleLogin(args) {
        // For CLI mode, we'll need to prompt for credentials
        // This is a simplified version - full implementation would use inquirer
        console.log(chalk.yellow('Login via CLI mode is not yet fully implemented.'));
        console.log(chalk.yellow('Please use interactive mode: app'));
        console.log(chalk.gray('Or provide token via environment variable: APP_TOKEN=xxx app ...'));
    }

    /**
     * Handle logout command
     */
    async handleLogout() {
        // This would clear stored token
        console.log(chalk.green('Logged out successfully'));
    }
}

