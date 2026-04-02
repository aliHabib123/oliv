# Filament Live Preview with File Uploads - Solution Documentation

**Problem Solved:** Real-time preview updates in Filament admin panel with file uploads (images) that persist correctly across all form interactions.

## The Core Issue

Filament's `FileUpload` component with `live()` mode has reactivity issues:
- Uploaded files are `TemporaryUploadedFile` objects (not serializable)
- Form data contains stale values when other fields update
- Alpine.js preview loses image URLs after text field changes
- Livewire re-renders destroy Alpine state

## Solution Pattern

### 1. PHP Backend (Filament Page)

#### A. Add Live State Properties

```php
// Tracks live uploaded files (temp URLs or saved paths)
public ?string $liveBannerImage = null;
public array $liveFeatureSections = [];
```

#### B. Initialize on Mount

```php
public function mount(): void
{
    // ... existing mount code ...
    
    // Initialize live banner with existing saved image
    if (isset($this->data['banner_image']) && is_string($this->data['banner_image'])) {
        $this->liveBannerImage = $this->data['banner_image'];
    }

    // Initialize live sections with UUID unwrapping and array_values
    if (isset($this->data['feature_sections']) && is_array($this->data['feature_sections'])) {
        $sections = array_values($this->data['feature_sections']);
        foreach ($sections as &$section) {
            if (isset($section['items']) && is_array($section['items'])) {
                $section['items'] = array_values($section['items']);
            }
        }
        $this->liveFeatureSections = $sections;
    }

    $this->form->fill($this->data);
}
```

#### C. File Upload Callback (Convert TemporaryUploadedFile)

```php
Forms\Components\FileUpload::make('banner_image')
    ->live(onBlur: false)
    ->afterStateUpdated(function ($state) {
        // Convert TemporaryUploadedFile to temporary URL
        if ($state === null) {
            $this->liveBannerImage = null;
        } elseif ($state instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            try {
                $this->liveBannerImage = $state->temporaryUrl();
            } catch (\Exception $e) {
                $this->liveBannerImage = null;
            }
        } else {
            $this->liveBannerImage = is_string($state) ? $state : null;
        }

        // Dispatch live state
        $data = $this->data;
        $data['banner_image'] = $this->liveBannerImage;
        $this->dispatch('preview-updated', data: $data);
    })
```

#### D. Text Field Callbacks (Use Live State)

```php
Forms\Components\TextInput::make('welcome_title')
    ->live(onBlur: false)
    ->afterStateUpdated(function ($state, $get) {
        // Dispatch with LIVE state, not raw form data
        $this->dispatch('preview-updated', data: [
            'banner_image' => $this->liveBannerImage,  // ← Live state
            'welcome_title' => $state,
            'welcome_text' => $get('welcome_text'),
            'feature_sections' => $this->liveFeatureSections,  // ← Live state
        ]);
    })
```

#### E. Repeater with Nested File Uploads

**CRITICAL: Unwrap Filament's UUID-keyed arrays before processing**

```php
Forms\Components\Repeater::make('feature_sections')
    ->live(onBlur: false)
    ->afterStateUpdated(function ($state, $get) {
        // Ensure sequential arrays for JSON (not objects)
        $sections = is_array($state) ? array_values($state) : [];
        
        foreach ($sections as $sKey => &$section) {
            if (isset($section['items']) && is_array($section['items'])) {
                $section['items'] = array_values($section['items']);
                
                foreach ($section['items'] as &$item) {
                    if (isset($item['icon'])) {
                        $icon = $item['icon'];
                        
                        // CRITICAL: Unwrap UUID structure
                        // Filament wraps: ['uuid-string' => TemporaryUploadedFile]
                        if (is_array($icon) && !empty($icon)) {
                            $icon = reset($icon); // Get first value
                        }
                        
                        if ($icon instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                            try {
                                $item['icon'] = $icon->temporaryUrl();
                            } catch (\Exception $e) {
                                $item['icon'] = null;
                            }
                        } elseif (is_string($icon)) {
                            $item['icon'] = $icon; // Keep saved paths
                        } else {
                            $item['icon'] = null;
                        }
                    }
                }
            }
        }
        
        $this->liveFeatureSections = $sections;
        
        $this->dispatch('preview-updated', data: [
            'banner_image' => $this->liveBannerImage,
            'welcome_title' => $get('welcome_title'),
            'welcome_text' => $get('welcome_text'),
            'feature_sections' => $this->liveFeatureSections,
        ]);
    })
```

**IMPORTANT: Do NOT add `afterStateUpdated` to nested FileUpload components - the parent repeater handles everything.**

### 2. Frontend (Alpine.js Blade Template)

#### A. Add `wire:ignore` to Alpine Container

```blade
<div wire:ignore x-data="previewComponent(@js($introData))">
    {{-- Alpine controls this DOM - Livewire won't touch it --}}
</div>
```

#### B. Alpine Component with Event Listener

```javascript
function previewComponent(initialData) {
    return {
        previewData: initialData || {},
        isUpdating: false,
        
        init() {
            // Listen for preview updates from Livewire
            Livewire.on('preview-updated', (eventData) => {
                const data = Array.isArray(eventData) ? eventData[0]?.data : eventData.data;
                
                this.isUpdating = true;
                
                // Replace entire object to trigger Alpine reactivity
                this.previewData = { ...data };
                
                this.$nextTick(() => {
                    this.isUpdating = false;
                });
            });
        },
        
        getBannerUrl() {
            const raw = this.previewData.banner_image;
            if (!raw) return null;
            
            // Handle full URLs (temp files) or relative paths (saved)
            return raw.startsWith('http') || raw.startsWith('/') 
                ? raw 
                : '/storage/' + raw;
        },
        
        getIconUrl(icon) {
            if (!icon) return null;
            let path = icon;

            // Handle Filament's UUID structure: {uuid: 'path'}
            if (typeof icon === 'object' && !Array.isArray(icon)) {
                const values = Object.values(icon);
                path = values[0];
            }

            if (!path || typeof path !== 'string') return null;

            // Full URLs (temp) or storage paths (saved)
            if (path.startsWith('/storage/') || path.startsWith('http')) {
                return path;
            }
            
            return '/storage/' + path;
        }
    };
}
```

## Key Technical Details

### Why `array_values()`?

PHP associative arrays with numeric keys serialize as JSON objects `{0: ..., 1: ...}` instead of arrays `[..., ...]`. Alpine's `x-for` requires arrays.

```php
// Without array_values(): {"0": {...}, "1": {...}}  ← Object
// With array_values():    [{...}, {...}]           ← Array
$sections = array_values($this->data['feature_sections']);
```

### Why `reset()` on Icon Arrays?

Filament wraps file uploads in UUID-keyed associative arrays:

```php
// What you get: ['61ceb276-...' => TemporaryUploadedFile]
// What you need: TemporaryUploadedFile

if (is_array($icon) && !empty($icon)) {
    $icon = reset($icon); // Extract first value
}
```

### Why `wire:ignore`?

Without `wire:ignore`, Livewire re-renders destroy Alpine's reactive state, causing blinks and lost data.

### Why Replace Entire Object in Alpine?

Alpine's reactivity tracks object replacement, not deep mutations:

```javascript
// ❌ Won't trigger reactivity
this.previewData.welcome_title = newTitle;

// ✓ Triggers reactivity
this.previewData = { ...data };
```

## Applying to Other Pages (e.g., HomeLayout)

1. **Copy the pattern:**
   - Add `$live...` properties for each file upload field
   - Initialize in `mount()` with `array_values()` 
   - Convert `TemporaryUploadedFile → temporaryUrl()` in callbacks
   - Unwrap UUID arrays with `reset()`
   - Dispatch live state, not raw form data

2. **Blade template:**
   - Add `wire:ignore` to Alpine container
   - Listen to `Livewire.on('preview-updated')`
   - Replace entire object: `this.previewData = { ...data }`

3. **Test flow:**
   - Page loads → existing files appear ✓
   - Delete file → disappears ✓
   - Upload file → appears immediately ✓
   - Type in text → everything persists ✓

## Common Pitfalls

1. **Don't add callbacks to nested FileUpload** - Parent repeater handles it
2. **Don't forget `array_values()`** - Results in JSON objects, not arrays
3. **Don't forget `reset()`** - UUID arrays won't unwrap
4. **Don't mutate Alpine objects** - Replace entire object for reactivity
5. **Don't skip `wire:ignore`** - Livewire will destroy Alpine state

## Summary

The solution ensures:
- ✓ Real-time preview updates
- ✓ Temporary upload URLs display immediately
- ✓ Saved file paths render correctly
- ✓ Text fields update live
- ✓ Banner and icons persist across all interactions
- ✓ No DOM destruction/blinking
- ✓ Clean, reusable pattern

**Time to implement:** ~2-3 hours for first page, ~30 min for additional pages

**Files modified:**
- `app/Filament/Pages/[Page].php` - Backend logic
- `resources/views/filament/components/[preview].blade.php` - Frontend preview

---

*Last updated: March 11, 2026*
*Pattern tested on: Introduction page (banner + repeater icons)*
