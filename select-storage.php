<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PEA INVENTORY - Select Storage</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap');
        body { font-family: 'Kanit', sans-serif; }
        .pea-bg { background: linear-gradient(135deg, #1e0b36 0%, #3b0764 50%, #581c87 100%); }
        .gold-glow { text-shadow: 0 0 12px rgba(251, 191, 36, 0.6); }
        
        /* การ์ดมิติแก้วเรืองแสงแบบมีภาพพื้นหลัง */
        .storage-card {
            background: rgba(30, 11, 54, 0.65);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .storage-card:hover {
            transform: translateY(-8px) scale(1.03);
            border-color: #fbbf24;
            box-shadow: 0 15px 30px rgba(109, 40, 217, 0.4), 0 0 20px rgba(251, 191, 36, 0.25);
        }
        
        @keyframes floatBg {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }
        .floating-orb { animation: floatBg 8s infinite ease-in-out; }
    </style>
</head>
<body class="pea-bg min-h-screen flex flex-col justify-between items-center p-4 overflow-x-hidden relative select-none">

    <div class="absolute top-1/4 left-1/4 w-72 h-72 bg-purple-600/20 rounded-full blur-[100px] floating-orb pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-amber-500/10 rounded-full blur-[120px] floating-orb pointer-events-none" style="animation-delay: -3s;"></div>
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10 pointer-events-none"></div>

    <header class="w-full max-w-4xl text-center pt-8 sm:pt-16 relative z-10 animate__animated animate__fadeInDown">
        <div class="inline-block p-4 bg-amber-400 text-purple-950 rounded-2xl font-bold text-xs tracking-widest uppercase mb-4 shadow-lg animate-pulse">
            ⚡️ ก้าวสู่ความอัจฉริยะในการบริหาร
        </div>
        <h1 class="text-white text-4xl sm:text-6xl font-black tracking-tight">
            PEA <span class="text-amber-400 gold-glow">INVENTORY</span>
        </h1>
        <p class="text-purple-200 mt-3 text-sm sm:text-base max-w-md mx-auto font-light leading-relaxed">
            กรุณาเลือกสถานที่ตั้งคลัง เพื่อตรวจสอบและจัดการพัสดุระบบไฟฟ้าได้อย่างแม่นยำ
        </p>
    </header>

    <main class="w-full max-w-4xl my-10 relative z-10">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 px-2 sm:px-6">
            <?php
            $url = "https://script.google.com/macros/s/AKfycbzyGkNj_5hCnoPD_DrMpzT7SY2L1GksaXeJUgACPscAPHI5l91z5ykMYiAav3ob4svdbg/exec";
            $response = @file_get_contents($url);
            $data = json_decode($response, true);

            if ($data) {
                $storages = [];
                foreach ($data as $item) {
                    if (!empty($item['storage_detail'])) {
                        $storageName = $item['storage_detail'];
                        
                        // ถ้าระบบยังไม่มีการบันทึกคลังนี้ ให้ตั้งค่าเริ่มต้น
                        if (!isset($storages[$storageName])) {
                            // ดึงรูปแรกมาใช้เป็นรูป Demo Cover ถ้าไม่มีจะใช้รูปดีฟอลต์แทน
                            $cover = (isset($item['images']) && is_array($item['images']) && count($item['images']) > 0) 
                                     ? $item['images'] [0] 
                                     : 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=500';

                            $storages[$storageName] = [
                                'name' => $storageName,
                                'contact' => isset($item['contact_name']) ? $item['contact_name'] : 'เจ้าหน้าที่พัสดุ',
                                'cover_image' => $cover
                            ];
                        }
                    }
                }

                $icons = ['🏬', '⚡️', '📦', '🏗️'];
                $index = 0;

                foreach ($storages as $storageName => $storageInfo) {
                    $currentIcon = $icons[$index % count($icons)];
                    $link_url = "app.php?storage=" . urlencode($storageInfo['name']); // เปลี่ยน app.php เป็นชื่อไฟล์หลักของคุณได้เลย
                    
                    echo '
                    <a href="' . $link_url . '" 
                       class="storage-card rounded-[2.5rem] p-6 sm:p-8 flex flex-col justify-between items-start group relative overflow-hidden animate__animated animate__fadeInUp" style="animation-delay: ' . ($index * 0.05) . 's">
                        
                        <div class="absolute inset-0 z-0 transition-transform duration-700 group-hover:scale-110">
                            <img src="' . $storageInfo['cover_image'] . '" class="w-full h-full object-cover opacity-25" alt="Storage Demo" referrerpolicy="no-referrer">
                            <div class="absolute inset-0 bg-gradient-to-t from-[#1e0b36] via-[#1e0b36]/80 to-purple-950/40"></div>
                        </div>

                        <div class="flex justify-between items-center w-full mb-8 relative z-10">
                            <div class="w-14 h-14 bg-purple-900/50 rounded-2xl border border-purple-500/30 flex items-center justify-center text-2xl group-hover:bg-amber-400 group-hover:text-purple-950 transition-colors duration-300 shadow-inner">
                                ' . $currentIcon . '
                            </div>
                            <span class="text-xs font-bold text-purple-300 bg-purple-950/80 px-3 py-1 rounded-full border border-purple-800/60 backdrop-blur-sm">
                                คลังที่เปิดบริการ
                            </span>
                        </div>
                        
                        <div class="relative z-10 w-full">
                            <h2 class="text-white text-2xl font-bold group-hover:text-amber-400 transition-colors line-clamp-1 tracking-tight">' . $storageInfo['name'] . '</h2>
                            <p class="text-purple-200/80 text-sm mt-1.5 font-light leading-snug line-clamp-2">ผู้ดูแลคลังพัสดุ: ' . $storageInfo['contact'] . '</p>
                        </div>
                        
                        <div class="mt-8 flex items-center gap-2 text-xs font-bold text-amber-400 group-hover:translate-x-2 transition-transform relative z-10">
                            <span>ตรวจสอบพัสดุภายในคลังนี้</span>
                            <span class="text-base">➔</span>
                        </div>
                    </a>';
                    
                    $index++;
                }
            } else {
                echo '<div class="col-span-2 text-center text-purple-300 py-12 font-medium relative z-10">❌ ไม่สามารถดึงรายชื่อคลังพัสดุจากระบบได้ในขณะนี้</div>';
            }
            ?>
        </div>
    </main>

    <footer class="w-full text-center pb-6 text-purple-400 text-xs font-light tracking-widest animate__animated animate__fadeInUp relative z-10">
        PEA INVENTORY APP • VERSION 2.0 • DIGITAL GOLD MANAGEMENT
    </footer>

</body>
</html>