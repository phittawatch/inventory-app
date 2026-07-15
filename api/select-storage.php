<?php
// 1. ดึงข้อมูลพัสดุจาก API
$url = "https://script.google.com/macros/s/AKfycbzyGkNj_5hCnoPD_DrMpzT7SY2L1GksaXeJUgACPscAPHI5l91z5ykMYiAav3ob4svdbg/exec";
$response = @file_get_contents($url);
$rawData = json_decode($response, true);

// 2. จัดกลุ่มข้อมูลใน PHP ตาม 'sub_of' เพื่อส่งให้ Frontend ใช้งานง่ายขึ้น
$groupedData = [];
if (is_array($rawData)) {
    foreach ($rawData as $item) {
        // ดึงค่า sub_of ถ้าไม่มีให้ใส่ "ไม่ระบุหน่วยงานหลัก"
        $subOf = !empty($item['sub_of']) ? $item['sub_of'] : 'ไม่ระบุหน่วยงานหลัก';
        
        // เตรียมรูปภาพ
        $item['display_images'] = (isset($item['images']) && is_array($item['images']) && count($item['images']) > 0) 
                                  ? $item['images'] 
                                  : ['https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=500'];
        
        // จัดกลุ่มย่อย
        $groupedData[$subOf][] = $item;
    }
}

// แปลงข้อมูลที่จัดกลุ่มแล้วเป็น JSON สำหรับ Alpine.js
$jsonGroupedData = htmlspecialchars(json_encode($groupedData), ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PEA INVENTORY APP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap');
        body { font-family: 'Kanit', sans-serif; }
        .pea-gradient { background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 100%); }
        .gold-text { color: #fbbf24; text-shadow: 0 0 10px rgba(251, 191, 36, 0.5); }
        .card-hover:hover { transform: translateY(-5px) scale(1.01); }
        
        /* สไตล์พิเศษสำหรับหน้า Loading */
        .loading-gradient { background: linear-gradient(180deg, #2e1065 0%, #4c1d95 50%, #6d28d9 100%); }
        .gold-glow { box-shadow: 0 0 20px rgba(251, 191, 36, 0.6); }
        
        @keyframes pulseGlow {
            0%, 100% { transform: scale(1); opacity: 0.6; }
            50% { transform: scale(1.1); opacity: 1; }
        }
        .pulse-layer { animation: pulseGlow 2s infinite ease-in-out; }

        .swiper { width: 100%; height: 200px; border-radius: 1.25rem; background-color: #f8fafc; }
        @media (min-width: 768px) { .swiper { height: 240px; } }
        
        .swiper-slide { display: flex; justify-content: center; align-items: center; overflow: hidden; }
        .swiper-button-next, .swiper-button-prev { color: #6d28d9 !important; transform: scale(0.5); }
        .swiper-pagination-bullet-active { background: #6d28d9 !important; }
        
        .no-scroll { overflow: hidden; }
    </style>
</head>
<body class="bg-slate-50 no-scroll" 
      x-data="{ 
          open: false, 
          activeItem: {}, 
          swiperInstance: null, 
          isLoading: true, 
          touchStartY: 0,
          // จัดการเกี่ยวกับการเลือกกลุ่ม
          groupedData: <?php echo $jsonGroupedData; ?>,
          selectedSubOf: null // ค่าเริ่มต้นยังไม่ได้เลือกตัวไหนเลย (จะแสดงรายชื่อ sub_of ทั้งหมด)
      }"
      :class="{ 'no-scroll': open || isLoading }"
      x-init="setTimeout(() => { isLoading = false; }, 2000)">

<div x-show="isLoading"
     x-transition:leave="transition ease-in duration-700 transform"
     x-transition:leave-start="translate-y-0 opacity-100"
     x-transition:leave-end="-translate-y-full opacity-0"
     @touchstart="touchStartY = $event.touches[0].clientY"
     @touchend="if (touchStartY - $event.changedTouches[0].clientY > 70) { isLoading = false; }"
     class="fixed inset-0 z-[100] loading-gradient flex flex-col justify-between items-center p-8 select-none text-center overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 pointer-events-none"></div>
    <div></div>
    <div class="relative z-10 flex flex-col items-center">
        <div class="w-24 h-24 bg-amber-500/10 rounded-full absolute pulse-layer flex items-center justify-center blur-md"></div>
        <div class="w-20 h-20 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-3xl flex items-center justify-center text-4xl shadow-lg gold-glow animate__animated animate__bounceIn">⚡️</div>
        <h1 class="text-white text-4xl font-bold tracking-tighter mt-6 animate__animated animate__fadeInUp animate__delay-1s">
            PEA <span class="gold-text">INVENTORY</span> APP
        </h1>
        <p class="text-purple-200 mt-3 text-base max-w-xs font-light tracking-wide animate__animated animate__fadeInUp animate__delay-1s">
            ระบบจัดการพัสดุไฟฟ้า<br><span class="text-yellow-400/90 font-medium">"ค้นหาง่าย แยกตามจังหวัด/กฟจ."</span>
        </p>
    </div>
    <div class="relative z-10 flex flex-col items-center mb-4 animate__animated animate__fadeInUp animate__delay-2s pointer-events-none">
        <div class="text-amber-400 text-xl font-bold mb-1 animate-bounce">▲</div>
        <p class="text-purple-300 text-xs tracking-widest font-light uppercase">Swipe Up to Skip หรือรอสักครู่</p>
    </div>
</div>

<header class="pea-gradient p-6 sm:p-10 shadow-2xl mb-6 relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20"></div>
    <div class="container mx-auto text-center relative z-10">
        <h1 class="text-white text-3xl sm:text-5xl font-bold tracking-tighter">PEA <span class="gold-text">INVENTORY</span> APP</h1>
        <p class="text-purple-200 mt-2 text-sm sm:text-lg">ระบบจัดการพัสดุไฟฟ้า อัจฉริยะ ⚡️ ทองคำแห่งการบริหาร</p>
    </div>
</header>

<main class="container mx-auto px-4 pb-20">

    <div x-show="selectedSubOf !== null" class="mb-6 animate__animated animate__fadeIn">
        <button @click="selectedSubOf = null" class="px-5 py-2.5 bg-purple-100 hover:bg-purple-200 text-purple-800 font-bold rounded-xl transition-all flex items-center gap-2 shadow-xs">
            ⬅️ ย้อนกลับไปเลือกหน่วยงานหลัก (sub_of)
        </button>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mt-4 border-l-4 border-purple-700 pl-3">
            📍 รายการพัสดุภายใต้หน่วยงาน: <span class="text-purple-700" x-text="selectedSubOf"></span>
        </h2>
    </div>

    <div x-show="selectedSubOf === null" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate__animated animate__fadeIn">
        <template x-for="(items, subOfName) in groupedData" :key="subOfName">
            <div @click="selectedSubOf = subOfName" 
                 class="bg-white rounded-3xl p-6 shadow-md border border-purple-50 card-hover cursor-pointer transition-all duration-300 flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 bg-purple-100 rounded-2xl flex items-center justify-center text-xl mb-4">🏢</div>
                    <h2 class="text-xl font-bold text-gray-800 tracking-tight" x-text="subOfName"></h2>
                    <p class="text-sm text-gray-400 mt-1">หน่วยงานควบคุมดูแลระบบจำหน่ายหลัก</p>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-50 flex justify-between items-center">
                    <span class="bg-purple-50 text-purple-700 text-xs font-bold px-3 py-1 rounded-full">
                        📦 คลังย่อยในระบบ: <span x-text="items.length"></span> แห่ง
                    </span>
                    <span class="text-purple-600 font-bold text-sm">กดเข้าดูที่นี่ ➡️</span>
                </div>
            </div>
        </template>
        <?php if (!$rawData): ?>
            <div class="col-span-3 text-center text-gray-500 py-12 font-medium">❌ ไม่สามารถดึงข้อมูลคลังพัสดุได้ในขณะนี้</div>
        <?php endif; ?>
    </div>

    <div x-show="selectedSubOf !== null" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8 animate__animated animate__fadeIn">
        <template x-for="(item, index) in (groupedData[selectedSubOf] || [])" :key="index">
            <div class="bg-white rounded-3xl shadow-lg border border-purple-50 card-hover transition-all duration-300 overflow-hidden">
                <div class="h-44 sm:h-48 bg-purple-100 relative overflow-hidden">
                    <img :src="item.display_images[0]" class="w-full h-full object-cover" alt="PEA Photo" referrerpolicy="no-referrer" loading="lazy">
                    <span class="absolute bottom-3 right-3 bg-purple-900/80 text-white text-xs px-2.5 py-1 rounded-full backdrop-blur-sm" x-text="'📸 ' + item.display_images.length + ' รูปภาพ'">
                    </span>
                </div>
                <div class="p-5 sm:p-6">
                    <div class="flex justify-between items-start mb-2">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-800 line-clamp-1" x-text="item.storage_detail || 'ไม่มีชื่อคลัง'"></h2>
                    </div>
                    <div class="flex justify-between items-start mb-4 gap-2">
                        <h3 class="text-sm sm:text-base font-medium text-gray-600 line-clamp-2 h-10 sm:h-12" x-text="item.details || 'ไม่มีรายละเอียด'"></h3>
                        <span class="bg-yellow-100 text-yellow-700 px-2.5 py-0.5 sm:py-1 rounded-full text-xs font-bold whitespace-nowrap" x-text="'✨ ' + item.materials"></span>
                    </div>
                    <div class="flex items-center gap-2 mb-4 sm:mb-6">
                        <span class="text-purple-900 font-bold text-base sm:text-lg" x-text="'จำนวน ' + Number(item.avaliable_unit).toLocaleString() + ' ' + (item.unit || 'ต้น')"></span>
                    </div>
       
                    <button @click="open = true; activeItem = item; $nextTick(() => { initSwiper(); })" 
                            class="w-full py-3.5 sm:py-4 bg-gradient-to-r from-purple-800 to-purple-600 text-white rounded-2xl font-bold text-sm sm:text-base shadow-lg shadow-purple-200 hover:scale-[1.02] active:scale-95 transition-all flex justify-center gap-2">
                        🔍 ดูรายละเอียดเพิ่มเติม
                    </button>
                </div>
            </div>
        </template>
    </div>
</main>

<div x-show="open" class="fixed inset-0 z-50 flex items-end md:items-center justify-center p-0 md:p-4 bg-black/60 backdrop-blur-sm" x-cloak>
    <div class="bg-white rounded-t-[2rem] md:rounded-[2rem] w-full max-w-lg md:max-w-md p-6 relative shadow-[0_-10px_30px_rgba(0,0,0,0.15)] md:shadow-[0_20px_50px_rgba(0,0,0,0.25)] max-h-[90vh] md:max-h-[85vh] overflow-y-auto" @click.away="destroySwiper(); open = false">
        
        <button @click="destroySwiper(); open = false" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 transition text-2xl md:text-3xl z-10 p-2">&times;</button>
        
        <div class="text-center mb-4 mt-2">
            <h2 class="text-xl font-bold text-purple-900 line-clamp-2 px-6" x-text="activeItem.details"></h2>
        </div>

        <div class="mb-4 relative">
            <div class="swiper mySwiper">
                <div class="swiper-wrapper">
                    <template x-for="(img, index) in activeItem.display_images" :key="index">
                        <div class="swiper-slide rounded-2xl overflow-hidden">
                            <img :src="img" class="w-full h-full object-cover" alt="พัสดุ PEA" referrerpolicy="no-referrer" loading="lazy">
                        </div>
                    </template>
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
        
        <div class="space-y-2 text-gray-700 bg-slate-50 p-4 rounded-2xl border border-slate-100 text-xs sm:text-sm mb-3">
            <p><strong>รหัสพัสดุ:</strong> <span x-text="activeItem.materials" class="text-purple-700 font-bold"></span></p>
            <p><strong>หน่วยงานหลัก (sub_of):</strong> <span x-text="activeItem.sub_of"></span></p>
            <p><strong>คลังจัดเก็บย่อย (storage_detail):</strong> <span x-text="activeItem.storage_detail" class="text-purple-950 font-medium"></span></p>
            <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                <span class="text-lg font-bold text-purple-700">คงเหลือ: <span x-text="Number(activeItem.avaliable_unit).toLocaleString()"></span> <span x-text="activeItem.unit"></span></span>
                <span class="text-xs bg-purple-100 px-2 py-1 rounded-full text-purple-800 font-bold" x-text="'ราคา: ' + Number(activeItem.price).toLocaleString() + ' THB'"></span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-indigo-50 p-4 rounded-2xl border border-purple-100 text-xs sm:text-sm">
            <h4 class="text-purple-900 font-bold text-sm mb-2 flex items-center gap-1.5">📞 ข้อมูลผู้ขาย / ช่องทางติดต่อ</h4>
            <div class="space-y-1.5 text-gray-700">
                <p class="flex items-center gap-2">
                    <span class="text-gray-400 w-20 inline-block font-medium">ชื่อผู้ติดต่อ:</span>
                    <span class="text-gray-900 font-semibold" x-text="activeItem.contact_name || 'ไม่ระบุชื่อ'"></span>
                </p>
                <p class="flex items-center gap-2">
                    <span class="text-gray-400 w-20 inline-block font-medium">เบอร์โทรศัพท์:</span>
                    <a :href="'tel:' + activeItem.contact_number" class="text-purple-700 font-bold hover:underline flex items-center gap-1" x-show="activeItem.contact_number">
                        <span x-text="activeItem.contact_number"></span> 📞
                    </a>
                </p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 gap-3 mt-4">
            <a :href="activeItem.location" target="_blank" 
               class="flex items-center justify-center gap-2 py-3 bg-gradient-to-r from-yellow-500 to-amber-500 text-white rounded-xl font-bold text-xs sm:text-sm shadow-md hover:scale-[1.02] active:scale-95 transition-all">
                📍 ไปที่ Google Maps
            </a>
        </div>
    </div>
</div>

<script>
    function initSwiper() {
        destroySwiper();
        this.swiperInstance = new Swiper(".mySwiper", {
            loop: true,
            observer: true,
            observeParents: true,
            pagination: { el: ".swiper-pagination", clickable: true },
            navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
        });
    }

    function destroySwiper() {
        if (this.swiperInstance) {
            this.swiperInstance.destroy(true, true);
            this.swiperInstance = null;
        }
    }
</script>
</body>
</html>