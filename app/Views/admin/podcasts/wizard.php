<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-navy-900"><?= esc($title) ?></h1>
    <p class="text-gray-500 mt-1">Complete the steps below to publish a new Podcast.</p>
</div>

<div x-data="{ 
    step: 1, 
    maxStep: 4,
    errorMessage: '',
    
    // Upload States
    isUploading: false,
    uploadProgress: 0,
    uploadStatusText: 'Uploading files...',
    
    // Variables
    title: '<?= isset($podcast) ? esc($podcast['title'], 'js') : '' ?>',
    podcastDate: '<?= isset($podcast) && !empty($podcast['podcast_date']) ? esc($podcast['podcast_date'], 'js') : date('Y-m-d') ?>',
    categoryId: '<?= isset($podcast) ? $podcast['category_id'] : '' ?>',
    coverImageUrl: '<?= isset($podcast) && !empty($podcast['cover_image_url']) ? (str_starts_with($podcast['cover_image_url'], 'http') ? esc($podcast['cover_image_url'], 'js') : media_url($podcast['cover_image_url'])) : '' ?>',
    isUpdate: <?= isset($podcast) ? 'true' : 'false' ?>,
    
    nextStep() {
        this.errorMessage = ''; 

        if (this.step === 1 && (this.title.trim() === '' || this.categoryId === '')) {
            this.errorMessage = 'Please fill out all compulsory fields marked with an asterisk (*).';
            return; 
        }
        
        if (this.step === 2 && !this.isUpdate) {
            if (!this.$refs.mediaHighInput.files.length) {
                this.errorMessage = 'Please select a High Quality MP3 file (*).';
                return;
            }
        }

        this.step++;
    },

    checkFileSize(event) {
        const file = event.target.files[0];
        const maxSizeInBytes = 2 * 1024 * 1024; // 2MB limit
        if (file && file.size > maxSizeInBytes) {
            alert('File is too large! Maximum size is 2MB.');
            event.target.value = ''; 
        } else {
            this.coverImageUrl = URL.createObjectURL(file);
        }
    },

    
   // Robust AJAX Submitter with Orchestration
    submitForm(e) {
        this.errorMessage = '';
        this.isUploading = true;
        this.uploadProgress = 0;
        this.uploadStatusText = 'Sending files to server...';

        let formData = new FormData(e.target);
        
        // 👉 STEP 1 ISOLATION: Pull the cover image out if this is a new podcast
        let coverFile = formData.get('cover_image');
        if (!this.isUpdate) {
            formData.delete('cover_image');
        }

        let xhr = new XMLHttpRequest();
        xhr.open('POST', e.target.action, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.onprogress = (event) => {
            if (event.lengthComputable) {
                this.uploadProgress = Math.round((event.loaded / event.total) * 100);
                if(this.uploadProgress === 100) {
                    this.uploadStatusText = this.isUpdate ? 'Syncing to Cloudflare R2... Please wait.' : 'Saving MP3 to Vault... Please wait.';
                }
            }
        };

        xhr.onload = () => {
            try {
                let response = JSON.parse(xhr.responseText);
                
                if (xhr.status >= 200 && xhr.status < 300 && response.success) {
                    
                    // 👉 THE CHAINED REQUEST: Send ONLY the cover image!
                    if (!this.isUpdate && coverFile && coverFile.size > 0) {
                        this.uploadStatusText = 'Audio secured! Uploading Cover Image...';
                        this.uploadProgress = 0; // Reset progress bar

                        // Create a brand new, clean FormData object
                        let coverData = new FormData();
                        
                        // Append ONLY the 3 required pieces of data
                        coverData.append('cover_image', coverFile);
                        coverData.append('action', 'upload_only_cover'); // The trigger!
                        coverData.append('<?= csrf_token() ?>', response.new_csrf);

                        let xhrCover = new XMLHttpRequest();
                        // Ping the exact same endpoint, but append the NEW podcast ID
                        xhrCover.open('POST', '<?= site_url('admin/podcasts/save/') ?>' + response.podcast_id, true);
                        xhrCover.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                        
                        xhrCover.onload = () => {
                            // Regardless of if the cover succeeds or fails, we redirect!
                            window.location.href = response.redirect;
                        };
                        
                        xhrCover.onerror = () => {
                            // Fail gracefully and redirect anyway
                            window.location.href = response.redirect;
                        };

                        xhrCover.send(coverData);
                    } else {
                        // Normal update, or no cover image selected - redirect immediately
                        window.location.href = response.redirect;
                    }

                } else {
                    this.isUploading = false;
                    this.errorMessage = response.message || 'An unknown error occurred during upload.';
                    this.step = 2; 
                }
            } catch (error) {
                this.isUploading = false;
                console.error('Raw Server Response:', xhr.responseText); 
                this.errorMessage = 'Server Crash: The file might be too large for your server settings, or a database error occurred.';
                this.step = 2;
            }
        };

        xhr.onerror = () => {
            this.isUploading = false;
            this.errorMessage = 'A network error occurred. Check your connection.';
            this.step = 2;
        };

        xhr.send(formData);
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

    <form action="<?= site_url('admin/podcasts/save/' . ($podcast['id'] ?? '')) ?>" method="POST" enctype="multipart/form-data" @submit.prevent="submitForm" class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 relative overflow-hidden">
        <?= csrf_field() ?>

        <!-- Full Screen Uploading Overlay -->
        <div x-show="isUploading" style="display: none;" class="absolute inset-0 bg-white/95 backdrop-blur-sm z-50 flex flex-col items-center justify-center p-8">
            <div class="w-full max-w-md text-center">
                <i class="ph-duotone ph-cloud-arrow-up text-6xl text-gold-500 mb-4 animate-bounce"></i>
                <h3 class="text-xl font-bold text-navy-900 mb-2">Publishing Podcast</h3>
                <p class="text-sm text-gray-500 mb-6" x-text="uploadStatusText"></p>
                
                <div class="w-full bg-gray-200 rounded-full h-3 mb-2 overflow-hidden shadow-inner">
                    <div class="bg-gold-500 h-3 rounded-full transition-all duration-200 ease-out relative" :style="'width: ' + uploadProgress + '%'">
                        <div class="absolute top-0 left-0 bottom-0 right-0 overflow-hidden">
                            <div class="w-full h-full bg-white/20 animate-[shimmer_2s_infinite]"></div>
                        </div>
                    </div>
                </div>
                <p class="text-xs font-bold text-navy-900" x-text="uploadProgress + '%'"></p>
            </div>
        </div>

        <!-- STEP 1 -->
        <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            <h3 class="text-xl font-bold text-navy-900 mb-6 border-b pb-2">1. Core Information</h3>
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teaching Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" x-model="title" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description summary</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500"><?= isset($podcast) ? esc($podcast['description']) : '' ?></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Podcast Date </label>
        <input type="date" name="podcast_date" x-model="podcastDate" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <select name="category_id" x-model="categoryId" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500 select2-single">
                            <option value="">Select Category...</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (isset($podcast) && $podcast['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= esc($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Theme</label>
                        <select name="theme_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500 select2-single">
                            <option value="">Select Theme (Optional)...</option>
                            <?php foreach($themes as $theme): ?>
                                <option value="<?= $theme['id'] ?>" <?= (isset($podcast) && $podcast['theme_id'] == $theme['id']) ? 'selected' : '' ?>><?= esc($theme['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 2: MEDIA -->
        <div x-show="step === 2" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            <h3 class="text-xl font-bold text-navy-900 mb-6 border-b pb-2">2. Media & Streaming</h3>
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Podcast Cover Image (Max 2MB)</label>
                    <div class="mt-2 flex items-center space-x-6">
                        <div class="h-20 w-20 bg-gray-100 rounded-lg border border-gray-200 overflow-hidden flex items-center justify-center flex-shrink-0 shadow-sm">
                            <template x-if="coverImageUrl"><img :src="coverImageUrl" class="h-full w-full object-cover"></template>
                            <template x-if="!coverImageUrl"><i class="ph ph-image text-gray-400 text-3xl"></i></template>
                        </div>
                        <div class="flex-1">
                            <input type="file" name="cover_image" accept="image/*" @change="checkFileSize($event)" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                    </div>
                </div>

                <?php if(!isset($podcast)): ?>
                    <div class="p-5 bg-gray-50 rounded-lg border border-gray-200 shadow-sm">
                        <p class="text-sm font-medium text-navy-900 mb-4"><i class="ph-fill ph-cloud-arrow-up text-blue-500 mr-1.5"></i> Upload MP3 Audio File</p>
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">High Quality File (320kbps MP3) <span class="text-red-500">*</span></label>
                                <input type="file" x-ref="mediaHighInput" name="media_high" accept="audio/mpeg, audio/mp3" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gold-50 file:text-gold-700 hover:file:bg-gold-100 border border-gray-300 rounded-md bg-white">
                            </div>
                            <!-- <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Saver File (64kbps AAC/M4A) <span class="text-xs text-gray-400 font-normal">- Optional</span></label>
                                <input type="file" name="media_low" accept="audio/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-200 file:text-gray-700 hover:file:bg-gray-300 border border-gray-300 rounded-md bg-white">
                            </div> -->
                        </div>
                    </div>
                <?php else: ?>
                    <div class="p-5 bg-blue-50 rounded-lg border border-blue-100 flex items-start">
                        <i class="ph-fill ph-info text-blue-500 text-xl mr-3 mt-0.5"></i>
                        <div>
                            <h4 class="text-sm font-bold text-blue-900">Audio Management Locked</h4>
                            <p class="text-sm text-blue-800 mt-1">Audio files cannot be replaced from this general update screen. To update the MP3s for this teaching, please use the dedicated Media Editor.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- STEP 3: AUTHORS -->
        <div x-show="step === 3" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            <h3 class="text-xl font-bold text-navy-900 mb-6 border-b pb-2">3. Authors & Permissions</h3>
            <div class="space-y-5">
               <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Primary Author / Teacher <span class="text-red-500">*</span></label>
                    
                    <?php if(session()->get('role') === 'superadmin'): ?>
                        <select name="primary_author_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                            <?php foreach($authors as $author): ?>
                                <option value="<?= $author['id'] ?>" <?= (isset($primary_author_id) && $primary_author_id == $author['id']) || (!isset($primary_author_id) && session()->get('user_id') == $author['id']) ? 'selected' : '' ?>>
                                    <?= esc($author['first_name'] . ' ' . $author['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="primary_author_id" value="<?= session()->get('user_id') ?>">
                        <div class="w-full px-4 py-2 border border-gray-200 bg-gray-50 rounded-lg text-gray-500 font-medium cursor-not-allowed flex items-center shadow-inner">
                            <i class="ph-fill ph-lock-key mr-2 text-gray-400"></i>
                            <?= esc(session()->get('first_name') . ' ' . session()->get('last_name')) ?>
                        </div>
                    <?php endif; ?>
                </div>

             <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Add Co-Authors (Optional)</label>
                    
                    <select name="co_authors[]" multiple class="select2-multiple w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                        <?php 
                            // Ensure we have an array (defaults to empty for new podcasts)
                            $savedCoAuthors = $selected_co_authors ?? []; 
                        ?>
                        <?php foreach($authors as $author): ?>
                            
                            <?php 
                                // EXCLUDE THE CURRENT LOGGED-IN USER FROM THIS LIST
                                if($author['id'] == session()->get('user_id')) continue; 
                                
                                // 👉 THE FIX: Check if this author's ID is in the array from the controller!
                                $isSelected = in_array($author['id'], $savedCoAuthors) ? 'selected' : '';
                            ?>
                            
                            <!-- Add the $isSelected variable here! -->
                            <option value="<?= $author['id'] ?>" <?= $isSelected ?>>
                                <?= esc($author['first_name'] . ' ' . $author['last_name']) ?>
                            </option>
                        
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Search and select any additional teachers involved.</p>
                </div>

                <div class="flex items-center mt-4">
                    <!-- 👉 THE FIX: Pre-check the box if the controller says they can edit! -->
                    <input type="checkbox" name="co_authors_can_edit" value="1" <?= (isset($podcast['co_authors_can_edit']) && $podcast['co_authors_can_edit'] == 1) ? 'checked' : '' ?> class="h-4 w-4 text-gold-500 border-gray-300 rounded focus:ring-gold-500">
                    <label class="ml-2 block text-sm text-gray-700">Allow Co-Authors to edit text and categories.</label>
                </div>
            </div>
        </div>

        <!-- STEP 4: PUBLISH STATUS -->
        <!-- <div x-show="step === 4" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            <h3 class="text-xl font-bold text-navy-900 mb-6 border-b pb-2">4. Publish</h3>
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Publish Status</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                        <option value="draft" <?= (isset($podcast) && $podcast['status'] == 'draft') ? 'selected' : '' ?>>Save as Draft (Hidden from App)</option>
                        <option value="published" <?= (!isset($podcast) || $podcast['status'] == 'published') ? 'selected' : '' ?>>Publish Immediately</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 bg-navy-50 p-4 rounded-lg border border-navy-100 flex items-start">
                <i class="ph-fill ph-check-circle text-navy-900 text-xl mr-3 mt-0.5"></i>
                <p class="text-sm text-navy-900">You are ready! Ensure your files are selected. Please do not close the window once you click Save & Upload, as the files need time to sync to the Cloud.</p>
            </div>
        </div> -->
<div x-show="step === 4" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            <h3 class="text-xl font-bold text-navy-900 mb-6 border-b pb-2">4. Finalize & Submit</h3>
             <?php if(!isset($podcast)): ?>
            <div class="space-y-4">
                <label class="flex items-start p-4 border border-gold-500 bg-gold-50/30 rounded-xl cursor-pointer transition-colors hover:bg-gold-50">
                    <div class="flex-shrink-0 mt-0.5">
                        <input type="radio" name="status" value="processing" checked class="w-5 h-5 text-gold-600 focus:ring-gold-500 border-gray-300">
                    </div>
                    <div class="ml-4">
                        <span class="block text-sm font-bold text-navy-900">Submit for Processing & Review</span>
                        <span class="block text-xs text-gray-600 mt-1">Our servers will optimize your audio (HLS/Data Saver). Once complete, it will automatically be sent to the Admin team for QA review.</span>
                    </div>
                </label>

                <label class="flex items-start p-4 border border-gray-200 rounded-xl cursor-pointer transition-colors hover:bg-gray-50">
                    <div class="flex-shrink-0 mt-0.5">
                        <input type="radio" name="status" value="draft" class="w-5 h-5 text-navy-900 focus:ring-navy-900 border-gray-300">
                    </div>
                    <div class="ml-4">
                        <span class="block text-sm font-bold text-navy-900">Save as Draft (Do Not Process)</span>
                        <span class="block text-xs text-gray-500 mt-1">Upload the files and save your text, but do not start server processing or alert reviewers yet.</span>
                    </div>
                </label>
            </div>

            <div class="mt-6 bg-navy-50 p-4 rounded-lg border border-navy-100 flex items-start">
                <i class="ph-fill ph-info text-navy-900 text-xl mr-3 mt-0.5"></i>
                <p class="text-sm text-navy-900 leading-relaxed">
                    <strong>Note on Uploading:</strong> Please do not close this window once you click "Save & Upload". Heavy audio files require time to safely sync to our secure cloud storage.
                </p>
            </div>
             <?php else: ?>
                    <div class="p-5 bg-blue-50 rounded-lg border border-blue-100 flex items-start">
                        <i class="ph-fill ph-info text-blue-500 text-xl mr-3 mt-0.5"></i>
                        <div>
                            <h4 class="text-sm font-bold text-blue-900">Audio Management Locked</h4>
                            <p class="text-sm text-blue-800 mt-1">Podcast publish status   cannot be edited here. To update the Podcast status , please visit the podcast table.</p>
                        </div>
                    </div>
                <?php endif; ?>
        </div>
        <div class="mt-8 pt-6 border-t border-gray-100 flex justify-between" x-show="!isUploading">
            <button type="button" x-show="step > 1" @click="step--" class="px-6 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Back
            </button>
            <div x-show="step === 1"></div> 
            
            <button type="button" x-show="step < maxStep" @click="nextStep()" class="px-6 py-2 bg-navy-900 text-white font-bold rounded-lg hover:bg-navy-800 transition-colors">
                Continue
            </button>

            <button type="submit" x-show="step === maxStep" style="display: none;" class="px-6 py-2 bg-gold-500 text-navy-900 font-bold rounded-lg hover:bg-gold-600 transition-colors shadow-sm flex items-center">
                <i class="ph-bold ph-upload-simple mr-2"></i> Save & Upload
            </button>
        </div>
    </form>
</div>

<style>
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.select2-container--default .select2-selection--multiple {
        border-color: #D1D5DB; 
        border-radius: 0.5rem; 
        min-height: 42px;
        padding: 2px 4px;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #EAB308; 
        box-shadow: 0 0 0 1px #EAB308; 
    }

    /* Select2 Single Overrides */
    .select2-container--default .select2-selection--single {
        border-color: #D1D5DB; 
        border-radius: 0.5rem; 
        height: 42px;
        padding: 6px 4px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }
    .select2-container--default.select2-container--open .select2-selection--single,
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #EAB308; 
        box-shadow: 0 0 0 1px #EAB308; 
    }
</style>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* Quick Tailwind overrides for Select2 to match your theme */
    .select2-container--default .select2-selection--multiple {
        border-color: #D1D5DB; /* border-gray-300 */
        border-radius: 0.5rem; /* rounded-lg */
        min-height: 42px;
        padding: 2px 4px;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #EAB308; /* focus:border-gold-500 */
        box-shadow: 0 0 0 1px #EAB308; /* focus:ring-gold-500 */
    }
    .select2-container--default .select2-selection--multiple {
        border-color: #D1D5DB; 
        border-radius: 0.5rem; 
        min-height: 42px;
        padding: 2px 4px;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #EAB308; 
        box-shadow: 0 0 0 1px #EAB308; 
    }

    /* Select2 Single Styling Overrides to Match Tailwind */
    .select2-container--default .select2-selection--single {
        border-color: #D1D5DB; 
        border-radius: 0.5rem; 
        height: 42px; /* Matches Tailwind py-2 inputs */
        display: flex;
        align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #374151; /* text-gray-700 */
        line-height: normal;
        padding-left: 1rem; /* px-4 */
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
        right: 8px;
    }
    .select2-container--default.select2-container--open .select2-selection--single,
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #EAB308; 
        box-shadow: 0 0 0 1px #EAB308; 
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #9CA3AF; /* text-gray-400 */
    }
</style>

<script>
    $(document).ready(function() {
        $('.select2-multiple').select2({
            placeholder: "Select co-authors...",
            allowClear: true,
            width: '100%'
        });

      $('.select2-single').select2({
            placeholder: "Select an option...",
            allowClear: true,
            width: '100%'
        });

        // THE FIX: Tell Alpine.js whenever Select2 changes!
        // We listen specifically to select2 events to avoid infinite loops, 
        // and dispatch a native 'change' event which x-model is listening for.
        $('.select2-single').on('select2:select select2:unselect select2:clear', function() {
            this.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
</script>

<?= $this->endSection() ?>