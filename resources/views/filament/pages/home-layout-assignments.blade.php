<x-filament-panels::page>
    @if(empty($this->categories))
        <div style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 3rem; text-align: center;">
            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">No Categories Synced</h3>
            <p style="color: #6b7280; margin-bottom: 1.5rem;">Sync your main categories from Shopify first to assign layouts.</p>
            <a href="{{ route('filament.admin.pages.app-settings') }}"
               style="display: inline-block; padding: 0.625rem 1.25rem; background: #3b82f6; color: white; font-weight: 500; border-radius: 0.5rem; text-decoration: none;">
                Go to App Settings
            </a>
        </div>
    @else
        <div style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                        <th style="padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                            Category
                        </th>
                        <th style="padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                            Status
                        </th>
                        <th style="padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                            Assigned Layout
                        </th>
                        <th style="padding: 0.75rem 1.5rem; text-align: right; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->assignments as $handle => $assignment)
                        <tr style="border-bottom: 1px solid #e5e7eb;" onmouseover="this.style.backgroundColor='#f9fafb'" onmouseout="this.style.backgroundColor='white'">
                            <td style="padding: 1rem 1.5rem;">
                                <div style="font-weight: 500; font-size: 0.875rem; color: #111827;">
                                    {{ $assignment['category_title'] }}
                                </div>
                                <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">
                                    {{ $handle }}
                                </div>
                            </td>

                            <td style="padding: 1rem 1.5rem;">
                                @if($assignment['has_assignment'])
                                    @if($assignment['is_active'])
                                        <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; background: #d1fae5; color: #065f46; font-size: 0.75rem; font-weight: 500; border-radius: 9999px;">
                                            Active
                                        </span>
                                    @else
                                        <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; background: #f3f4f6; color: #374151; font-size: 0.75rem; font-weight: 500; border-radius: 9999px;">
                                            Inactive
                                        </span>
                                    @endif
                                @else
                                    <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; background: #fef3c7; color: #92400e; font-size: 0.75rem; font-weight: 500; border-radius: 9999px;">
                                        Not Assigned
                                    </span>
                                @endif
                            </td>

                            <td style="padding: 1rem 1.5rem; font-size: 0.875rem; color: #111827;">
                                {{ $assignment['layout_name'] ?? '—' }}
                            </td>

                            <td style="padding: 1rem 1.5rem;">
                                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; align-items: center;">
                                    @if($assignment['has_assignment'])
                                        <button
                                            type="button"
                                            wire:click="toggleStatus('{{ $handle }}')"
                                            style="padding: 0.375rem 0.75rem; font-size: 0.75rem; font-weight: 500; border-radius: 0.375rem; border: none; cursor: pointer; transition: all 0.15s; {{ $assignment['is_active'] ? 'background: #f3f4f6; color: #374151;' : 'background: #d1fae5; color: #065f46;' }}"
                                            onmouseover="this.style.opacity='0.8'"
                                            onmouseout="this.style.opacity='1'">
                                            {{ $assignment['is_active'] ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    @endif

                                    <div x-data="{ open: false }" style="position: relative; display: inline-block;">
                                        <button
                                            type="button"
                                            @click="open = !open"
                                            style="padding: 0.375rem 0.75rem; background: #3b82f6; color: white; font-size: 0.75rem; font-weight: 500; border-radius: 0.375rem; border: none; cursor: pointer; transition: all 0.15s;"
                                            onmouseover="this.style.backgroundColor='#2563eb'"
                                            onmouseout="this.style.backgroundColor='#3b82f6'">
                                            {{ $assignment['has_assignment'] ? 'Reassign' : 'Assign' }}
                                        </button>

                                        <div x-show="open"
                                             x-transition
                                             @click.outside="open = false"
                                             style="position: absolute; right: 0; z-index: 50; margin-top: 0.5rem; width: 14rem; background: white; border-radius: 0.5rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
                                            <div style="padding: 0.5rem;">
                                                @if(!empty($this->existingLayouts))
                                                    <div style="padding: 0.5rem 0.75rem; font-size: 0.625rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                                                        Existing Layouts
                                                    </div>
                                                    @foreach($this->existingLayouts as $layout)
                                                        <button
                                                            type="button"
                                                            wire:click="assignExistingLayout('{{ $handle }}', {{ $layout['id'] }})"
                                                            @click="open = false"
                                                            style="width: 100%; text-align: left; padding: 0.5rem 0.75rem; font-size: 0.875rem; color: #374151; border-radius: 0.375rem; background: transparent; border: none; cursor: pointer; transition: background-color 0.15s;"
                                                            onmouseover="this.style.backgroundColor='#f3f4f6'"
                                                            onmouseout="this.style.backgroundColor='transparent'">
                                                            {{ $layout['name'] }}
                                                        </button>
                                                    @endforeach
                                                    <div style="margin: 0.5rem 0; border-top: 1px solid #e5e7eb;"></div>
                                                @endif

                                                <button
                                                    type="button"
                                                    wire:click="assignFromTemplate('{{ $handle }}')"
                                                    @click="open = false"
                                                    style="width: 100%; text-align: left; padding: 0.5rem 0.75rem; font-size: 0.875rem; color: #374151; border-radius: 0.375rem; background: transparent; border: none; cursor: pointer; transition: background-color 0.15s; display: flex; align-items: center; gap: 0.5rem;"
                                                    onmouseover="this.style.backgroundColor='#f3f4f6'"
                                                    onmouseout="this.style.backgroundColor='transparent'">
                                                    <svg style="width: 1rem; height: 1rem; color: #9ca3af; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    Import from Template
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
