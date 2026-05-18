<?= $this->extend('admin/layout/master') ?>
<?= $this->section('content') ?>

<div class="mb-6 flex items-center">
    <a href="<?= site_url('admin/users') ?>" class="text-gray-500 hover:text-navy-900 mr-4 transition-colors"><i class="ph-bold ph-arrow-left text-xl"></i></a>
    <h1 class="text-2xl font-bold text-navy-900"><?= esc($title) ?></h1>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 max-w-2xl">
    <div class="mb-6 pb-6 border-b border-gray-100">
        <h2 class="text-lg font-bold text-navy-900">Account Details</h2>
        <p class="text-sm text-gray-500 mt-1">An invitation email will be sent to this user with a secure link to set their own password.</p>
    </div>

    <form action="<?= site_url('admin/users/store') ?>" method="POST" class="space-y-6">
        <?= csrf_field() ?>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                <input type="text" name="first_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                <input type="text" name="last_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
            <input type="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Platform Role <span class="text-red-500">*</span></label>
            <select name="role" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500">
                <option value="author">Author (Can upload and edit their own podcasts)</option>
                <option value="reviewer">Reviewer (Can review and approve pending podcasts)</option>
                <option value="superadmin">Super Admin (Can manage everything)</option>
            </select>
        </div>

        <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button type="submit" class="bg-navy-900 hover:bg-navy-800 text-white font-bold py-2 px-6 rounded-lg transition-colors flex items-center">
                <i class="ph-bold ph-paper-plane-tilt mr-2"></i> Send Invitation
            </button>
        </div>
    </form>
</div>

<?= $this->endSection() ?>