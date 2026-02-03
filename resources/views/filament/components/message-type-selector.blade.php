<div x-data="{
    selected: 'center',
    init() {
        this.selected = document.getElementById('form.message_type')?.value || 'center';
    }
}">
    <label class="text-sm font-medium text-gray-700 dark:text-gray-200" style="display: block; margin-bottom: 0.75rem;">
        Message Type
    </label>

    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
        {{-- Top Banner --}}
        <button
            type="button"
            @click="selected = 'top'; document.getElementById('form.message_type').value = 'top'; document.getElementById('form.message_type').dispatchEvent(new Event('input', { bubbles: true }));"
            :class="selected === 'top' ? 'bg-orange-50' : 'bg-white hover:bg-gray-50'"
            style="display: flex; flex-direction: column; align-items: center; padding: 1rem 0.75rem; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;"
            :style="selected === 'top' ? 'border: 3px solid #f97316;' : 'border: 2px solid #d1d5db;'"
        >
            <svg viewBox="0 0 100 200" style="width: 50px; height: 100px; margin: 0 auto 0.5rem auto; display: block;">
                {{-- Phone outline --}}
                <rect x="10" y="10" width="80" height="180" rx="12" fill="none" stroke="#6B7280" stroke-width="3"/>
                {{-- Screen --}}
                <rect x="15" y="25" width="70" height="160" rx="2" fill="#F3F4F6"/>
                {{-- Top banner (highlighted) --}}
                <rect x="15" y="25" width="70" height="30" rx="2" fill="#6366F1" opacity="0.8"/>
            </svg>
            <span style="font-size: 0.875rem; font-weight: 500; color: #374151;">Top</span>
        </button>

        {{-- Center Modal --}}
        <button
            type="button"
            @click="selected = 'center'; document.getElementById('form.message_type').value = 'center'; document.getElementById('form.message_type').dispatchEvent(new Event('input', { bubbles: true }));"
            :class="selected === 'center' ? 'bg-orange-50' : 'bg-white hover:bg-gray-50'"
            style="display: flex; flex-direction: column; align-items: center; padding: 1rem 0.75rem; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;"
            :style="selected === 'center' ? 'border: 3px solid #f97316;' : 'border: 2px solid #d1d5db;'"
        >
            <svg viewBox="0 0 100 200" style="width: 50px; height: 100px; margin: 0 auto 0.5rem auto; display: block;">
                {{-- Phone outline --}}
                <rect x="10" y="10" width="80" height="180" rx="12" fill="none" stroke="#6B7280" stroke-width="3"/>
                {{-- Screen --}}
                <rect x="15" y="25" width="70" height="160" rx="2" fill="#F3F4F6"/>
                {{-- Center modal (highlighted) --}}
                <rect x="25" y="75" width="50" height="50" rx="4" fill="#6366F1" opacity="0.8"/>
            </svg>
            <span style="font-size: 0.875rem; font-weight: 500; color: #374151;">Center</span>
        </button>

        {{-- Bottom Banner --}}
        <button
            type="button"
            @click="selected = 'bottom'; document.getElementById('form.message_type').value = 'bottom'; document.getElementById('form.message_type').dispatchEvent(new Event('input', { bubbles: true }));"
            :class="selected === 'bottom' ? 'bg-orange-50' : 'bg-white hover:bg-gray-50'"
            style="display: flex; flex-direction: column; align-items: center; padding: 1rem 0.75rem; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;"
            :style="selected === 'bottom' ? 'border: 3px solid #f97316;' : 'border: 2px solid #d1d5db;'"
        >
            <svg viewBox="0 0 100 200" style="width: 50px; height: 100px; margin: 0 auto 0.5rem auto; display: block;">
                {{-- Phone outline --}}
                <rect x="10" y="10" width="80" height="180" rx="12" fill="none" stroke="#6B7280" stroke-width="3"/>
                {{-- Screen --}}
                <rect x="15" y="25" width="70" height="160" rx="2" fill="#F3F4F6"/>
                {{-- Bottom banner (highlighted) --}}
                <rect x="15" y="155" width="70" height="30" rx="2" fill="#6366F1" opacity="0.8"/>
            </svg>
            <span style="font-size: 0.875rem; font-weight: 500; color: #374151;">Bottom</span>
        </button>

        {{-- Full Screen --}}
        <button
            type="button"
            @click="selected = 'full'; document.getElementById('form.message_type').value = 'full'; document.getElementById('form.message_type').dispatchEvent(new Event('input', { bubbles: true }));"
            :class="selected === 'full' ? 'bg-orange-50' : 'bg-white hover:bg-gray-50'"
            style="display: flex; flex-direction: column; align-items: center; padding: 1rem 0.75rem; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;"
            :style="selected === 'full' ? 'border: 3px solid #f97316;' : 'border: 2px solid #d1d5db;'"
        >
            <svg viewBox="0 0 100 200" style="width: 50px; height: 100px; margin: 0 auto 0.5rem auto; display: block;">
                {{-- Phone outline --}}
                <rect x="10" y="10" width="80" height="180" rx="12" fill="none" stroke="#6B7280" stroke-width="3"/>
                {{-- Full screen (highlighted) --}}
                <rect x="15" y="25" width="70" height="160" rx="2" fill="#6366F1" opacity="0.8"/>
            </svg>
            <span style="font-size: 0.875rem; font-weight: 500; color: #374151;">Full</span>
        </button>
    </div>
</div>
