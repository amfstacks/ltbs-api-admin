<?= $this->extend('admin/layout/master') ?>

<?= $this->section('content') ?>

<div class="min-h-[80vh] flex items-center justify-center">
    
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-gray-100">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-navy-900 mb-4 shadow-lg">
                <i class="ph-fill ph-book-open text-3xl text-gold-400"></i>
            </div>
            <h2 class="text-2xl font-bold text-navy-900">Admin Portal</h2>
            <p class="text-gray-500 text-sm mt-1">Sign in to manage the platform</p>
        </div>

        <form action="<?= site_url('admin/login/authenticate') ?>" method="POST" class="space-y-6">
            <?= csrf_field() ?>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ph ph-envelope text-gray-400"></i>
                    </div>
                    <input type="email" name="email" required class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500 sm:text-sm transition-colors" placeholder="admin@letthebiblespeak.com">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative" x-data="{ showPassword: false }">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ph ph-lock-key text-gray-400"></i>
                    </div>
                    <input :type="showPassword ? 'text' : 'password'" name="password" required class="block w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500 sm:text-sm transition-colors" placeholder="••••••••">
                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-navy-900">
                        <i class="ph" :class="showPassword ? 'ph-eye-slash' : 'ph-eye'"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-navy-900 bg-gold-400 hover:bg-gold-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gold-500 transition-all">
                Sign In <i class="ph-bold ph-arrow-right ml-2"></i>
            </button>
        </form>
    </div>

</div>

<?= $this->endSection() ?>