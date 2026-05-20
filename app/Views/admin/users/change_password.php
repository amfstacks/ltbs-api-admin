<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-navy-900">Change Password</h1>
    <p class="text-gray-500 mt-1">Ensure your account is using a long, random password to stay secure.</p>
</div>

<div class="max-w-2xl bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <form action="<?= site_url('admin/change-password/update') ?>" method="POST" class="p-8">
        <?= csrf_field() ?>

        <div class="space-y-6">
            <div x-data="{ show: false }">
                <label class="block text-sm font-bold text-navy-900 mb-1">Current Password</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="old_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500 pr-10">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-navy-900">
                        <i class="ph-fill" :class="show ? 'ph-eye-slash' : 'ph-eye'"></i>
                    </button>
                </div>
            </div>

            <hr class="border-gray-100">

            <div x-data="{ show: false }">
                <label class="block text-sm font-bold text-navy-900 mb-1">New Password</label>
                <p class="text-xs text-gray-500 mb-2">Must be at least 8 characters long.</p>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="new_password" required minlength="8" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500 pr-10">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-navy-900">
                        <i class="ph-fill" :class="show ? 'ph-eye-slash' : 'ph-eye'"></i>
                    </button>
                </div>
            </div>

            <div x-data="{ show: false }">
                <label class="block text-sm font-bold text-navy-900 mb-1">Confirm New Password</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="confirm_password" required minlength="8" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500 pr-10">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-navy-900">
                        <i class="ph-fill" :class="show ? 'ph-eye-slash' : 'ph-eye'"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
            <button type="submit" class="px-6 py-2.5 bg-navy-900 text-white font-bold rounded-lg hover:bg-navy-800 transition-colors shadow-sm flex items-center">
                <i class="ph-bold ph-lock-key mr-2"></i> Update Password
            </button>
        </div>
    </form>
</div>

<?= $this->endSection() ?>