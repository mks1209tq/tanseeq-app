/**
 * Storage utilities for token and preferences
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export class Storage {
    constructor(storagePath) {
        this.storagePath = storagePath;
        this.ensureStorageDir();
    }

    ensureStorageDir() {
        const dir = path.dirname(this.storagePath);
        if (!fs.existsSync(dir)) {
            fs.mkdirSync(dir, { recursive: true });
        }
    }

    /**
     * Save token to storage
     */
    saveToken(token) {
        try {
            const data = {
                token,
                savedAt: new Date().toISOString(),
            };
            fs.writeFileSync(this.storagePath, JSON.stringify(data, null, 2));
            return true;
        } catch (error) {
            console.error('Failed to save token:', error);
            return false;
        }
    }

    /**
     * Load token from storage
     */
    loadToken() {
        try {
            if (!fs.existsSync(this.storagePath)) {
                return null;
            }
            const data = JSON.parse(fs.readFileSync(this.storagePath, 'utf8'));
            return data.token || null;
        } catch (error) {
            return null;
        }
    }

    /**
     * Remove token from storage
     */
    removeToken() {
        try {
            if (fs.existsSync(this.storagePath)) {
                fs.unlinkSync(this.storagePath);
            }
            return true;
        } catch (error) {
            return false;
        }
    }

    /**
     * Check if token exists
     */
    hasToken() {
        return fs.existsSync(this.storagePath);
    }
}

