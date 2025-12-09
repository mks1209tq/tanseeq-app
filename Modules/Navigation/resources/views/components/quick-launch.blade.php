{{-- Quick Launch Component --}}
{{-- This component initializes the quick launch functionality --}}
{{-- The modal HTML is created dynamically by the JavaScript module --}}

<style>
    .quick-launch-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 9999;
        display: none;
        align-items: flex-start;
        justify-content: center;
        padding-top: 10vh;
    }

    .quick-launch-modal.open {
        display: flex;
    }

    .quick-launch-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }

    .dark .quick-launch-backdrop {
        background: rgba(0, 0, 0, 0.7);
    }

    .quick-launch-container {
        position: relative;
        width: 100%;
        max-width: 640px;
        margin: 0 auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        z-index: 1;
    }

    .dark .quick-launch-container {
        background: #161615;
        border: 1px solid #3E3E3A;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.3);
    }

    .quick-launch-header {
        padding: 1rem;
        border-bottom: 1px solid #e3e3e0;
    }

    .dark .quick-launch-header {
        border-bottom-color: #3E3E3A;
    }

    .quick-launch-search {
        width: 100%;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        border: 1px solid #e3e3e0;
        border-radius: 6px;
        background: white;
        color: #1b1b18;
        outline: none;
        transition: border-color 0.2s;
    }

    .quick-launch-search:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .dark .quick-launch-search {
        background: #0a0a0a;
        border-color: #3E3E3A;
        color: #EDEDEC;
    }

    .dark .quick-launch-search:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }

    .quick-launch-results {
        max-height: 400px;
        overflow-y: auto;
        padding: 0.5rem;
    }

    .quick-launch-empty {
        padding: 2rem;
        text-align: center;
    }

    .quick-launch-item {
        padding: 0.75rem 1rem;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.15s;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .quick-launch-item:hover,
    .quick-launch-item.selected {
        background: #f5f5f4;
    }

    .dark .quick-launch-item:hover,
    .dark .quick-launch-item.selected {
        background: #2a2a28;
    }

    .quick-launch-item-content {
        display: flex;
        align-items: center;
        flex: 1;
        min-width: 0;
    }

    .quick-launch-item-label {
        font-weight: 500;
        color: #1b1b18;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dark .quick-launch-item-label {
        color: #EDEDEC;
    }

    .quick-launch-group {
        font-size: 0.75rem;
        color: #706f6c;
        background: #f5f5f4;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        margin-left: 0.5rem;
        white-space: nowrap;
    }

    .dark .quick-launch-group {
        color: #A1A09A;
        background: #2a2a28;
    }

    .quick-launch-item-route {
        font-size: 0.875rem;
        color: #706f6c;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px;
    }

    .dark .quick-launch-item-route {
        color: #A1A09A;
    }

    .quick-launch-footer {
        padding: 0.75rem 1rem;
        border-top: 1px solid #e3e3e0;
        background: #fafafa;
    }

    .dark .quick-launch-footer {
        border-top-color: #3E3E3A;
        background: #1a1a19;
    }

    .quick-launch-hints {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .quick-launch-hint {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.75rem;
        color: #706f6c;
    }

    .dark .quick-launch-hint {
        color: #A1A09A;
    }

    .quick-launch-hint kbd {
        display: inline-block;
        padding: 0.125rem 0.375rem;
        font-size: 0.75rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        background: white;
        border: 1px solid #e3e3e0;
        border-radius: 4px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        color: #1b1b18;
    }

    .dark .quick-launch-hint kbd {
        background: #2a2a28;
        border-color: #3E3E3A;
        color: #EDEDEC;
    }

    @media (max-width: 640px) {
        .quick-launch-modal {
            padding-top: 0;
            align-items: stretch;
        }

        .quick-launch-container {
            max-width: 100%;
            border-radius: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .quick-launch-results {
            flex: 1;
            max-height: none;
        }
    }
</style>

{{-- Script is loaded via app.js import --}}

