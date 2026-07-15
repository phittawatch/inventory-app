<?php
// 1. ดึงข้อมูลพัสดุจาก API
$url = "https://script.google.com/macros/s/AKfycbzyGkNj_5hCnoPD_DrMpzT7SY2L1GksaXeJUgACPscAPHI5l91z5ykMYiAav3ob4svdbg/exec";
$response = @file_get_contents($url);
$rawData = json_decode($response, true);

// 2. จัดกลุ่มข้อมูลใน PHP ตาม 'sub_of'
$groupedData = [];
$totalSubStorageCount = 0;

if (is_array($rawData)) {
    foreach ($rawData as $item) {
        $subOf = !empty($item['sub_of']) ? $item['sub_of'] : 'ไม่ระบุหน่วยงานหลัก';
        
        $item['display_images'] = (isset($item['images']) && is_array($item['images']) && count($item['images']) > 0) 
                                  ? $item['images'] 
                                  : ['https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=500'];
        
        $groupedData[$subOf][] = $item;
        $totalSubStorageCount++;
    }
}

$totalMainUnits = count($groupedData);
$jsonGroupedData = htmlspecialchars(json_encode($groupedData), ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PEA INVENTORY PREMIUM APP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Kanit', sans-serif; background-color: #f8fafc; }
        .pea-gradient { background: linear-gradient(135deg, #2e1065 0%, #4c1d95 60%, #6d28d9 100%); }
        .gold-text { color: #fbbf24; text-shadow: 0 0 12px rgba(251, 191, 36, 0.6); }
        
        /* การ์ดหน้าแรกสุดหรู */
        .premium-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(109, 40, 217, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .premium-card:hover {
            transform: translateY(-8px);
            border-color: #fbbf24;
            box-shadow: 0 20px 30px -10px rgba(76, 29, 149, 0.15), 0 0 15px rgba(251, 191, 36, 0.2);
        }
        
        /* หน้าจอ Loading จองธีมเดิมไว้ */
        .loading-gradient { background: linear-gradient(180deg, #1e1b4b 0%, #4c1d95 50%, #6d28d9 100%); }
        .gold-glow { box-shadow: 0 0 25px rgba(251, 191, 36, 0.7); }
        .pulse-layer { animation: pulseGlow 2s infinite ease-in-out; }
        @keyframes pulseGlow {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.15); opacity: 0.9; }
        }

        .swiper { width: 100%; height: 220px; border-radius: 1.5rem; }
        @media (min-width: 768px) { .swiper { height: 260px; } }
        .swiper-slide img { width: 100%; height: 100%; object-cover: cover; }
        .swiper-button-next, .swiper-button-prev { color: #6d28d9 !important; transform: scale(0.6); }
        .swiper-pagination-bullet-active { background: #6d28d9 !important; }
        .no-scroll { overflow: hidden; }
    </style>
</head>
<body class="no-scroll" 
      x-data="{ 
          open: false, 
          activeItem: {}, 
          swiperInstance: null, 
          isLoading: true, 
          touchStartY: 0,
          groupedData: <?php echo $jsonGroupedData; ?>,
          selectedSubOf: null 
      }"
      :class="{ 'no-scroll': open || isLoading }"
      x-init="setTimeout(() => { isLoading = false; }, 2200)">

<!-- 1. หน้าจอ Loading Screen -->
<div x-show="isLoading"
     x-transition:leave="transition ease-in duration-700 transform"
     x-transition:leave-start="translate-y-0 opacity-100"
     x-transition:leave-end="-translate-y-full opacity-0"
     @touchstart="touchStartY = $event.touches[0].clientY"
     @touchend="if (touchStartY - $event.changedTouches[0].clientY > 70) { isLoading = false; }"
     class="fixed inset-0 z-[100] loading-gradient flex flex-col justify-between items-center p-8 select-none text-center overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-15 pointer-events-none"></div>
    <div></div>
    <div class="relative z-10 flex flex-col items-center">
        <div class="w-28 h-28 bg-amber-500/15 rounded-full absolute pulse-layer flex items-center justify-center blur-md"></div>
        <div class="w-20 h-20 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-3xl flex items-center justify-center text-4xl shadow-2xl gold-glow animate__animated animate__bounceIn">⚡️</div>
        <h1 class="text-white text-4xl font-bold tracking-tight mt-6 animate__animated animate__fadeInUp animate__delay-1s">
            PEA <span class="gold-text">INVENTORY</span> HUB
        </h1>
        <p class="text-purple-200 mt-3 text-base max-w-xs font-light tracking-wide animate__animated animate__fadeInUp animate__delay-1s">
            ระบบจัดการคลังพัสดุเชิงลึก กฟฉ.3<br><span class="text-yellow-400/90 font-medium">"มุ่งสู่การบริหารงานระดับพรีเมียม"</span>
        </p>
    </div>
    <div class="relative z-10 flex flex-col items-center mb-4 animate__animated animate__fadeInUp animate__delay-2s pointer-events-none">
        <div class="text-amber-400 text-xl font-bold mb-1 animate-bounce">▲</div>
        <p class="text-purple-300 text-xs tracking-widest font-light uppercase">Swipe Up เพื่อเข้าสู่ระบบ</p>
    </div>
</div>

<!-- 2. Premium Header -->
<header class="pea-gradient pt-8 pb-16 px-6 sm:px-10 shadow-2xl mb-6 relative overflow-hidden rounded-b-[2.5rem] sm:rounded-b-[4rem]">
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-15"></div>
    <div class="absolute -right-16 -top-16 w-64 h-64 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
    <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-amber-500/5 rounded-full blur-2xl pointer-events-none"></div>
    
    <div class="container mx-auto text-center relative z-10">
        <div class="inline-block px-3 py-1 bg-white/10 text-yellow-300 text-xs font-semibold uppercase tracking-widest rounded-full mb-3 backdrop-blur-md">
            ⚡️ PEA Region 3 Inventory Management
        </div>
        <h1 class="text-white text-3xl sm:text-6xl font-extrabold tracking-tighter">PEA <span class="gold-text">INVENTORY</span> APP</h1>
        <p class="text-purple-200/90 mt-2 text-sm sm:text-xl font-light">ระบบศูนย์กลางข้อมูลและวิเคราะห์สถานะคลังพัสดุไฟฟ้าอย่างแม่นยำ</p>
    </div>
</header>

<main class="container mx-auto px-4 pb-24 -mt-8 relative z-20">

    <!-- 3. ส่วนสถิติภาพรวมหน้าแรก (ดึงดูดสายตาบอสใหญ่) -->
    <div x-show="selectedSubOf === null" class="grid grid-cols-2 md:grid-cols-2 gap-4 mb-8 max-w-4xl mx-auto animate__animated animate__fadeInUp">
        <div class="bg-gradient-to-br from-purple-900 to-indigo-950 p-5 rounded-3xl text-center text-white border border-white/10 shadow-xl">
            <span class="text-xs text-purple-200/70 block uppercase font-medium tracking-wider mb-1">หน่วยงานหลัก</span>
            <span class="text-3xl sm:text-4xl font-extrabold gold-text"><?php echo $totalMainUnits; ?></span> <span class="text-xs text-purple-300 font-light">กฟจ.</span>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-yellow-600 p-5 rounded-3xl text-center text-white border border-white/10 shadow-xl">
            <span class="text-xs text-yellow-100/80 block uppercase font-medium tracking-wider mb-1">จำนวนคลังย่อยทั้งหมด</span>
            <span class="text-3xl sm:text-4xl font-extrabold text-white"><?php echo $totalSubStorageCount; ?></span> <span class="text-xs text-yellow-100 font-light">รายการ</span>
        </div>
    </div>

    <!-- 4. แถบควบคุมเมื่อคลิกเข้ามาหน้ารายการย่อย -->
    <div x-show="selectedSubOf !== null" class="mb-6 animate__animated animate__fadeIn" x-cloak>
        <button @click="selectedSubOf = null" class="px-5 py-3 bg-purple-900 hover:bg-purple-950 text-white font-bold rounded-2xl transition-all flex items-center gap-2 shadow-lg shadow-purple-900/20 active:scale-95">
            ⬅️ กลับสู่หน้าหลักหลัก (เลือกจังหวัด)
        </button>
        <div class="mt-6 flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-purple-100 pb-4">
            <h2 class="text-xl sm:text-3xl font-bold text-slate-800 flex items-center gap-2">
                <span class="text-purple-700">📍 สังกัด:</span> <span x-text="selectedSubOf" class="underline decoration-amber-400 decoration-4"></span>
            </h2>
            <span class="bg-purple-100 text-purple-800 font-bold px-3 py-1.5 rounded-xl text-sm self-start" x-text="'คลังในสังกัด ' + (groupedData[selectedSubOf] ? groupedData[selectedSubOf].length : 0) + ' แห่ง'"></span>
        </div>
    </div>

    <!-- 5. [หน้าแรก] รายชื่อกลุ่ม 'sub_of' ดีไซน์พรีเมียมหรูหรา -->
    <div x-show="selectedSubOf === null" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8 max-w-6xl mx-auto">
        <template x-for="(items, subOfName) in groupedData" :key="subOfName">
            <div @click="selectedSubOf = subOfName" 
                 class="premium-card rounded-[2rem] p-6 sm:p-8 shadow-lg cursor-pointer flex flex-col justify-between group relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 text-slate-100 group-hover:text-purple-50/70 transition-colors text-9xl font-bold select-none pointer-events-none z-0" x-text="subOfName.substring(4,7)"></div>
                
                <div class="relative z-10">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-100 to-indigo-50 text-purple-700 rounded-2xl flex items-center justify-center text-2xl mb-6 shadow-inner group-hover:from-purple-700 group-hover:to-purple-900 group-hover:text-white transition-all duration-300">🏢</div>
                    <h2 class="text-2xl font-bold text-slate-800 group-hover:text-purple-950 transition-colors" x-text="subOfName"></h2>
                    <p class="text-sm text-slate-400 mt-2 font-light">การบริหารคลังพัสดุและเสาคอนกรีตจำหน่ายในพื้นที่การไฟฟ้า</p>
                </div>
                
                <div class="mt-8 pt-4 border-t border-slate-100 relative z-10 flex justify-between items-center">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                        <span class="text-purple-950 font-bold text-sm" x-text="items.length + ' คลังย่อยในระบบ'"></span>
                    </div>
                    <span class="w-9 h-9 rounded-full bg-slate-50 flex items-center justify-center text-purple-700 font-bold group-hover:bg-purple-700 group-hover:text-white shadow-xs transition-all duration-300">➔</span>
                </div>
            </div>
        </template>
    </div>

    <!-- 6. [หน้าสอง] รายชื่อ 'storage_detail' หลังจากคลิกเลือกกลุ่มย่อยเข้ามาแล้ว -->
    <div x-show="selectedSubOf !== null" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8 animate__animated animate__fadeIn" x-cloak>
        <template x-for="(item, index) in (groupedData[selectedSubOf] || [])" :key="index">
            <div class="bg-white rounded-[2rem] shadow-xl border border-purple-50 hover:shadow-2xl transition-all duration-300 overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="h-48 bg-purple-900 relative overflow-hidden">
                        <img :src="item.display_images[0]" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" alt="PEA Photo" referrerpolicy="no-referrer" loading="lazy">
                        <span class="absolute top-3 left-3 bg-purple-900/90 text-white text-xs font-bold px-3 py-1 rounded-full backdrop-blur-md" x-text="item.storage_id"></span>
                        <span class="absolute bottom-3 right-3 bg-black/60 text-white text-xs px-2.5 py-1 rounded-full backdrop-blur-xs" x-text="'📸 ' + item.display_images.length + ' ภาพ'"></span>
                    </div>
                    <div class="p-6">
                        <div class="flex justify-between items-start gap-2 mb-3">
                            <h2 class="text-xl font-bold text-slate-900 line-clamp-1" x-text="item.storage_detail || 'คลังไม่มีชื่อ'"></h2>
                        </div>
                        <h3 class="text-sm font-light text-slate-500 line-clamp-2 mb-4 h-10" x-text="item.details || 'ไม่มีรายละเอียดสินค้า'"></h3>
                        
                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100 flex justify-between items-center">
                            <div>
                                <span class="text-xs text-slate-400 block font-medium uppercase">ยอดคงเหลือพร้อมส่ง</span>
                                <span class="text-xl font-extrabold text-purple-900" x-text="Number(item.avaliable_unit).toLocaleString() + ' ' + (item.unit || 'ต้น')"></span>
                            </div>
                            <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2.5 py-1 rounded-md" x-text="item.materials"></span>
                        </div>
                    </div>
                </div>
                
                <div class="p-6 pt-0">
                    <button @click="open = true; activeItem = item; $nextTick(() => { initSwiper(); })" 
                            class="w-full py-4 bg-gradient-to-r from-purple-800 to-indigo-900 text-white rounded-xl font-bold text-sm shadow-md hover:scale-[1.01] active:scale-95 transition-all flex justify-center gap-2 items-center">
                        🔍 เจาะลึกข้อมูลพัสดุ
                    </button>
                </div>
            </div>
        </template>
    </div>
</main>

<!-- 7. Modal รายละเอียดเชิงลึก -->
<div x-show="open" class="fixed inset-0 z-50 flex items-end md:items-center justify-center p-0 md:p-4 bg-black/70 backdrop-blur-sm" x-cloak>
    <div class="bg-white rounded-t-[2.5rem] md:rounded-[2.5rem] w-full max-w-lg md:max-w-md p-6 relative shadow-2xl max-h-[90vh] md:max-h-[85vh] overflow-y-auto animate__animated animate__fadeInUp animate__fast" @click.away="destroySwiper(); open = false">
        
        <button @click="destroySwiper(); open = false" class="absolute top-4 right-4 text-slate-400 hover:text-red-500 transition text-3xl z-10 p-2">&times;</button>
        
        <div class="text-center mb-4 mt-2">
            <h2 class="text-xl font-bold text-purple-900 px-6 line-clamp-2" x-text="activeItem.details"></h2>
            <span class="text-xs text-slate-400 mt-1 block">รายละเอียดพัสดุอย่างเป็นทางการ</span>
        </div>

        <div class="mb-5 relative">
            <div class="swiper mySwiper shadow-inner border border-slate-100">
                <div class="swiper-wrapper">
                    <template x-for="(img, index) in activeItem.display_images" :key="index">
                        <div class="swiper-slide rounded-2xl overflow-hidden bg-slate-100">
                            <img :src="img" class="w-full h-full object-cover" alt="พัสดุ PEA" referrerpolicy="no-referrer" loading="lazy">
                        </div>
                    </template>
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
        
        <div class="space-y-2.5 text-slate-700 bg-slate-50 p-5 rounded-2xl border border-slate-100 text-xs sm:text-sm mb-4">
            <div class="flex justify-between border-b border-slate-200/60 pb-1.5">
                <span class="text-slate-400">รหัสพัสดุ (Material):</span>
                <span x-text="activeItem.materials" class="text-purple-700 font-bold"></span>
            </div>
            <div class="flex justify-between border-b border-slate-200/60 pb-1.5">
                <span class="text-slate-400">หน่วยงานหลัก :</span>
                <span x-text="activeItem.sub_of" class="font-medium"></span>
            </div>
            <div class="flex justify-between border-b border-slate-200/60 pb-1.5">
                <span class="text-slate-400">คลังจัดเก็บ (storage_detail):</span>
                <span x-text="activeItem.storage_detail" class="text-purple-950 font-bold text-right max-w-[200px] line-clamp-1"></span>
            </div>
            <div class="flex justify-between items-center pt-2">
                <span class="text-base font-extrabold text-purple-900">คงเหลือสุทธิ: <span x-text="Number(activeItem.avaliable_unit).toLocaleString()"></span> <span x-text="activeItem.unit"></span></span>
                <span class="text-xs bg-amber-100 px-2.5 py-1 rounded-full text-amber-900 font-extrabold" x-text="'มูลค่า: ' + Number(activeItem.price).toLocaleString() + ' THB'"></span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-indigo-50 p-4 rounded-2xl border border-purple-100 text-xs sm:text-sm mb-4">
            <h4 class="text-purple-900 font-bold text-sm mb-2 flex items-center gap-1.5">📞 ข้อมูลบุคลากรผู้รับผิดชอบ</h4>
            <div class="space-y-2 text-slate-700">
                <p class="flex justify-between">
                    <span class="text-slate-400 font-medium">ชื่อผู้ติดต่อ:</span>
                    <span class="text-slate-900 font-semibold" x-text="activeItem.contact_name || 'ไม่ระบุชื่อ'"></span>
                </p>
                <p class="flex justify-between">
                    <span class="text-slate-400 font-medium">เบอร์โทรศัพท์:</span>
                    <a :href="'tel:' + activeItem.contact_number" class="text-purple-700 font-bold hover:underline bg-white px-2 py-0.5 rounded border border-purple-200" x-show="activeItem.contact_number">
                        <span x-text="activeItem.contact_number"></span> 📞
                    </a>
                </p>
            </div>
        </div>
        
        <a :href="activeItem.location" target="_blank" 
           class="flex items-center justify-center gap-2 py-3.5 bg-gradient-to-r from-amber-500 to-yellow-500 text-white rounded-xl font-bold text-sm shadow-md hover:brightness-105 active:scale-95 transition-all w-full">
            📍 นำทางผ่าน Google Maps
        </a>
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