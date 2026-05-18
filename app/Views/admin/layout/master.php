<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Admin Panel') ?> | Let The Bible Speak</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { 800: '#0A192F', 900: '#020C1B' }, // Deep Navy
                        gold: { 400: '#FACC15', 500: '#EAB308' }, // Premium Gold
                    }
                }
            }
        }
    </script>
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <script src="https://unpkg.com/@phosphor-icons/web" defer></script>
</head>
<body class="bg-gray-50 text-gray-800 h-screen flex overflow-hidden">

    <?php if (session()->get('is_logged_in')): ?>
        <?= $this->include('admin/layout/sidebar') ?>
    <?php endif; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <?php if (session()->get('is_logged_in')): ?>
            <?= $this->include('admin/layout/topbar') ?>
        <?php endif; ?>

        <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
            
            <?php if (session()->getFlashdata('success')) : ?>
                <div x-data="{ show: true }" x-show="show" class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 flex justify-between rounded shadow-sm">
                    <p><?= session()->getFlashdata('success') ?></p>
                    <button @click="show = false"><i class="ph ph-x"></i></button>
                </div>
            <?php endif; ?>
            
            <?php if (session()->getFlashdata('error')) : ?>
                <div x-data="{ show: true }" x-show="show" class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 flex justify-between rounded shadow-sm">
                    <p><?= session()->getFlashdata('error') ?></p>
                    <button @click="show = false"><i class="ph ph-x"></i></button>
                </div>
            <?php endif; ?>

            <?= $this->renderSection('content') ?>
            
        </main>
    </div>

</body>
</html>