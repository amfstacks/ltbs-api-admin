<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-navy-900"><?= esc($title) ?></h1>
    <p class="text-gray-500 mt-1">Complete the steps below to publish a new teaching.</p>
</div>

<div x-data="{ 
    step: 1, 
    maxStep: 4,
    errorMessage: '',
    
    // Variables to track our compulsory fields
    title: '',
    categoryId: '',
    mediaHighUrl: '',
    primaryAuthorId: '<?= session()->get('user_id') ?>',

    // Validates before allowing the user to move to the next step
    nextStep() {
        this.errorMessage = ''; // Clear old errors

        if (this.step === 1) {
            if (this.title.trim() === '' || this.categoryId === '') {
                this.errorMessage = 'Please fill out all compulsory fields marked with an asterisk (*).';
                return; // Stop them from moving forward
            }
        }
        
        if (this.step === 2) {
            if (this.mediaHighUrl.trim() === '') {
                this.errorMessage = 'Please provide the High Quality Media URL (*).';
                return;
            }
        }

        // If validation passes, go to next step
        this.step++;
    },

    // Instantly checks file size when a user selects an image
    checkFileSize(event) {
        const file = event.target.files[0];
        const maxSizeInBytes = 2 * 1024 * 1024; // 2MB limit
        
        if (file && file.size > maxSizeInBytes) {
            alert('File is too large! Maximum size is 2MB.');
            event.target.value = ''; // Clears the file input instantly!
        }
    }
}" class="max-w-4xl mx-auto">
    
    <div class="mb-8">
        <div class="flex justify-between mb-2">
            <span class="text-xs font-semibold text-gold-500 uppercase" x-text="'Step ' + step + ' of ' + maxStep"></span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-gold-500 h-2 rounded-full transition-all duration-300" :style="'width: ' + ((step / maxStep) * 100) + '%'"></div>
        </div>
    </div>
    <div x-show="errorMessage !== ''" x-transition class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r shadow-sm flex items-center">
        <i class="ph-fill ph-warning-circle text-xl mr-3"></i>
        <p x-text="errorMessage" class="text-sm font-medium"></p>
    </div>

    <form action="<?= site_url('admin/podcasts/store') ?>" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <?= csrf_field() ?>

        <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            <h3 class="text-xl font-bold text-navy-900 mb-6 border-b pb-2">1. Core Information</h3>
            
            <div class="space-y-5">
               <div>
    <label class="block text-sm font-medium text-gray-700 mb-1">
        Teaching Title <span class="text-red-500">*</span>
    </label>
    <input type="text" name="title" x-model="title" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
</div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description summary</label>
                    <textarea name="description" rows="3"  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
    <label class="block text-sm font-medium text-gray-700 mb-1">
        Category <span class="text-red-500">*</span>
    </label>
    <select name="category_id" x-model="categoryId" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
        <option value="">Select Category...</option>
        <?php foreach($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"><?= esc($cat['name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Theme</label>
                        <select name="theme_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                            <option value="">Select Theme (Optional)...</option>
                            <?php foreach($themes as $theme): ?>
                                <option value="<?= $theme['id'] ?>"><?= esc($theme['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="step === 2" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            <h3 class="text-xl font-bold text-navy-900 mb-6 border-b pb-2">2. Media & Streaming</h3>
            
            <div class="space-y-6">
                <!-- <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Podcast Cover Image</label>
                    <input type="file" name="cover_image" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div> -->
                <div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Podcast Cover Image (Max 2MB)</label>
    <input type="file" name="cover_image" accept="image/*" @change="checkFileSize($event)" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
</div>

                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-sm text-gray-500 mb-4"><i class="ph-fill ph-info text-blue-500 mr-1"></i> Enter the Cloudflare R2 / S3 URLs for your audio files to protect server bandwidth.</p>
                    
                    <div class="space-y-4">
                        <div>
                     <label class="block text-sm font-medium text-gray-700 mb-1">High Quality URL (320kbps MP3) <span class="text-red-500">*</span></label>
<input type="url" name="media_high_url" x-model="mediaHighUrl" placeholder="https://media.letthebiblespeak.com/audio_high.mp3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">   </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data Saver URL (64kbps AAC/M4A) - Optional</label>
                            <input type="url" name="media_low_url" placeholder="https://media.letthebiblespeak.com/audio_low.m4a" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="step === 3" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            <h3 class="text-xl font-bold text-navy-900 mb-6 border-b pb-2">3. Authors & Permissions</h3>
            
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Primary Author / Teacher</label>
                    <select name="primary_author_id"  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                        <?php foreach($authors as $author): ?>
                            <option value="<?= $author['id'] ?>" <?= session()->get('user_id') == $author['id'] ? 'selected' : '' ?>>
                                <?= esc($author['first_name'] . ' ' . $author['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Add Co-Authors (Optional)</label>
                    <select name="co_authors[]" multiple class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500 h-24">
                        <?php foreach($authors as $author): ?>
                            <option value="<?= $author['id'] ?>"><?= esc($author['first_name'] . ' ' . $author['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select multiple.</p>
                </div>

                <div class="flex items-center mt-4">
                    <input type="checkbox" name="co_authors_can_edit" value="1" class="h-4 w-4 text-gold-500 border-gray-300 rounded focus:ring-gold-500">
                    <label class="ml-2 block text-sm text-gray-700">Allow Co-Authors to edit text and categories (They cannot change the media URL).</label>
                </div>
            </div>
        </div>

        <div x-show="step === 4" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            <h3 class="text-xl font-bold text-navy-900 mb-6 border-b pb-2">4. Publish</h3>
            
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Publish Status</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                        <option value="draft">Save as Draft (Hidden from App)</option>
                        <option value="published">Publish Immediately</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 bg-navy-50 p-4 rounded-lg border border-navy-100 flex items-start">
                <i class="ph-fill ph-check-circle text-navy-900 text-xl mr-3 mt-0.5"></i>
                <p class="text-sm text-navy-900">You are ready to upload! Ensure your Cloudflare URLs are correct. Once published, this teaching will immediately be available to users on the mobile app.</p>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-100 flex justify-between">
            <button type="button" x-show="step > 1" @click="step--" class="px-6 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Back
            </button>
            <!-- <div x-show="step === 1"></div> <button type="button" x-show="step < maxStep" @click="step++" class="px-6 py-2 bg-navy-900 text-white font-bold rounded-lg hover:bg-navy-800 transition-colors">
                Continue
            </button> -->
            <button type="button" x-show="step < maxStep" @click="nextStep()" class="px-6 py-2 bg-navy-900 text-white font-bold rounded-lg hover:bg-navy-800 transition-colors">
    Continue
</button>

            <button type="submit" x-show="step === maxStep" style="display: none;" class="px-6 py-2 bg-gold-500 text-navy-900 font-bold rounded-lg hover:bg-gold-600 transition-colors shadow-sm flex items-center">
                <i class="ph-bold ph-upload-simple mr-2"></i> Save & Upload
            </button>
        </div>

    </form>
</div>

<?= $this->endSection() ?>