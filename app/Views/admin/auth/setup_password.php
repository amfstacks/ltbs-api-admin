<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> | Let The Bible Speak</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- Tailwind Config for Branding -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { 800: '#1e293b', 900: '#0f172a' },
                        gold: { 400: '#facc15', 500: '#eab308', 600: '#ca8a04' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-6">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        
        <!-- Header -->
        <div class="bg-navy-900 p-8 text-center">
            <i class="ph-fill ph-shield-check text-5xl text-gold-400 mb-3"></i>
            <h1 class="text-2xl font-bold text-white">Welcome, <?= esc($user['first_name']) ?>!</h1>
            <p class="text-gray-400 text-sm mt-2">Create a secure password to activate your <?= esc($user['role']) ?> account.</p>
        </div>

        <!-- Form Body -->
        <div class="p-8">
            
            <!-- Alert Messages -->
            <?php if(session()->getFlashdata('error')): ?>
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <form action="<?= site_url('admin/setup-password/save') ?>" method="POST" class="space-y-5">
                <?= csrf_field() ?>
                
                <!-- Hidden Token -->
                <input type="hidden" name="token" value="<?= esc($token) ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ph ph-lock-key text-gray-400 text-lg"></i>
                        </div>
                        <input type="password" name="password" required minlength="8" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500 transition-colors" placeholder="At least 8 characters">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ph ph-check-circle text-gray-400 text-lg"></i>
                        </div>
                        <input type="password" name="confirm_password" required minlength="8" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-gold-500 focus:border-gold-500 transition-colors" placeholder="Type it again">
                    </div>
                </div>

                <button type="submit" class="w-full bg-gold-500 hover:bg-gold-600 text-navy-900 font-bold text-lg py-3 rounded-lg shadow transition-colors mt-4">
                    Activate Account
                </button>
            </form>
            
        </div>
    </div>

</body>
</html>