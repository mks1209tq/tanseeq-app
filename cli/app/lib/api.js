/**
 * API Client for Laravel Backend
 */

import axios from 'axios';

export class ApiClient {
    constructor(baseURL, token = null) {
        this.baseURL = baseURL;
        this.token = token;
        this.client = axios.create({
            baseURL,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Tenant-ID': process.env.TENANT_ID || '1', // Default to tenant 1
            },
        });

        // Add request interceptor to include token and tenant
        this.client.interceptors.request.use((config) => {
            if (this.token) {
                config.headers.Authorization = `Bearer ${this.token}`;
            }
            // Ensure tenant header is set for multi-tenancy
            if (!config.headers['X-Tenant-ID']) {
                config.headers['X-Tenant-ID'] = process.env.TENANT_ID || '1';
            }
            return config;
        });

        // Add response interceptor for error handling
        this.client.interceptors.response.use(
            (response) => response,
            (error) => {
                if (error.response?.status === 401) {
                    throw new Error('Unauthorized - Please login again');
                }
                if (error.response?.status === 403) {
                    throw new Error('Forbidden - You do not have permission');
                }
                throw error;
            }
        );
    }

    /**
     * Set authentication token
     */
    setToken(token) {
        this.token = token;
    }

    /**
     * Login and get token
     */
    async login(email, password) {
        try {
            const response = await this.client.post('/api/login', {
                email,
                password,
            });

            if (response.data.token) {
                this.token = response.data.token;
                return response.data.token;
            }

            throw new Error('No token received from server');
        } catch (error) {
            // Re-throw with more context for better error handling upstream
            if (error.response) {
                // Preserve the full error object so formatError can access response details
                const enhancedError = new Error(error.response.data?.message || 'Login failed');
                enhancedError.response = error.response;
                enhancedError.status = error.response.status;
                enhancedError.data = error.response.data;
                throw enhancedError;
            }
            
            // Network or other errors
            const enhancedError = new Error(error.message || 'Unknown error');
            enhancedError.code = error.code;
            enhancedError.originalError = error;
            throw enhancedError;
        }
    }

    /**
     * Get available models for quick launch
     */
    async getModels() {
        const response = await this.client.get('/api/quick-launch/models');
        return response.data.models || {};
    }

    /**
     * Search navigation items
     */
    async searchNavigation(query) {
        const response = await this.client.get('/api/navigation/search', {
            params: { q: query },
        });
        return response.data.items || [];
    }

    /**
     * Search model items
     */
    async searchModel(model, query) {
        const response = await this.client.get(`/api/${model}/search`, {
            params: { q: query },
        });
        return response.data.items || [];
    }

    /**
     * List resources
     */
    async list(model, params = {}) {
        const response = await this.client.get(`/${model}`, { params });
        return response.data;
    }

    /**
     * Get single resource
     */
    async show(model, id) {
        const response = await this.client.get(`/${model}/${id}`);
        return response.data;
    }

    /**
     * Create resource
     */
    async create(model, data) {
        const formData = new FormData();
        Object.keys(data).forEach(key => {
            if (data[key] !== null && data[key] !== undefined) {
                formData.append(key, data[key]);
            }
        });

        const response = await this.client.post(`/${model}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        return response.data;
    }

    /**
     * Update resource
     */
    async update(model, id, data) {
        const formData = new FormData();
        formData.append('_method', 'PUT');
        Object.keys(data).forEach(key => {
            if (data[key] !== null && data[key] !== undefined) {
                formData.append(key, data[key]);
            }
        });

        const response = await this.client.post(`/${model}/${id}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        return response.data;
    }

    /**
     * Delete resource
     */
    async delete(model, id) {
        const response = await this.client.delete(`/${model}/${id}`);
        return response.data;
    }

    /**
     * Get CSRF token (for web routes)
     */
    async getCsrfToken() {
        try {
            const response = await this.client.get('/sanctum/csrf-cookie');
            // CSRF token is set in cookies, axios handles it automatically
            return true;
        } catch (error) {
            // If CSRF endpoint doesn't exist, that's okay for API routes
            return false;
        }
    }
}

