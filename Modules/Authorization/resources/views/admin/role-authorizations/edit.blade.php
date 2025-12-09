@extends('ui::layouts.app')

@section('title', 'Edit Role Authorization')

@section('content')
<div class="max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('authorization.dashboard') }}" class="text-lg font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-1 hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Authorization Admin</a>
        <h1 class="text-3xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">Edit Authorization for Role: {{ $role->name }}</h1>
    </div>

    <form action="{{ route('admin.authorization.role-authorizations.update', [$role, $roleAuthorization]) }}" method="POST" class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label for="auth_object_id" class="block text-sm font-medium mb-1">Authorization Object *</label>
            <select name="auth_object_id" id="auth_object_id" required class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
                @foreach($authObjects as $authObject)
                    <option value="{{ $authObject->id }}" {{ $roleAuthorization->auth_object_id == $authObject->id ? 'selected' : '' }}>{{ $authObject->code }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="label" class="block text-sm font-medium mb-1">Label</label>
            <input type="text" name="label" id="label" value="{{ old('label', $roleAuthorization->label) }}" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
        </div>

        <div>
            <div class="flex justify-between items-center mb-2">
                <label class="block text-sm font-medium">Field Rules</label>
                <button type="button" onclick="addFieldRule()" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">+ Add Field Rule</button>
            </div>
            <div id="fields-container" class="space-y-4">
                @foreach($roleAuthorization->fields as $index => $field)
                    <div class="border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm p-4 space-y-3">
                        <div class="flex justify-between">
                            <h4 class="font-medium">Field Rule {{ $index + 1 }}</h4>
                            <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-red-600 dark:text-red-400">Remove</button>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Field Code *</label>
                            <input type="text" name="fields[{{ $index }}][field_code]" value="{{ $field->field_code }}" required class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Operator *</label>
                            <select name="fields[{{ $index }}][operator]" required class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615]" onchange="toggleValueFields(this)">
                                <option value="*" {{ $field->operator === '*' ? 'selected' : '' }}>* (Wildcard)</option>
                                <option value="=" {{ $field->operator === '=' ? 'selected' : '' }}>= (Equals)</option>
                                <option value="in" {{ $field->operator === 'in' ? 'selected' : '' }}>in (In List)</option>
                                <option value="between" {{ $field->operator === 'between' ? 'selected' : '' }}>between (Range)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Value From</label>
                            <input type="text" name="fields[{{ $index }}][value_from]" value="{{ $field->value_from }}" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Value To (for between)</label>
                            <input type="text" name="fields[{{ $index }}][value_to]" value="{{ $field->value_to }}" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615]">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                Update
            </button>
            <a href="{{ route('admin.authorization.roles.edit', $role) }}" class="px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm hover:bg-gray-50 dark:hover:bg-[#3E3E3A] transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
let fieldRuleIndex = {{ $roleAuthorization->fields->count() }};
function addFieldRule() {
    const container = document.getElementById('fields-container');
    const fieldHtml = `
        <div class="border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm p-4 space-y-3">
            <div class="flex justify-between">
                <h4 class="font-medium">Field Rule ${fieldRuleIndex + 1}</h4>
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-red-600 dark:text-red-400">Remove</button>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Field Code *</label>
                <input type="text" name="fields[${fieldRuleIndex}][field_code]" required class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615]">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Operator *</label>
                <select name="fields[${fieldRuleIndex}][operator]" required class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615]" onchange="toggleValueFields(this)">
                    <option value="*">* (Wildcard)</option>
                    <option value="=">= (Equals)</option>
                    <option value="in">in (In List)</option>
                    <option value="between">between (Range)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Value From</label>
                <input type="text" name="fields[${fieldRuleIndex}][value_from]" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615]">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Value To (for between)</label>
                <input type="text" name="fields[${fieldRuleIndex}][value_to]" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615]">
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', fieldHtml);
    fieldRuleIndex++;
}

function toggleValueFields(select) {
    const container = select.closest('.border');
    const valueFrom = container.querySelector('input[name*="[value_from]"]');
    const valueTo = container.querySelector('input[name*="[value_to]"]');
    
    if (select.value === '*') {
        valueFrom.disabled = true;
        valueTo.disabled = true;
    } else if (select.value === 'between') {
        valueFrom.disabled = false;
        valueTo.disabled = false;
        valueTo.required = true;
    } else {
        valueFrom.disabled = false;
        valueTo.disabled = true;
    }
}
</script>
@endsection

