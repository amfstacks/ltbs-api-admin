<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-navy-900">My Profile</h1>
    <p class="text-gray-500 mt-1">Manage your personal information and author biography.</p>
</div>

<form action="<?= site_url('admin/profile/update') ?>" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <?= csrf_field() ?>

    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center" x-data="imagePreview()">
            <h3 class="text-sm font-bold text-navy-900 mb-4 uppercase tracking-wider">Profile Picture</h3>
            
            <div class="relative w-40 h-40 mx-auto mb-6 rounded-full border-4 border-gray-50 bg-gray-100 shadow-inner overflow-hidden flex items-center justify-center group">
                
                <template x-if="imageUrl">
                    <img :src="imageUrl" class="w-full h-full object-cover">
                </template>
                
                <template x-if="!imageUrl">
                    <i class="ph-fill ph-user text-6xl text-gray-300"></i>
                </template>

                <label class="absolute inset-0 bg-navy-900/60 flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer z-10 backdrop-blur-sm">
                    <i class="ph-bold ph-camera text-2xl mb-1"></i>
                    <span class="text-xs font-medium">Change Photo</span>
                    <input type="file" name="profile_image" accept="image/*" class="hidden" @change="fileChosen">
                </label>
            </div>
            
            <p class="text-xs text-gray-500">Allowed formats: JPG, PNG, WEBP. Max size 2MB. Image will be automatically compressed.</p>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
            <h3 class="text-lg font-bold text-navy-900 mb-6 border-b pb-2">Personal Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <select name="title" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500 bg-white">
                        <option value="">None</option>
                        <?php 
                        $titles = ['Mr.', 'Mrs.', 'Miss', 'Dr.', 'Evang.', 'Elder', 'Pst.', 'Rev.', 'Bro.', 'Sis.'];
                        foreach($titles as $t): 
                        ?>
                            <option value="<?= $t ?>" <?= (isset($user['title']) && $user['title'] == $t) ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                    <input type="text" name="first_name" value="<?= esc($user['first_name']) ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                    <input type="text" name="last_name" value="<?= esc($user['last_name']) ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <div class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-500 flex items-center shadow-inner cursor-not-allowed">
                    <i class="ph-fill ph-envelope-simple mr-2 text-gray-400"></i>
                    <?= esc($user['email']) ?>
                </div>
                <p class="text-xs text-gray-400 mt-1">Email cannot be changed from this screen. Contact a superadmin if you need to update it.</p>
            </div>

            <div class="mb-8">
                <label class="block text-sm font-medium text-gray-700 mb-1">Author Biography</label>
                <p class="text-xs text-gray-500 mb-2">This bio will be visible to users on your Teacher Profile page.</p>
                
                <textarea id="bio-editor" name="bio"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-navy-900 text-white font-bold rounded-lg hover:bg-navy-800 transition-colors shadow-sm flex items-center">
                    <i class="ph-bold ph-floppy-disk mr-2"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('imagePreview', () => ({
            // Initialize with the securely fetched media URL!
            imageUrl: '<?= !empty($user['profile_image_url']) ? secure_audio_url($user['profile_image_url']) : '' ?>',
            
            fileChosen(event) {
                this.fileToDataUrl(event, src => this.imageUrl = src)
            },
            fileToDataUrl(event, callback) {
                if (!event.target.files.length) return
                let file = event.target.files[0],
                    reader = new FileReader()
                reader.readAsDataURL(file)
                reader.onload = e => callback(e.target.result)
            },
        }))
    })
</script>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

<style>
    /* Quick overrides to make Summernote match your Tailwind theme */
    .note-editor.note-frame {
        border-color: #D1D5DB !important; /* Tailwind border-gray-300 */
        border-radius: 0.5rem !important; /* Tailwind rounded-lg */
        box-shadow: none !important;
    }
    .note-editor .note-toolbar {
        background-color: #F9FAFB !important; /* Tailwind bg-gray-50 */
        border-bottom-color: #D1D5DB !important;
        border-top-left-radius: 0.5rem !important;
        border-top-right-radius: 0.5rem !important;
    }
</style>

<script>
    $(document).ready(function() {
        $('#bio-editor').summernote({
            placeholder: 'Write your author biography here...',
            tabsize: 2,
            height: 300,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link']],
                ['view', ['fullscreen', 'codeview']]
            ],
            callbacks: {
                // Prevent nasty image pasting that bloats your database
                onImageUpload: function(files) {
                    alert('Direct image uploads are disabled in the bio. Please use text only.');
                }
            }
        });
    });
</script>

<?= $this->endSection() ?>