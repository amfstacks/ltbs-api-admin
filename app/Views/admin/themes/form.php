<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6 flex items-center">
    <a href="<?= site_url('admin/themes') ?>" class="text-gray-500 hover:text-navy-900 mr-4 transition-colors">
        <i class="ph-bold ph-arrow-left text-xl"></i>
    </a>
    <h1 class="text-2xl font-bold text-navy-900"><?= $title ?></h1>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 max-w-2xl">
    
    <form action="<?= site_url('admin/themes/save/' . ($theme['id'] ?? '')) ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Theme Name</label>
            <input type="text" name="name" value="<?= old('name', $theme['name'] ?? '') ?>" required 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500" 
                   placeholder="e.g., Spiritual Warfare, Healing">
            <p class="text-xs text-gray-500 mt-1">The URL slug will be generated automatically.</p>
        </div>

        <div x-data="{ imageUrl: '<?= isset($theme['icon_url']) && $theme['icon_url'] ? base_url($theme['icon_url']) : '' ?>' }">
            <label class="block text-sm font-medium text-gray-700 mb-1">Theme Icon (Optional)</label>
            <div class="mt-1 flex items-center space-x-6">
                <div class="h-16 w-16 bg-gray-100 rounded-lg border border-gray-200 overflow-hidden flex items-center justify-center">
                    <template x-if="imageUrl">
                        <img :src="imageUrl" class="h-full w-full object-cover">
                    </template>
                    <template x-if="!imageUrl">
                        <i class="ph ph-image text-gray-400 text-2xl"></i>
                    </template>
                </div>
                <div class="flex-1">
                    <input type="file" name="icon" accept="image/*" 
                           @change="const file = $event.target.files[0]; if(file) { imageUrl = URL.createObjectURL(file); }"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button type="submit" class="bg-navy-900 hover:bg-navy-800 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                Save Theme
            </button>
        </div>
    </form>
</div>

<?= $this->endSection() ?>