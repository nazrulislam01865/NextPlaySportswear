<aside class="np-banner-editor-column">
    <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" id="sale-banner-form" class="np-banner-editor-form">
        @csrf
        @if($selected) @method('PUT') @endif

        <x-admin.section-card
            title="1. Content"
            :description="$selected ? 'Edit the banner artwork and customer-facing content.' : 'Add the banner artwork and customer-facing content.'"
            id="banner-content"
        >
            <div class="np-banner-two-col">
                <label class="admin-label">Banner name <span class="text-brand-red">*</span>
                    <input class="admin-input" type="text" name="name" x-model="name" maxlength="255" required>
                </label>
                <label class="admin-label">Linked campaign
                    <select class="admin-input" name="sale_campaign_id" x-model="campaignId">
                        <option value="">No linked campaign</option>
                        @foreach($campaigns as $campaign)
                            <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="np-banner-image-grid">
                <div>
                    <label class="admin-label">Desktop image <span class="np-banner-recommendation">(recommended ratio 24:5)</span></label>
                    <input x-ref="desktopInput" class="sr-only" type="file" name="desktop_image" accept="image/jpeg,image/png,image/webp,image/avif" @change="setDesktopFile($event.target.files[0])" {{ $selected ? '' : 'required' }}>
                    <div class="np-banner-upload-preview" :class="desktopPreviewUrl ? 'has-image' : ''">
                        <template x-if="desktopPreviewUrl"><img :src="desktopPreviewUrl" alt="Desktop banner preview"></template>
                        <template x-if="!desktopPreviewUrl"><button type="button" @click="$refs.desktopInput.click()"><span>▧</span><strong>Add desktop image</strong><small>JPG, PNG, WebP or AVIF · up to 12MB</small></button></template>
                        <button x-show="desktopPreviewUrl" type="button" class="np-banner-remove-image" @click="clearDesktopFile()" aria-label="Clear selected desktop image">×</button>
                    </div>
                    <div class="np-banner-upload-actions" x-show="desktopPreviewUrl">
                        <button type="button" class="btn btn-white" @click="$refs.desktopInput.click()">▧ &nbsp;Change image</button>
                    </div>
                    @error('desktop_image')<p class="np-banner-field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-label">Mobile image <span class="np-banner-recommendation">(recommended ratio 5:4)</span></label>
                    <input x-ref="mobileInput" class="sr-only" type="file" name="mobile_image" accept="image/jpeg,image/png,image/webp,image/avif" @change="setMobileFile($event.target.files[0])">
                    <input type="hidden" name="remove_mobile_image" :value="removeMobile ? 1 : 0">
                    <div class="np-banner-upload-preview np-banner-upload-preview--mobile" :class="mobilePreviewUrl ? 'has-image' : ''">
                        <template x-if="mobilePreviewUrl"><img :src="mobilePreviewUrl" alt="Mobile banner preview"></template>
                        <template x-if="!mobilePreviewUrl"><button type="button" @click="$refs.mobileInput.click()"><span>▧</span><strong>Add mobile image</strong><small>JPG, PNG, WebP or AVIF · up to 12MB</small></button></template>
                        <button x-show="mobilePreviewUrl" type="button" class="np-banner-remove-image" @click="clearMobileFile()" aria-label="Remove mobile image">×</button>
                    </div>
                    <div class="np-banner-upload-actions" x-show="mobilePreviewUrl"><button type="button" class="btn btn-white" @click="$refs.mobileInput.click()">⇧ &nbsp;Change image</button></div>
                    @error('mobile_image')<p class="np-banner-field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <label class="admin-label">Alt text <span class="text-brand-red">*</span>
                <input class="admin-input" type="text" name="alt_text" x-model="altText" maxlength="255" required>
            </label>

            <div class="np-banner-two-col np-banner-field-row">
                <label class="admin-label">Heading <span class="text-brand-red">*</span>
                    <input class="admin-input" type="text" name="heading" x-model="heading" maxlength="255" required>
                </label>
                <label class="admin-label">CTA label <span class="text-brand-red">*</span>
                    <input class="admin-input" type="text" name="cta_label" x-model="ctaLabel" maxlength="80" required>
                </label>
            </div>

            <label class="admin-label np-banner-field-row">Destination link <span class="text-brand-red">*</span>
                <input class="admin-input" type="text" name="destination_link" x-model="destinationLink" maxlength="2048" placeholder="/sale" required>
            </label>
        </x-admin.section-card>

        <x-admin.section-card title="2. Placement" description="Choose where this banner will appear and review the actual content overlay." id="banner-placement">
            <div class="np-banner-placement-grid">
                <div>
                    @include('admin.promotions._banner-placement-fields', [
                        'placementState' => 'placements',
                        'toggleMethod' => 'togglePlacement',
                        'fieldName' => 'placements',
                    ])
                    @error('placements')<p class="np-banner-field-error">{{ $message }}</p>@enderror

                    <label class="admin-label np-banner-priority-label">Display priority
                        <input class="admin-input" type="number" name="priority" x-model.number="priority" min="1" max="999" required>
                    </label>
                </div>

                <div class="np-banner-device-preview">
                    <h3>Live banner preview</h3>
                    <p>Heading and CTA are rendered on top of the uploaded artwork, as they will be on the storefront.</p>
                    <div class="np-banner-device-tabs">
                        <button type="button" :class="previewDevice === 'desktop' ? 'is-active' : ''" @click="previewDevice='desktop'">Desktop</button>
                        <button type="button" :class="previewDevice === 'mobile' ? 'is-active' : ''" @click="previewDevice='mobile'">Mobile</button>
                    </div>
                    <div class="np-banner-device-frame" :class="previewDevice === 'mobile' ? 'is-mobile' : ''">
                        <template x-if="previewImageUrl">
                            <div class="np-banner-live-artwork">
                                <img :src="previewImageUrl" :alt="altText || name">
                                <span class="np-banner-live-artwork__shade" aria-hidden="true"></span>
                                <div class="np-banner-live-artwork__content">
                                    <strong x-text="heading || name || 'Banner heading'"></strong>
                                    <span x-show="ctaLabel" x-text="ctaLabel"></span>
                                </div>
                            </div>
                        </template>
                        <template x-if="!previewImageUrl"><div class="np-banner-preview-empty">Upload an image to preview the banner.</div></template>
                    </div>
                </div>
            </div>
        </x-admin.section-card>

        <x-admin.section-card title="3. Schedule" description="Set when this banner will be visible." id="banner-schedule">
            <div class="np-banner-schedule-row">
                <label class="np-banner-inherit-check">
                    <input type="checkbox" name="inherit_campaign_schedule" value="1" :checked="inheritSchedule" @change="inheritSchedule=$event.target.checked">
                    <span><strong>Inherit campaign schedule</strong><small x-show="campaignId">Banner follows the linked campaign schedule, including its selected weekdays.</small></span>
                </label>
                <label class="admin-label">Time zone
                    <select class="admin-input" name="timezone" x-model="timezone" required>
                        @foreach($timeZones as $timeZone)
                            <option value="{{ $timeZone->identifier }}">{{ $timeZone->label }} — {{ $timeZone->identifier }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div x-show="!inheritSchedule" x-cloak class="np-banner-custom-schedule">
                <label class="admin-label">Start date
                    <input class="admin-input" type="date" name="start_date" x-model="startDate">
                </label>
                <label class="admin-label">Start time
                    <input class="admin-input" type="time" name="start_time" x-model="startTime">
                </label>
                <label class="admin-label">End date
                    <input class="admin-input" type="date" name="end_date" x-model="endDate">
                </label>
                <label class="admin-label">End time
                    <input class="admin-input" type="time" name="end_time" x-model="endTime">
                </label>
            </div>
        </x-admin.section-card>

        <div class="np-banner-editor-footer">
            <button class="btn btn-red" type="submit">{{ $selected ? 'Save Banner' : 'Add Banner' }}</button>
            <a class="btn btn-white" :href="previewUrl" target="_blank" rel="noopener">◉ &nbsp;Preview on Store</a>
        </div>
    </form>

    @if($selected)
        <form method="POST" action="{{ route('admin.promotions.banners.destroy', $selected) }}" class="np-banner-delete-form" onsubmit="return confirm('Delete this sale banner?')">
            @csrf
            @method('DELETE')
            <button type="submit">Delete banner</button>
        </form>
    @endif
</aside>
